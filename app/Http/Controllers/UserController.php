<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Models\Car;
use App\Models\CarTransaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('login', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('middle_name', 'like', "%{$q}%")
                        ->orWhere('passport_number', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'q'));
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['role'] = $data['role'] ?? UserRole::Driver->value;
        $data['balance'] = 0;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('avatars', 'public');
        } else {
            unset($data['photo']);
        }

        $user = User::create($data);

        ActivityLogger::log(
            'users.created',
            $user,
            "Создан пользователь {$user->full_name} ({$user->role->value})"
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Пользователь создан.');
    }

    public function show(User $user): View
    {
        $user->load(['transactions' => fn ($q) => $q->take(15)]);

        return view('users.show', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = User::query()->orderBy('last_name')->limit(20);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('login', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        return response()->json(
            $query->get()->map(fn (User $u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'short_name' => $u->short_name,
                'login' => $u->login,
                'phone' => $u->phone,
                'role' => $u->role->value,
                'photo_url' => $u->photo_url,
                'initials' => mb_substr($u->first_name, 0, 1).mb_substr($u->last_name, 0, 1),
            ])->values()
        );
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $passwordChanged = false;

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $passwordChanged = true;
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('photo')->store('avatars', 'public');
        } else {
            unset($data['photo']);
        }

        $user->fill($data)->save();

        $diff = ActivityLogger::diff($user);
        if (! empty($diff)) {
            ActivityLogger::log(
                'users.updated',
                $user,
                "Изменён пользователь {$user->full_name}",
                $diff
            );
        }

        if ($passwordChanged) {
            ActivityLogger::log(
                'users.password_reset',
                $user,
                "Сброшен пароль пользователю {$user->full_name}"
            );
        }

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Профиль пользователя обновлён.');
    }

    /**
     * Delete a user with dependencies. FK cascade removes their transactions,
     * rentals and payment requests; created_by references are set to null.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Нельзя удалить самого себя.']);
        }

        $name = $user->full_name;

        DB::transaction(function () use ($user) {
            $rentalIds = $user->rentals()->pluck('id')->all();

            // Recompute car balances: reverse income the deleted user paid to cars.
            $carTxs = CarTransaction::whereIn('rental_id', $rentalIds)->get();
            foreach ($carTxs->groupBy('car_id') as $carId => $group) {
                $car = Car::lockForUpdate()->find($carId);
                if (! $car) {
                    continue;
                }
                $delta = 0.0;
                foreach ($group as $t) {
                    $delta += $t->type->sign() * (float) $t->amount;
                }
                $car->balance = (float) $car->balance - $delta;
                $car->save();
            }
            CarTransaction::whereIn('rental_id', $rentalIds)->delete();

            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            // FK cascade removes the user's own transactions, rentals and payment requests.
            $user->delete();
        });

        ActivityLogger::log('users.deleted', null, "Удалён пользователь: {$name} — вместе с арендами, транзакциями и пополнениями; балансы авто пересчитаны");

        return redirect()->route('users.index')
            ->with('toast', ['type' => 'success', 'message' => "Пользователь «{$name}» удалён вместе с зависимостями."]);
    }
}
