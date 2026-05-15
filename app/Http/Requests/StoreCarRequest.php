<?php

namespace App\Http\Requests;

use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $carId = $this->route('car')?->id;

        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1980,'.(int) date('Y')],
            'balance' => ['nullable', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'battery_capacity' => ['nullable', 'integer', 'between:0,500000'],
            'battery_number' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['required', 'string', 'max:32', Rule::unique(Car::class)->ignore($carId)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'brand' => 'марка',
            'model' => 'модель',
            'year' => 'год выпуска',
            'balance' => 'баланс',
            'comment' => 'комментарий',
            'battery_capacity' => 'размер аккумулятора',
            'battery_number' => 'номер аккумулятора',
            'license_plate' => 'номер авто',
            'photo' => 'фото',
        ];
    }
}
