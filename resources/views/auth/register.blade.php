<x-guest-layout>
    @section('title', 'Регистрация')

    <div class="mb-6">
        <h1 class="text-2xl font-display font-bold tracking-tight">Регистрация водителя</h1>
        <p class="text-sm text-ink-300 mt-1">Заполните личные и паспортные данные. Доступ — <span class="badge badge-driver align-middle">driver</span></p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5" autocomplete="off">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <x-input-label for="last_name" :value="'Фамилия'" />
                <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required autofocus />
                <x-input-error :messages="$errors->get('last_name')" />
            </div>
            <div>
                <x-input-label for="first_name" :value="'Имя'" />
                <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required />
                <x-input-error :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="middle_name" :value="'Отчество'" />
                <x-text-input id="middle_name" type="text" name="middle_name" :value="old('middle_name')" />
                <x-input-error :messages="$errors->get('middle_name')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <x-input-label for="birth_date" :value="'Дата рождения'" />
                <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date')" />
                <x-input-error :messages="$errors->get('birth_date')" />
            </div>
            <div>
                <x-input-label for="birth_place" :value="'Место рождения'" />
                <x-text-input id="birth_place" type="text" name="birth_place" :value="old('birth_place')" maxlength="255" placeholder="Гор. Саратов" />
                <x-input-error :messages="$errors->get('birth_place')" />
            </div>
        </div>

        <fieldset class="rounded-xl border border-white/8 px-4 py-3">
            <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Контакты и адреса</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                <div>
                    <x-input-label for="phone" :value="'Телефон'" />
                    <x-text-input id="phone" type="tel" name="phone" :value="old('phone')" maxlength="32" placeholder="+7…" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <div>
                    <x-input-label for="phone2" :value="'Телефон 2'" />
                    <x-text-input id="phone2" type="tel" name="phone2" :value="old('phone2')" maxlength="32" placeholder="+7… (доп.)" />
                    <x-input-error :messages="$errors->get('phone2')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="email" :value="'Email (для чека)'" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" maxlength="255" placeholder="mail@example.com" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address_registration" :value="'Адрес регистрации'" />
                    <x-text-input id="address_registration" type="text" name="address_registration" :value="old('address_registration')" maxlength="500" placeholder="город, улица, дом, кв." />
                    <x-input-error :messages="$errors->get('address_registration')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address_residence" :value="'Адрес проживания (если отличается)'" />
                    <x-text-input id="address_residence" type="text" name="address_residence" :value="old('address_residence')" maxlength="500" />
                    <x-input-error :messages="$errors->get('address_residence')" />
                </div>
            </div>
        </fieldset>

        <fieldset class="rounded-xl border border-white/8 px-4 py-3">
            <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Паспортные данные</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                <div>
                    <x-input-label for="passport_series" :value="'Серия'" />
                    <x-text-input id="passport_series" type="text" name="passport_series" :value="old('passport_series')" maxlength="10" />
                    <x-input-error :messages="$errors->get('passport_series')" />
                </div>
                <div>
                    <x-input-label for="passport_number" :value="'Номер'" />
                    <x-text-input id="passport_number" type="text" name="passport_number" :value="old('passport_number')" maxlength="20" />
                    <x-input-error :messages="$errors->get('passport_number')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="passport_issued_by" :value="'Кем выдан'" />
                    <x-text-input id="passport_issued_by" type="text" name="passport_issued_by" :value="old('passport_issued_by')" />
                    <x-input-error :messages="$errors->get('passport_issued_by')" />
                </div>
                <div>
                    <x-input-label for="passport_issued_at" :value="'Когда выдан'" />
                    <x-text-input id="passport_issued_at" type="date" name="passport_issued_at" :value="old('passport_issued_at')" />
                    <x-input-error :messages="$errors->get('passport_issued_at')" />
                </div>
                <div>
                    <x-input-label for="passport_department_code" :value="'Код подразделения'" />
                    <x-text-input id="passport_department_code" type="text" name="passport_department_code" :value="old('passport_department_code')" maxlength="10" placeholder="000-000" />
                    <x-input-error :messages="$errors->get('passport_department_code')" />
                </div>
            </div>
        </fieldset>

        <fieldset class="rounded-xl border border-white/8 px-4 py-3">
            <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Учётная запись</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                <div class="sm:col-span-2">
                    <x-input-label for="login" :value="'Логин'" />
                    <x-text-input id="login" type="text" name="login" :value="old('login')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('login')" />
                </div>
                <div>
                    <x-input-label for="password" :value="'Пароль'" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" :value="'Повторите пароль'" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>
        </fieldset>

        <div class="pt-1">
            <label class="flex items-start gap-2.5 cursor-pointer select-none">
                <input type="checkbox" name="accept_offer" value="1" {{ old('accept_offer') ? 'checked' : '' }} required
                       class="mt-0.5 rounded border-white/20 bg-ink-800/70 text-neon-cyan focus:ring-neon-cyan">
                <span class="text-xs text-ink-300 leading-relaxed">
                    Я принимаю условия
                    <a href="{{ route('offer') }}" target="_blank" rel="noopener" class="text-neon-cyan hover:underline">публичной оферты</a>
                    и даю согласие на обработку персональных данных в соответствии с 152-ФЗ.
                </span>
            </label>
            <x-input-error :messages="$errors->get('accept_offer')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('login') }}" class="text-sm text-ink-300 hover:text-neon-cyan">Уже есть аккаунт? Войти</a>
            <x-primary-button>
                Зарегистрироваться
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
