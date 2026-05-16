<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_label',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'changes',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Group key used by filter chips on the logs page.
     */
    public function getGroupAttribute(): string
    {
        $action = $this->action;
        return match (true) {
            str_starts_with($action, 'auth.') => 'auth',
            str_starts_with($action, 'users.'), str_starts_with($action, 'profile.') => 'users',
            str_starts_with($action, 'tariffs.') => 'tariffs',
            str_starts_with($action, 'cars.') => 'cars',
            str_starts_with($action, 'rentals.'), str_starts_with($action, 'cron.rental') => 'rentals',
            str_starts_with($action, 'transactions.'), str_starts_with($action, 'car_transactions.') => 'money',
            default => 'other',
        };
    }

    /**
     * Human-readable label for the action.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'auth.login' => 'Вход',
            'auth.logout' => 'Выход',
            'auth.register' => 'Регистрация',
            'users.created' => 'Создан пользователь',
            'users.updated' => 'Изменён пользователь',
            'users.password_reset' => 'Сброс пароля (админ)',
            'profile.updated' => 'Профиль обновлён',
            'profile.password_changed' => 'Смена пароля',
            'tariffs.created' => 'Создан тариф',
            'tariffs.updated' => 'Изменён тариф',
            'cars.created' => 'Добавлено авто',
            'cars.updated' => 'Изменено авто',
            'transactions.deposit' => 'Пополнение баланса пользователя',
            'transactions.withdrawal' => 'Списание с баланса пользователя',
            'car_transactions.income' => 'Приход на баланс авто',
            'car_transactions.expense' => 'Расход с баланса авто',
            'rentals.created' => 'Создана аренда',
            'rentals.paused' => 'Аренда приостановлена',
            'rentals.resumed' => 'Аренда возобновлена',
            'rentals.closed' => 'Аренда закрыта',
            'cron.rental_charge' => 'Списание по аренде (крон)',
            'cron.rental_buyout_completed' => 'Авто выкуплено (крон)',
            default => $this->action,
        };
    }

    /**
     * Tailwind class for the action chip.
     */
    public function getActionBadgeAttribute(): string
    {
        return match ($this->group) {
            'auth' => 'badge-driver',
            'users' => 'badge-driver',
            'tariffs' => 'badge-driver',
            'cars' => 'badge-driver',
            'rentals' => 'badge-admin',
            'money' => str_contains($this->action, 'deposit') || str_contains($this->action, 'income')
                ? 'badge-deposit'
                : 'badge-withdrawal',
            default => 'badge-driver',
        };
    }

    /**
     * URL for the subject of this log entry (if applicable).
     */
    public function getSubjectUrlAttribute(): ?string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        return match ($this->subject_type) {
            User::class => route('users.show', $this->subject_id),
            Car::class => route('cars.show', $this->subject_id),
            Tariff::class => route('tariffs.show', $this->subject_id),
            Rental::class => route('rentals.show', $this->subject_id),
            default => null,
        };
    }
}
