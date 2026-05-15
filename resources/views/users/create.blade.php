<x-app-layout>
    @section('title', 'Новый пользователь')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <a href="{{ route('users.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    к списку
                </a>
                <h1 class="text-3xl font-display font-bold tracking-tight">Новый пользователь</h1>
            </div>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="glass rounded-2xl p-6 space-y-5" autocomplete="off">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="last_name" :value="'Фамилия *'" />
                    <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required autofocus />
                    <x-input-error :messages="$errors->get('last_name')" />
                </div>
                <div>
                    <x-input-label for="first_name" :value="'Имя *'" />
                    <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required />
                    <x-input-error :messages="$errors->get('first_name')" />
                </div>
                <div>
                    <x-input-label for="middle_name" :value="'Отчество'" />
                    <x-text-input id="middle_name" type="text" name="middle_name" :value="old('middle_name')" />
                    <x-input-error :messages="$errors->get('middle_name')" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="birth_date" :value="'Дата рождения'" />
                    <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date')" />
                    <x-input-error :messages="$errors->get('birth_date')" />
                </div>
                <div>
                    <x-input-label for="phone" :value="'Телефон'" />
                    <x-text-input id="phone" type="tel" name="phone" :value="old('phone')" maxlength="32" placeholder="+7…" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <div>
                    <x-input-label for="role" :value="'Уровень доступов *'" />
                    <select id="role" name="role" required
                            class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}" @selected(old('role', 'driver') === $r->value)>
                                {{ $r->label() }} ({{ $r->value }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" />
                </div>
            </div>

            <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Паспорт</legend>
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
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                    <div>
                        <x-input-label for="login" :value="'Логин *'" />
                        <x-text-input id="login" type="text" name="login" :value="old('login')" required autocomplete="off" />
                        <x-input-error :messages="$errors->get('login')" />
                    </div>
                    <div>
                        <x-input-label for="password" :value="'Пароль *'" />
                        <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="'Повторите пароль *'" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                    </div>
                </div>
            </fieldset>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-ghost">Отмена</a>
                <x-primary-button>Создать</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
