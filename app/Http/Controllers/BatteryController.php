<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatteryRequest;
use App\Models\Battery;
use App\Models\Car;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BatteryController extends Controller
{
    public function index(): View
    {
        $batteries = Battery::withCount('activeRentals')->latest('id')->paginate(30);
        return view('batteries.index', compact('batteries'));
    }

    public function create(): View
    {
        return view('batteries.form', ['battery' => new Battery(), 'models' => $this->carModels()]);
    }

    public function store(StoreBatteryRequest $request): RedirectResponse
    {
        $battery = Battery::create($request->validated());
        ActivityLogger::log('batteries.created', $battery, "Добавлена АКБ {$battery->vin} ({$battery->car_model})");

        return redirect()->route('batteries.index')->with('toast', ['type' => 'success', 'message' => 'АКБ добавлена.']);
    }

    public function edit(Battery $battery): View
    {
        return view('batteries.form', ['battery' => $battery, 'models' => $this->carModels()]);
    }

    public function update(StoreBatteryRequest $request, Battery $battery): RedirectResponse
    {
        $battery->update($request->validated());
        ActivityLogger::log('batteries.updated', $battery, "Изменена АКБ {$battery->vin}");

        return redirect()->route('batteries.index')->with('toast', ['type' => 'success', 'message' => 'АКБ обновлена.']);
    }

    /** Distinct car models for the datalist (brand + model). */
    private function carModels(): array
    {
        return Car::query()
            ->orderBy('brand')->orderBy('model')
            ->get()
            ->map(fn ($c) => $c->display_name)
            ->unique()->values()->all();
    }
}
