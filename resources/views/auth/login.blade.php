<x-guest-layout>
    @section('title', 'Вход в систему')

    <div class="mb-6">
        <h1 class="text-2xl font-display font-bold tracking-tight">Вход в систему</h1>
        <p class="text-sm text-ink-300 mt-1">Используйте логин и пароль, выданные администратором.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 px-3 py-2 rounded-lg text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="login" :value="'Логин'" />
            <x-text-input id="login" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" />
        </div>

        <div>
            <x-input-label for="password" :value="'Пароль'" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label class="flex items-center gap-2 select-none cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-white/15 bg-ink-800/70 text-neon-cyan focus:ring-neon-cyan/40">
            <span class="text-sm text-ink-200">Запомнить меня</span>
        </label>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center text-base py-3">
                Войти
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </x-primary-button>
        </div>

        <div class="flex items-center gap-2 my-4">
            <div class="flex-1 h-px bg-white/10"></div>
            <span class="text-[10px] uppercase tracking-[0.3em] text-ink-300">или</span>
            <div class="flex-1 h-px bg-white/10"></div>
        </div>

        <a href="{{ route('register') }}" class="btn btn-ghost w-full justify-center">
            Зарегистрироваться
        </a>
    </form>
</x-guest-layout>
