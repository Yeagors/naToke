<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Models\Car;
use App\Models\Tariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CarController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $cars = Car::query()
            ->withCount(['rentals as active_rentals_count' => function ($q) {
                $q->whereIn('status', ['open', 'paused']);
            }])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%")
                        ->orWhere('license_plate', 'like', "%{$q}%")
                        ->orWhere('battery_number', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('cars.index', compact('cars', 'q'));
    }

    public function create(): View
    {
        return view('cars.create');
    }

    public function store(StoreCarRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('cars', 'public');
        }

        Car::create($data);

        return redirect()
            ->route('cars.index')
            ->with('status', 'Авто добавлено.');
    }

    public function show(Car $car): View
    {
        $car->load([
            'activeRental.user',
            'activeRental.tariff',
            'rentals.user',
            'rentals.tariff',
            'carTransactions' => fn ($q) => $q->take(50),
        ]);

        $tariffs = Tariff::where('is_active', true)->orderBy('name')->get();

        return view('cars.show', compact('car', 'tariffs'));
    }

    public function update(StoreCarRequest $request, Car $car): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($car->photo) {
                Storage::disk('public')->delete($car->photo);
            }
            $data['photo'] = $request->file('photo')->store('cars', 'public');
        } else {
            unset($data['photo']);
        }

        $car->fill($data)->save();

        return redirect()->route('cars.show', $car)->with('status', 'Данные авто обновлены.');
    }
}
