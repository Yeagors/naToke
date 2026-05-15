<?php

namespace App\Http\Controllers;

use App\Enums\TariffPeriod;
use App\Http\Requests\StoreTariffRequest;
use App\Models\Tariff;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TariffController extends Controller
{
    public function index(Request $request): View
    {
        $tariffs = Tariff::query()
            ->withCount('rentals')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(20);

        return view('tariffs.index', compact('tariffs'));
    }

    public function create(): View
    {
        return view('tariffs.form', [
            'tariff' => new Tariff(['period' => TariffPeriod::Hour, 'period_count' => 1, 'is_active' => true]),
            'periods' => TariffPeriod::cases(),
        ]);
    }

    public function store(StoreTariffRequest $request): RedirectResponse
    {
        $tariff = Tariff::create($request->validated());

        ActivityLogger::log('tariffs.created', $tariff, "Создан тариф «{$tariff->name}» — {$tariff->amount} ₽ / {$tariff->period_count} {$tariff->period->label()}");

        return redirect()->route('tariffs.index')->with('status', 'Тариф создан.');
    }

    public function show(Tariff $tariff): View
    {
        return view('tariffs.form', [
            'tariff' => $tariff,
            'periods' => TariffPeriod::cases(),
        ]);
    }

    public function update(StoreTariffRequest $request, Tariff $tariff): RedirectResponse
    {
        $tariff->fill($request->validated())->save();

        $diff = ActivityLogger::diff($tariff);
        if (! empty($diff)) {
            ActivityLogger::log('tariffs.updated', $tariff, "Изменён тариф «{$tariff->name}»", $diff);
        }

        return redirect()->route('tariffs.show', $tariff)->with('status', 'Тариф обновлён.');
    }
}
