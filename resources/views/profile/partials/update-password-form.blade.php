@if($user->isAdmin())
<section class="glass rounded-2xl p-6">
    <header class="mb-4">
        <h2 class="text-lg font-display font-bold">Смена пароля</h2>
        <p class="text-sm text-ink-300">Минимум 6 символов. Используйте длинный пароль.</p>
    </header>

    @if(session('status') === 'password-updated')
        <div class="mb-4 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
            Пароль обновлён.
        </div>
    @endif

    <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="current_password" :value="'Текущий пароль'" />
            <x-text-input id="current_password" type="password" name="current_password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <x-input-label for="password" :value="'Новый пароль'" />
                <x-text-input id="password" type="password" name="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="'Повторите пароль'" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>Сменить пароль</x-primary-button>
        </div>
    </form>
</section>
@else
<section class="glass rounded-2xl p-6">
    <header class="mb-2">
        <h2 class="text-lg font-display font-bold">Смена пароля</h2>
    </header>
    <p class="text-sm text-ink-300">Сменить пароль может администратор. Обратитесь к нему, если нужно обновить учётные данные.</p>
</section>
@endif
