<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * ВРЕМЕННЫЙ демо-контроллер: живой AI-продавец авто (PoC).
 * Удаляется целиком вместе с роутами /ai-seller-demo/* и вьюхой
 * resources/views/ai-seller-demo.blade.php. К основному домену naToke
 * отношения не имеет.
 *
 * Требует ANTHROPIC_API_KEY в .env.
 */
class AiSellerDemoController extends Controller
{
    private const API_URL   = 'https://api.anthropic.com/v1/messages';
    private const MODEL      = 'claude-haiku-4-5-20251001';
    private const RATE_ANNUAL = 0.18;

    /** Карточки в формате перекуп-канала. Публичные поля + скрытые min_price/deposit. */
    private function cars(): array
    {
        return [
            'car_001' => ['make' => 'Kia', 'model' => 'K5', 'year' => 2021, 'engine' => '2.0 л, бензин', 'gearbox' => 'автомат', 'drive' => 'передний', 'mileage_km' => 78000, 'color' => 'серый', 'city' => 'Москва', 'price' => 2190000, 'old_price' => 2350000, 'condition' => '1 владелец по ПТС, не битый. Пробег родной, сервисная история у дилера.', 'defects' => 'Мелкие сколы на переднем бампере от камней — видно на фото.', 'vin_report' => 'Автотека: 1 владелец, ДТП не найдено, не в залоге/аресте.', 'status' => 'в продаже', 'min_price' => 2080000, 'deposit' => 30000],
            'car_002' => ['make' => 'Skoda', 'model' => 'Octavia', 'year' => 2019, 'engine' => '1.4 TSI, бензин', 'gearbox' => 'робот DSG', 'drive' => 'передний', 'mileage_km' => 112000, 'color' => 'белый', 'city' => 'Санкт-Петербург', 'price' => 1640000, 'old_price' => 1720000, 'condition' => '2 владельца по ПТС, кузов ровный. Заменили масло и фильтры.', 'defects' => 'Честно: мехатроник DSG обслужен на 90 тыс, работает штатно, но за узлом надо следить. Потёртость на водительском сиденье.', 'vin_report' => 'Автотека: 2 владельца, одно мелкое ДТП (замена бампера), не в залоге.', 'status' => 'в продаже', 'min_price' => 1560000, 'deposit' => 25000],
            'car_003' => ['make' => 'Hyundai', 'model' => 'Tucson', 'year' => 2020, 'engine' => '2.0 л, дизель', 'gearbox' => 'автомат', 'drive' => 'полный', 'mileage_km' => 95000, 'color' => 'чёрный', 'city' => 'Москва', 'price' => 2540000, 'old_price' => 2540000, 'condition' => '1 владелец, дилерское обслуживание.', 'defects' => 'Без замечаний.', 'vin_report' => 'Автотека: 1 владелец, ДТП не найдено.', 'status' => 'продан', 'min_price' => 2420000, 'deposit' => 30000],
        ];
    }

    public function index()
    {
        return view('ai-seller-demo', ['hasKey' => (bool) config('services.anthropic.key')]);
    }

