<?php

namespace App\Http\Requests;

use App\Enums\TariffPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTariffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'period' => ['required', Rule::enum(TariffPeriod::class)],
            'period_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'extras' => ['nullable', 'array'],
            'extras.*.label' => ['required_with:extras.*.amount', 'string', 'max:255'],
            'extras.*.amount' => ['required_with:extras.*.label', 'numeric', 'min:0', 'max:9999999.99'],

            'is_buyout' => ['nullable', 'boolean'],
            'buyout_price' => ['nullable', 'required_if:is_buyout,1,true', 'numeric', 'min:0.01', 'max:99999999.99'],
            // buyout_days is auto-computed in prepareForValidation, not user-input
            'buyout_days' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function prepareForValidation(): void
    {
        // Filter out blank extras rows so they don't trip validation
        $extras = $this->input('extras', []);
        if (is_array($extras)) {
            $extras = array_values(array_filter($extras, function ($row) {
                if (! is_array($row)) {
                    return false;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $amount = $row['amount'] ?? null;
                return $label !== '' || ($amount !== null && $amount !== '');
            }));
            $this->merge(['extras' => $extras]);
        }
        $this->merge([
            'is_active' => (bool) $this->input('is_active', false),
            'is_buyout' => (bool) $this->input('is_buyout', false),
        ]);
        if (! $this->boolean('is_buyout')) {
            // Buyout off → blank out related fields so they don't get saved
            $this->merge(['buyout_price' => null, 'buyout_days' => null]);
        } else {
            // Buyout on → derive buyout_days from amount + price (rounded up).
            // Admin no longer enters days manually.
            $amount = (float) $this->input('amount', 0);
            $price = (float) $this->input('buyout_price', 0);
            if ($amount > 0 && $price > 0) {
                $this->merge(['buyout_days' => (int) ceil($price / $amount)]);
            }
        }
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'amount' => 'сумма списания',
            'period' => 'период',
            'period_count' => 'количество периодов',
            'deposit_amount' => 'депозит',
            'description' => 'описание',
            'is_buyout' => 'режим выкупа',
            'buyout_price' => 'выкупная стоимость',
            'buyout_days' => 'срок выкупа (дней)',
        ];
    }
}
