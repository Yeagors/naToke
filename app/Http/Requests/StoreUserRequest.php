<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $allowRoleChange = $this->user()?->isAdmin();

        $rules = [
            'login' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class)->ignore($userId)],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'phone2' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'address_registration' => ['nullable', 'string', 'max:500'],
            'address_residence' => ['nullable', 'string', 'max:500'],
            'passport_series' => ['nullable', 'string', 'max:10'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'passport_issued_by' => ['nullable', 'string', 'max:255'],
            'passport_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'passport_department_code' => ['nullable', 'string', 'max:10'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'confirmed', Password::min(6)];
        } else {
            $rules['password'] = ['nullable', 'confirmed', Password::min(6)];
        }

        if ($allowRoleChange) {
            $rules['role'] = ['required', Rule::enum(UserRole::class)];
        }

        // Согласие на обработку ПД (152-ФЗ) — только при публичной саморегистрации.
        // При создании пользователя администратором не требуется.
        if ($this->routeIs('register')) {
            $rules['accept_offer'] = ['accepted'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'accept_offer.accepted' => 'Необходимо принять условия оферты и согласие на обработку персональных данных.',
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => 'логин',
            'password' => 'пароль',
            'last_name' => 'фамилия',
            'first_name' => 'имя',
            'middle_name' => 'отчество',
            'phone' => 'телефон',
            'phone2' => 'телефон 2',
            'email' => 'email',
            'birth_date' => 'дата рождения',
            'birth_place' => 'место рождения',
            'address_registration' => 'адрес регистрации',
            'address_residence' => 'адрес проживания',
            'passport_series' => 'серия паспорта',
            'passport_number' => 'номер паспорта',
            'passport_issued_by' => 'кем выдан',
            'passport_issued_at' => 'когда выдан',
            'passport_department_code' => 'код подразделения',
            'role' => 'уровень доступов',
        ];
    }
}
