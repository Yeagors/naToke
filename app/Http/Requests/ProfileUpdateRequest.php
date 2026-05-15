<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class)->ignore($this->user()->id)],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_by' => ['nullable', 'string', 'max:255'],
            'passport_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'passport_department_code' => ['nullable', 'string', 'max:10'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => 'логин',
            'last_name' => 'фамилия',
            'first_name' => 'имя',
            'middle_name' => 'отчество',
            'birth_date' => 'дата рождения',
            'passport_series' => 'серия паспорта',
            'passport_number' => 'номер паспорта',
            'passport_issued_by' => 'кем выдан',
            'passport_issued_at' => 'когда выдан',
            'passport_department_code' => 'код подразделения',
            'photo' => 'фото',
        ];
    }
}
