<?php

namespace App\Http\Requests;

use App\Models\Battery;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists(User::class, 'id')],
            'tariff_id' => ['required', Rule::exists(Tariff::class, 'id')->where('is_active', true)],
            'battery_ids' => ['nullable', 'array'],
            'battery_ids.*' => [Rule::exists(Battery::class, 'id')],
            'started_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'арендатор',
            'tariff_id' => 'тариф',
            'started_at' => 'дата начала',
        ];
    }
}
