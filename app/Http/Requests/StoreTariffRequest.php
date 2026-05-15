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
        $this->merge(['is_active' => (bool) $this->input('is_active', false)]);
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
        ];
    }
}