    public function chat(Request $request): JsonResponse
    {
        $key = config('services.anthropic.key');
        if (! $key) {
            return response()->json(['error' => 'Не задан ANTHROPIC_API_KEY в .env (после правки: php artisan config:clear).'], 400);
        }

        $messages = $request->input('messages', []);
        if (! is_array($messages) || empty($messages)) {
            return response()->json(['error' => 'Пустой диалог.'], 422);
        }

        $toolLog = [];
        // Агентный цикл: крутим, пока модель просит инструменты (лимит от зацикливания).
        for ($i = 0; $i < 8; $i++) {
            $resp = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(60)->post(self::API_URL, [
                'model'      => self::MODEL,
                'max_tokens' => 1024,
                'system'     => $this->systemPrompt(),
                'tools'      => $this->toolsSchema(),
                'messages'   => $messages,
            ]);

            if ($resp->failed()) {
                return response()->json(['error' => 'Anthropic API '.$resp->status().': '.$resp->body()], 502);
            }

            $data    = $resp->json();
            $content = $data['content'] ?? [];
            $messages[] = ['role' => 'assistant', 'content' => $content];

            if (($data['stop_reason'] ?? '') !== 'tool_use') {
                break;
            }

            $results = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') !== 'tool_use') {
                    continue;
                }
                $out = $this->executeTool($block['name'], $block['input'] ?? []);
                $toolLog[] = ['name' => $block['name'], 'input' => $block['input'] ?? [], 'output' => $out];
                $results[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'     => json_encode($out, JSON_UNESCAPED_UNICODE),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        // Собираем видимый текст последнего ответа ассистента.
        $reply = '';
        $last  = end($messages);
        if (($last['role'] ?? '') === 'assistant') {
            foreach ((array) $last['content'] as $b) {
                if (($b['type'] ?? '') === 'text') {
                    $reply .= $b['text'];
                }
            }
        }

        return response()->json(['reply' => trim($reply), 'tools' => $toolLog, 'messages' => $messages]);
    }

    /* ─────────────────────────── Инструменты ─────────────────────────── */

    private function executeTool(string $name, array $in): array
    {
        $cars = $this->cars();
        $car  = $cars[$in['car_id'] ?? ''] ?? null;

        return match ($name) {
            'list_cars' => ['cars' => array_map(fn ($c) => [
                'name' => "{$c['make']} {$c['model']} {$c['year']}", 'price' => $c['price'], 'city' => $c['city'], 'status' => $c['status'],
            ], $cars)],

            'get_car_details' => $car ? [
                'name' => "{$car['make']} {$car['model']} {$car['year']}", 'engine' => $car['engine'], 'gearbox' => $car['gearbox'],
                'drive' => $car['drive'], 'mileage_km' => $car['mileage_km'], 'color' => $car['color'], 'city' => $car['city'],
                'price' => $car['price'], 'old_price' => $car['old_price'], 'condition' => $car['condition'],
                'defects' => $car['defects'], 'vin_report' => $car['vin_report'], 'status' => $car['status'],
            ] : ['error' => 'Машина не найдена'],

            'check_availability' => $car
                ? ['status' => $car['status'], 'available' => $car['status'] === 'в продаже']
                : ['error' => 'Машина не найдена'],

            'propose_price' => $this->proposePrice($car, (int) ($in['offer'] ?? 0)),

            'calc_credit' => $car ? $this->calcCredit($car, (int) ($in['down_payment'] ?? 0), (int) ($in['term_months'] ?? 60)) : ['error' => 'Машина не найдена'],

            'create_booking' => $car ? ($car['status'] === 'в продаже' ? [
                'status' => 'reserved', 'booking_id' => 'BK-'.strtoupper(substr(md5(($in['car_id'] ?? '').($in['phone'] ?? '')), 0, 6)),
                'car' => "{$car['make']} {$car['model']} {$car['year']}", 'deposit' => $car['deposit'],
                'payment_link' => 'https://pay.avtokanal.ru/'.strtoupper(substr(md5(($in['car_id'] ?? '').($in['phone'] ?? '')), 0, 6)),
                'note' => 'Бронь после оплаты задатка, действует 24 часа. Задаток входит в стоимость.',
            ] : ['status' => 'failed', 'reason' => 'Машина уже забронирована или продана']) : ['error' => 'Машина не найдена'],

            'schedule_inspection' => $car ? [
                'status' => 'scheduled', 'car' => "{$car['make']} {$car['model']} {$car['year']}",
                'when' => $in['datetime'] ?? 'по договорённости', 'city' => $car['city'],
                'note' => 'Менеджер подтвердит точный адрес по SMS.',
            ] : ['error' => 'Машина не найдена'],

            'handoff_to_manager' => ['status' => 'handed_off', 'reason' => $in['reason'] ?? '', 'note' => 'Передано живому менеджеру, свяжется в ближайшее время.'],

            default => ['error' => "Неизвестный инструмент: {$name}"],
        };
    }

    /** Торг. Инвариант: не ниже min_price, min_price не возвращается модели. */
    private function proposePrice(?array $car, int $offer): array
    {
        if (! $car) {
            return ['error' => 'Машина не найдена'];
        }
        if ($car['status'] !== 'в продаже') {
            return ['status' => 'unavailable', 'reason' => 'Машина не в продаже'];
        }
        $cur = (int) $car['price'];
        $min = (int) $car['min_price'];
        if ($offer >= $cur) {
            return ['status' => 'accepted', 'final_price' => $cur, 'note' => 'Готовы оформить по текущей цене'];
        }
        if ($offer >= $min) {
            return ['status' => 'accepted', 'final_price' => $offer, 'note' => 'Ваша цена нам подходит'];
        }
        $counter = max($min, (int) (round(($cur - ($cur - $offer) * 0.5) / 1000) * 1000));

        return ['status' => 'counter', 'counter_price' => $counter, 'note' => 'Это лучшее, что можем по этой машине'];
    }

    private function calcCredit(array $car, int $down, int $term): array
    {
        $term      = max(1, $term);
        $principal = max(0, (int) $car['price'] - max(0, $down));
        $r         = self::RATE_ANNUAL / 12;
        $monthly   = $r > 0 ? $principal * $r / (1 - pow(1 + $r, -$term)) : $principal / $term;

        return [
            'car_price' => (int) $car['price'], 'down_payment' => max(0, $down), 'loan_principal' => $principal,
            'term_months' => $term, 'rate_annual_pct' => (int) round(self::RATE_ANNUAL * 100),
            'monthly_payment' => (int) round($monthly), 'total_cost' => (int) round($monthly * $term + $down),
            'overpay' => (int) round($monthly * $term - $principal),
        ];
    }

    private function toolsSchema(): array
    {
        $carId = ['type' => 'string', 'description' => 'ID машины, напр. car_001'];

        return [
            ['name' => 'list_cars', 'description' => 'Список всех машин в наличии: цена, город, статус.', 'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'get_car_details', 'description' => 'Полная карточка: характеристики, состояние, недостатки, отчёт VIN.', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId], 'required' => ['car_id']]],
            ['name' => 'check_availability', 'description' => 'Актуальный статус машины (в продаже/бронь/продан).', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId], 'required' => ['car_id']]],
            ['name' => 'propose_price', 'description' => 'Передать ценовое предложение клиента. Вернёт accepted (с итоговой ценой) или counter. Торгуйся ТОЛЬКО через этот инструмент.', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId, 'offer' => ['type' => 'integer', 'description' => 'Цена клиента в рублях']], 'required' => ['car_id', 'offer']]],
            ['name' => 'calc_credit', 'description' => 'Рассчитать автокредит: платёж и переплату.', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId, 'down_payment' => ['type' => 'integer'], 'term_months' => ['type' => 'integer']], 'required' => ['car_id']]],
            ['name' => 'create_booking', 'description' => 'Забронировать машину. Нужны имя и телефон. Вернёт ссылку на оплату задатка.', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId, 'name' => ['type' => 'string'], 'phone' => ['type' => 'string']], 'required' => ['car_id', 'name', 'phone']]],
            ['name' => 'schedule_inspection', 'description' => 'Записать клиента на осмотр.', 'input_schema' => ['type' => 'object', 'properties' => ['car_id' => $carId, 'name' => ['type' => 'string'], 'phone' => ['type' => 'string'], 'datetime' => ['type' => 'string']], 'required' => ['car_id']]],
            ['name' => 'handoff_to_manager', 'description' => 'Передать диалог живому менеджеру (trade-in, юр. вопросы, жалобы).', 'input_schema' => ['type' => 'object', 'properties' => ['reason' => ['type' => 'string']], 'required' => ['reason']]],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Ты — Алексей, менеджер по продажам автоподбора «АвтоКанал». Общаешься с клиентом в мессенджере.

Стиль: коротко, по-человечески, дружелюбно, без канцелярита и воды. Одно-два предложения на реплику. Эмодзи — умеренно.

Правила:
1. Говори ТОЛЬКО про машины в наличии и только на основе данных из инструментов. Не выдумывай характеристики — вызови get_car_details или list_cars.
2. Будь честен. Если в карточке есть недостатки (defects) — сам о них скажи, не скрывай. Это наша репутация.
3. Торгуйся ТОЛЬКО через инструмент propose_price. НИКОГДА не называй и не угадывай минимальную/закупочную цену — ты её не знаешь. Клиенту озвучивай только то, что вернул инструмент.
4. Для брони возьми имя и телефон, вызови create_booking и дай ссылку на оплату задатка. Объясни, что задаток входит в стоимость, бронь 24 часа.
5. Если машина не в продаже — честно скажи и предложи похожую из наличия.
6. Вопросы вне компетенции (trade-in, юр. детали, жалобы) — вызывай handoff_to_manager.
7. Валюта — рубли.
TXT;
    }
}
