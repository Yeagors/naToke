@php $canEdit = $user->isAdmin(); @endphp
<section class="glass rounded-2xl p-6">
    <header class="mb-4">
        <h2 class="text-lg font-display font-bold">Личные данные</h2>
        <p class="text-sm text-ink-300">
            @if($canEdit)
                Обновите ФИО, паспортные данные и логин.
            @else
                Только просмотр. Изменения вносит администратор.
            @endif
        </p>
    </header>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PATCH')
        <fieldset @disabled(!$canEdit) class="space-y-5 contents">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <x-input-label for="last_name" :value="'Фамилия'" />
                <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name', $user->last_name)" required :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('last_name')" />
            </div>
            <div>
                <x-input-label for="first_name" :value="'Имя'" />
                <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name', $user->first_name)" required :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="middle_name" :value="'Отчество'" />
                <x-text-input id="middle_name" type="text" name="middle_name" :value="old('middle_name', $user->middle_name)" :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('middle_name')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <x-input-label for="birth_date" :value="'Дата рождения'" />
                <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date', optional($user->birth_date)->format('Y-m-d'))" :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('birth_date')" />
            </div>
            <div>
                <x-input-label for="phone" :value="'Телефон'" />
                <x-text-input id="phone" type="tel" name="phone" :value="old('phone', $user->phone)" maxlength="32" placeholder="+7…" :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <x-input-label for="login" :value="'Логин'" />
                <x-text-input id="login" type="text" name="login" :value="old('login', $user->login)" required :disabled="!$canEdit" />
                <x-input-error :messages="$errors->get('login')" />
            </div>
        </div>

        <fieldset class="rounded-xl border border-white/8 px-4 py-3">
            <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Паспортные данные</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                <div>
                    <x-input-label for="passport_series" :value="'Серия'" />
                    <x-text-input id="passport_series" type="text" name="passport_series" :value="old('passport_series', $user->passport_series)" maxlength="10" :disabled="!$canEdit" />
                    <x-input-error :messages="$errors->get('passport_series')" />
                </div>
                <div>
                    <x-input-label for="passport_number" :value="'Номер'" />
                    <x-text-input id="passport_number" type="text" name="passport_number" :value="old('passport_number', $user->passport_number)" maxlength="20" :disabled="!$canEdit" />
                    <x-input-error :messages="$errors->get('passport_number')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="passport_issued_by" :value="'Кем выдан'" />
                    <x-text-input id="passport_issued_by" type="text" name="passport_issued_by" :value="old('passport_issued_by', $user->passport_issued_by)" :disabled="!$canEdit" />
                    <x-input-error :messages="$errors->get('passport_issued_by')" />
                </div>
                <div>
                    <x-input-label for="passport_issued_at" :value="'Когда выдан'" />
                    <x-text-input id="passport_issued_at" type="date" name="passport_issued_at" :value="old('passport_issued_at', optional($user->passport_issued_at)->format('Y-m-d'))" :disabled="!$canEdit" />
                    <x-input-error :messages="$errors->get('passport_issued_at')" />
                </div>
                <div>
                    <x-input-label for="passport_department_code" :value="'Код подразделения'" />
                    <x-text-input id="passport_department_code" type="text" name="passport_department_code" :value="old('passport_department_code', $user->passport_department_code)" maxlength="10" :disabled="!$canEdit" />
                    <x-input-error :messages="$errors->get('passport_department_code')" />
                </div>
            </div>
        </fieldset>

        </fieldset>

        @if($canEdit)
            <div class="flex justify-end pt-2">
                <x-primary-button>Сохранить изменения</x-primary-button>
            </div>
        @endif
    </form>
</section>
