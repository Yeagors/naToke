<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarTransaction;
use App\Models\Rental;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Monthly financial report — XLSX with five sheets:
     *   1. Сводка        — KPIs / totals
     *   2. По авто       — per-car income/expense/net/ROI for the month
     *   3. По водителям  — per-driver topup/spend/closing-balance
     *   4. Аренды        — rentals that touched the month (started/ended/charged)
     *   5. Транзакции    — full ledger (user + car)
     */
    public function monthly(Request $request): StreamedResponse
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        } catch (\Throwable $e) {
            abort(400, 'Invalid month parameter, expected YYYY-MM');
        }
        $end = $start->copy()->endOfMonth();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('naToke CRM')
            ->setTitle('Финансовый отчёт '.\Illuminate\Support\Str::ucfirst($start->locale('ru')->isoFormat('MMMM YYYY')));

        $this->renderSummary($spreadsheet, $start, $end);
        $this->renderCarsSheet($spreadsheet, $start, $end);
        $this->renderDriversSheet($spreadsheet, $start, $end);
        $this->renderRentalsSheet($spreadsheet, $start, $end);
        $this->renderLedgerSheet($spreadsheet, $start, $end);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "natoke-report-{$month}.xlsx";
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    // ---------- sheets ----------------------------------------------------

    private function renderSummary(Spreadsheet $book, Carbon $start, Carbon $end): void
    {
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Сводка');

        $sheet->setCellValue('A1', 'Финансовый отчёт naToke');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0a0b16']],
        ]);

        $sheet->setCellValue('A2', 'Период');
        $sheet->setCellValue('B2', \Illuminate\Support\Str::ucfirst($start->locale('ru')->isoFormat('MMMM YYYY'))." ({$start->format('d.m.Y')} – {$end->format('d.m.Y')})");

        $sheet->setCellValue('A3', 'Сгенерирован');
        $sheet->setCellValue('B3', now()->format('d.m.Y H:i').' MSK');

        // Aggregate totals
        $income = (float) CarTransaction::where('type', 'income')->whereBetween('created_at', [$start, $end])->sum('amount');
        $expense = (float) CarTransaction::where('type', 'expense')->whereBetween('created_at', [$start, $end])->sum('amount');
        // Только по водителям — доли владельцев (админов) в водительские потоки не входят.
        $driverTx = fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', 'driver'));
        $userIn = (float) Transaction::where('type', 'deposit')->whereBetween('created_at', [$start, $end])->where($driverTx)->sum('amount');
        $userOut = (float) Transaction::where('type', 'withdrawal')->whereBetween('created_at', [$start, $end])->where($driverTx)->sum('amount');

        $rentalsCreated = Rental::whereBetween('created_at', [$start, $end])->count();
        $rentalsClosed = Rental::whereBetween('closed_at', [$start, $end])->count();
        $chargesCount = Transaction::where('type', 'withdrawal')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('rental_id')->count();

        $rows = [
            ['Метрика', 'Значение'],
            ['Доход парка (income)', $income],
            ['Расходы парка (expense, ремонт/ТО)', $expense],
            ['Чистая прибыль парка', $income - $expense],
            ['', ''],
            ['Пополнения балансов водителей', $userIn],
            ['Списания с балансов водителей', $userOut],
            ['', ''],
            ['Создано аренд', $rentalsCreated],
            ['Закрыто аренд', $rentalsClosed],
            ['Кол-во крон-списаний по арендам', $chargesCount],
        ];
        $startRow = 5;
        foreach ($rows as $i => $r) {
            $row = $startRow + $i;
            $sheet->setCellValue('A'.$row, $r[0]);
            $sheet->setCellValue('B'.$row, $r[1]);
            if (is_numeric($r[1]) && $i > 0) {
                $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0.00 [$₽-419];-#,##0.00 [$₽-419]');
            }
        }
        $sheet->getStyle("A{$startRow}:B{$startRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00e5ff']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(28);
    }

    private function renderCarsSheet(Spreadsheet $book, Carbon $start, Carbon $end): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('По авто');

        $headers = ['ID', 'Авто', 'Номер', 'Стоимость закупа', 'Доход за месяц', 'Расходы за месяц', 'Чистая за месяц', 'ROI всего, %', 'Окупилось?'];
        $sheet->fromArray($headers, null, 'A1');
        $this->headerStyle($sheet, 'A1:I1');

        $incomeMonth = CarTransaction::where('type', 'income')->whereBetween('created_at', [$start, $end])
            ->groupBy('car_id')->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');
        $expenseMonth = CarTransaction::where('type', 'expense')->whereBetween('created_at', [$start, $end])
            ->groupBy('car_id')->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');
        $incomeAll = CarTransaction::where('type', 'income')->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');
        $expenseAll = CarTransaction::where('type', 'expense')->groupBy('car_id')
            ->select('car_id', DB::raw('SUM(amount) as total'))->pluck('total', 'car_id');

        $row = 2;
        foreach (Car::orderByDesc('id')->get() as $car) {
            $incM = (float) ($incomeMonth[$car->id] ?? 0);
            $expM = (float) ($expenseMonth[$car->id] ?? 0);
            $netLife = (float) ($incomeAll[$car->id] ?? 0) - (float) ($expenseAll[$car->id] ?? 0);
            $purchase = $car->purchase_price !== null ? (float) $car->purchase_price : null;
            $roi = ($purchase !== null && $purchase > 0) ? round($netLife / $purchase * 100, 1) : null;
            $paidBack = $purchase !== null ? ($netLife >= $purchase ? 'да' : 'нет') : '—';

            $sheet->setCellValue('A'.$row, $car->id);
            $sheet->setCellValue('B'.$row, $car->display_name);
            $sheet->setCellValue('C'.$row, $car->license_plate);
            $sheet->setCellValue('D'.$row, $purchase);
            $sheet->setCellValue('E'.$row, $incM);
            $sheet->setCellValue('F'.$row, -$expM);
            $sheet->setCellValue('G'.$row, $incM - $expM);
            $sheet->setCellValue('H'.$row, $roi);
            $sheet->setCellValue('I'.$row, $paidBack);
            $row++;
        }
        foreach (['D', 'E', 'F', 'G'] as $col) {
            $sheet->getStyle("{$col}2:{$col}".($row - 1))->getNumberFormat()->setFormatCode('#,##0.00 [$₽-419];-#,##0.00 [$₽-419]');
        }
        $sheet->getStyle("H2:H".($row - 1))->getNumberFormat()->setFormatCode('0.0%');
        // Convert ROI percent to fraction display (we stored as percent)
        // Actually keep as text-like — set format 0.0\%:
        $sheet->getStyle("H2:H".($row - 1))->getNumberFormat()->setFormatCode('0.0"%"');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(14);

        $sheet->freezePane('A2');
    }

    private function renderDriversSheet(Spreadsheet $book, Carbon $start, Carbon $end): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('По водителям');

        $headers = ['ID', 'ФИО', 'Логин', 'Пополнено за месяц', 'Списано за месяц', 'Чистый расход', 'Баланс сейчас'];
        $sheet->fromArray($headers, null, 'A1');
        $this->headerStyle($sheet, 'A1:G1');

        $deposit = Transaction::where('type', 'deposit')->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')->select('user_id', DB::raw('SUM(amount) as total'))->pluck('total', 'user_id');
        $withdrawal = Transaction::where('type', 'withdrawal')->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')->select('user_id', DB::raw('SUM(amount) as total'))->pluck('total', 'user_id');

        $row = 2;
        foreach (User::where('role', 'driver')->orderBy('id')->get() as $u) {
            $d = (float) ($deposit[$u->id] ?? 0);
            $w = (float) ($withdrawal[$u->id] ?? 0);
            if ($d == 0 && $w == 0) continue; // skip users with no activity this month

            $sheet->setCellValue('A'.$row, $u->id);
            $sheet->setCellValue('B'.$row, $u->full_name);
            $sheet->setCellValue('C'.$row, $u->login);
            $sheet->setCellValue('D'.$row, $d);
            $sheet->setCellValue('E'.$row, -$w);
            $sheet->setCellValue('F'.$row, $d - $w);
            $sheet->setCellValue('G'.$row, (float) $u->balance);
            $row++;
        }
        foreach (['D', 'E', 'F', 'G'] as $col) {
            $sheet->getStyle("{$col}2:{$col}".($row - 1))->getNumberFormat()->setFormatCode('#,##0.00 [$₽-419];-#,##0.00 [$₽-419]');
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(20);

        $sheet->freezePane('A2');
    }

    private function renderRentalsSheet(Spreadsheet $book, Carbon $start, Carbon $end): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Аренды');

        $headers = ['ID', 'Авто', 'Арендатор', 'Тариф', 'Сумма списания', 'Период', 'Статус', 'Начата', 'Закрыта'];
        $sheet->fromArray($headers, null, 'A1');
        $this->headerStyle($sheet, 'A1:I1');

        $rentals = Rental::with(['car', 'user', 'tariff'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('started_at', [$start, $end])
                  ->orWhereBetween('closed_at', [$start, $end])
                  ->orWhere(function ($qq) use ($start, $end) {
                      $qq->where('started_at', '<', $start)
                         ->where(function ($qqq) use ($end) {
                             $qqq->whereNull('closed_at')->orWhere('closed_at', '>=', $end);
                         });
                  });
            })
            ->orderByDesc('id')
            ->get();

        $row = 2;
        foreach ($rentals as $r) {
            $sheet->setCellValue('A'.$row, $r->id);
            $sheet->setCellValue('B'.$row, $r->car?->display_name.' / '.$r->car?->license_plate);
            $sheet->setCellValue('C'.$row, $r->user?->full_name);
            $sheet->setCellValue('D'.$row, $r->tariff?->name);
            $sheet->setCellValue('E'.$row, (float) $r->amount);
            $sheet->setCellValue('F'.$row, $r->period_count.' '.$r->period?->label());
            $sheet->setCellValue('G'.$row, $r->status?->label());
            $sheet->setCellValue('H'.$row, $r->started_at?->format('d.m.Y H:i'));
            $sheet->setCellValue('I'.$row, $r->closed_at?->format('d.m.Y H:i'));
            $row++;
        }
        $sheet->getStyle("E2:E".($row - 1))->getNumberFormat()->setFormatCode('#,##0.00 [$₽-419]');

        foreach (['A' => 6, 'B' => 30, 'C' => 30, 'D' => 22, 'E' => 16, 'F' => 14, 'G' => 16, 'H' => 18, 'I' => 18] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('A2');
    }

    private function renderLedgerSheet(Spreadsheet $book, Carbon $start, Carbon $end): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Транзакции');

        $headers = ['Дата', 'Источник', 'Объект', 'Тип', 'Сумма', 'Баланс после', 'Аренда', 'Комментарий'];
        $sheet->fromArray($headers, null, 'A1');
        $this->headerStyle($sheet, 'A1:H1');

        // Детализация — только транзакции водителей (доли владельцев/компании исключаем).
        $userTx = Transaction::with(['user', 'rental.car'])
            ->whereHas('user', fn ($q) => $q->where('role', 'driver'))
            ->whereBetween('created_at', [$start, $end])->get();
        $carTx = CarTransaction::with(['car', 'rental.user'])->whereBetween('created_at', [$start, $end])->get();

        $rows = collect();
        foreach ($userTx as $t) {
            $sign = $t->type->value === 'deposit' ? 1 : -1;
            $rows->push([
                'created' => $t->created_at,
                'kind' => 'user',
                'subject' => $t->user?->full_name,
                'type_label' => $t->type->label(),
                'amount' => $sign * (float) $t->amount,
                'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
                'rental' => $t->rental_id,
                'comment' => $t->comment,
            ]);
        }
        foreach ($carTx as $t) {
            $sign = $t->type->value === 'income' ? 1 : -1;
            $rows->push([
                'created' => $t->created_at,
                'kind' => 'car',
                'subject' => $t->car?->display_name.' / '.$t->car?->license_plate,
                'type_label' => $t->type->label(),
                'amount' => $sign * (float) $t->amount,
                'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
                'rental' => $t->rental_id,
                'comment' => $t->comment,
            ]);
        }
        $rows = $rows->sortByDesc('created')->values();

        $row = 2;
        foreach ($rows as $r) {
            $sheet->setCellValue('A'.$row, $r['created']->format('d.m.Y H:i:s'));
            $sheet->setCellValue('B'.$row, $r['kind'] === 'user' ? 'Пользователь' : 'Авто');
            $sheet->setCellValue('C'.$row, $r['subject']);
            $sheet->setCellValue('D'.$row, $r['type_label']);
            $sheet->setCellValue('E'.$row, $r['amount']);
            $sheet->setCellValue('F'.$row, $r['balance_after']);
            $sheet->setCellValue('G'.$row, $r['rental'] ? '#'.$r['rental'] : '');
            $sheet->setCellValue('H'.$row, $r['comment'] ?: '');
            $row++;
        }
        $sheet->getStyle("E2:F".($row - 1))->getNumberFormat()->setFormatCode('#,##0.00 [$₽-419];-#,##0.00 [$₽-419]');

        foreach (['A' => 20, 'B' => 14, 'C' => 30, 'D' => 22, 'E' => 16, 'F' => 16, 'G' => 10, 'H' => 50] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('A2');
    }

    private function headerStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0a0b16']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00e5ff']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0a0b16']]],
        ]);
    }
}
