<?php

namespace App\Http\Requests;

use App\Enums\CarTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CarTransactionType::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'тип',
            'amount' => 'сумма',
            'comment' => 'комментарий',
        ];
    }
}
