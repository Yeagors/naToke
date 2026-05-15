<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'login',
        'password',
        'last_name',
        'first_name',
        'middle_name',
        'phone',
        'birth_date',
        'passport_series',
        'passport_number',
        'passport_issued_by',
        'passport_issued_at',
        'passport_department_code',
        'role',
        'balance',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'birth_date' => 'date',
            'passport_issued_at' => 'date',
            'balance' => 'decimal:2',
            'role' => UserRole::class,
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    public function getShortNameAttribute(): string
    {
        $initials = mb_substr($this->first_name, 0, 1) . '.';
        if ($this->middle_name) {
            $initials .= mb_substr($this->middle_name, 0, 1) . '.';
        }
        return trim("{$this->last_name} {$initials}");
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        return Storage::disk('public')->url($this->photo);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
