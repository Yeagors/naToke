@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin();
@endphp
<nav x-data="{ open: false }" class="relative z-20 border-b border-white/5 backdrop-blur-xl bg-ink-950/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            {{-- Logo + primary nav --}}
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5">
                    <svg class="w-8 h-8" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="lgNav" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#00e5ff"/>
                                <stop offset="0.6" stop-color="#a855f7"/>
                                <stop offset="1" stop-color="#ec4899"/>
                            </linearGradient>
                        </defs>
                        <path d="M16 48 L32 16 L48 48 L40 48 L32 32 L24 48 Z" stroke="url(#lgNav)" stroke-width="3" stroke-linejoin="round" fill="rgba(168,85,247,0.10)"/>
                        <circle cx="32" cy="42" r="3" fill="#00e5ff"/>
                    </svg>
                    <span class="font-display font-bold text-lg tracking-tight"><span class="text-gradient">naToke</span></span>
                </a>

                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                        Дашборд
                    </a>

                    @if($isAdmin)
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Пользователи
                        </a>
                        <a href="{{ route('cars.index') }}" class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2zm1-4a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                            Авто
                        </a>
                        <a href="{{ route('rentals.index') }}" class="nav-link {{ request()->routeIs('rentals.*') ? 'active' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Аренды
                        </a>
                        <a href="{{ route('tariffs.index') }}" class="nav-link {{ request()->routeIs('tariffs.*') ? 'active' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10v10H7zM3 3l4 4M21 3l-4 4M3 21l4-4M21 21l-4-4"/></svg>
                            Тарифы
                        </a>
                        <a href="{{ route('logs.index') }}" class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Логи
                        </a>
                    @endif
                </div>
            </div>

            {{-- Right side --}}
            <div class="hidden md:flex items-center gap-3">
                {{-- Balance pill --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 shadow-glow-cyan">
                    <span class="inline-block w-2 h-2 rounded-full bg-neon-lime animate-pulse"></span>
                    <span class="text-xs uppercase tracking-wider text-ink-300">Баланс</span>
                    <span class="font-mono font-semibold text-neon-lime">{{ number_format((float) $user->balance, 2, '.', ' ') }} ₽</span>
                </div>

                {{-- User dropdown --}}
                <div x-data="{ menu: false }" class="relative">
                    <button @click="menu = !menu" @click.outside="menu = false" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white/5 border border-white/10 hover:border-neon-cyan/40 transition">
                        @if($user->photo)
                            <img src="{{ $user->photo_url }}" class="w-7 h-7 rounded-full object-cover ring-1 ring-white/10" alt="">
                        @else
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                                 style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                {{ mb_substr($user->first_name, 0, 1) }}{{ mb_substr($user->last_name, 0, 1) }}
                            </div>
                        @endif
                        <span class="text-sm font-medium">{{ $user->short_name }}</span>
                        <svg class="w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="menu" x-transition x-cloak class="absolute right-0 mt-2 w-56 rounded-xl glass-strong overflow-hidden">
                        <div class="px-4 py-3 border-b border-white/5">
                            <div class="text-sm font-semibold text-ink-100">{{ $user->full_name }}</div>
                            <div class="text-xs text-ink-300">{{ '@'.$user->login }}</div>
                            <div class="mt-1">
                                @if($isAdmin)
                                    <span class="badge badge-admin">admin</span>
                                @else
                                    <span class="badge badge-driver">driver</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink-100 hover:bg-white/5 hover:text-neon-cyan transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Профиль
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-ink-100 hover:bg-white/5 hover:text-neon-red transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile burger --}}
            <button @click="open = !open" class="md:hidden p-2 rounded-lg text-ink-100 hover:bg-white/5">
                <svg x-show="!open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-transition x-cloak class="md:hidden pb-4">
            <div class="flex flex-col gap-1">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Дашборд</a>
                @if($isAdmin)
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Пользователи</a>
                    <a href="{{ route('cars.index') }}" class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}">Авто</a>
                    <a href="{{ route('rentals.index') }}" class="nav-link {{ request()->routeIs('rentals.*') ? 'active' : '' }}">Аренды</a>
                    <a href="{{ route('tariffs.index') }}" class="nav-link {{ request()->routeIs('tariffs.*') ? 'active' : '' }}">Тарифы</a>
                    <a href="{{ route('logs.index') }}" class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">Логи</a>
                @endif
                <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">Профиль</a>
                <div class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-xs uppercase tracking-wider text-ink-300">Баланс</span>
                    <span class="ml-auto font-mono font-semibold text-neon-lime">{{ number_format((float) $user->balance, 2, '.', ' ') }} ₽</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full text-left nav-link hover:text-neon-red">Выйти</button>
                </form>
            </div>
        </div>
    </div>
</nav>
