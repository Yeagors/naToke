<x-app-layout>
    @section('title', 'Дашборд')

    @php $user = auth()->user(); $isAdmin = $user->isAdmin(); @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Hero --}}
        <section class="relative glass rounded-2xl overflow-hidden mt-6 mb-8">
            <div aria-hidden="true" class="absolute inset-0 grid-bg opacity-40"></div>
            <div class="relative p-6 sm:p-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="text-xs uppercase tracking-[0.32em] text-ink-300 mb-2">Добро пожаловать</div>
                    <h1 class="text-3xl sm:text-4xl font-display font-bold tracking-tight">
                        Привет, <span class="text-gradient">{{ $user->first_name ?: $user->login }}</span> 👋
                    </h1>
                    <p class="text-ink-200 mt-2 max-w-xl">Это <span class="text-neon-cyan">naToke</span> — CRM для проката электровелосипедов. Управляйте парком, пользователями и балансом из одного места.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="glass-strong rounded-2xl px-5 py-4 min-w-[200px]">
                        <div class="stat-label">Ваш баланс</div>
                        <div class="mt-1 text-3xl font-display font-bold text-neon-lime drop-shadow-[0_0_20px_rgba(194,255,69,0.35)]">
                            {{ number_format((float) $user->balance, 2, '.', ' ') }} <span class="text-neon-lime/70 text-xl">₽</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        @if($isAdmin)
            {{-- Stats grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <a href="{{ route('users.index') }}" class="glass card-lift rounded-2xl p-5 block">
                    <div class="flex items-start justify-between">
                        <div class="stat-label">Пользователи</div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-cyan/10 text-neon-cyan">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-display font-bold">{{ $stats['users_count'] ?? 0 }}</div>
                    <div class="text-xs text-ink-300 mt-1">всего в базе</div>
                </a>

                <a href="{{ route('cars.index') }}" class="glass card-lift rounded-2xl p-5 block">
                    <div class="flex items-start justify-between">
                        <div class="stat-label">Авто</div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-violet/10 text-neon-violet">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2zm1-4a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-display font-bold">{{ $stats['cars_count'] ?? 0 }}</div>
                    <div class="text-xs text-ink-300 mt-1">в парке</div>
                </a>

                <div class="glass rounded-2xl p-5">
                    <div class="flex items-start justify-between">
                        <div class="stat-label">Сумма балансов</div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-lime/10 text-neon-lime">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8m9 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-2xl font-display font-bold text-neon-lime">{{ number_format($stats['total_balance'] ?? 0, 2, '.', ' ') }} ₽</div>
                    <div class="text-xs text-ink-300 mt-1">на счетах пользователей</div>
                </div>

                <div class="glass rounded-2xl p-5">
                    <div class="flex items-start justify-between">
                        <div class="stat-label">Транзакции</div>
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-pink/10 text-neon-pink">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-display font-bold">{{ $stats['transactions_count'] ?? 0 }}</div>
                    <div class="text-xs text-ink-300 mt-1">всего операций</div>
                </div>
            </div>

            {{-- Two columns: recent users + recent transactions --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-display font-semibold">Последние пользователи</h2>
                        <a href="{{ route('users.index') }}" class="text-xs text-neon-cyan hover:underline">все →</a>
                    </div>
                    @forelse($stats['latest_users'] ?? [] as $u)
                        <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0">
                            @if($u->photo)
                                <img src="{{ $u->photo_url }}" class="w-9 h-9 rounded-full object-cover" alt="">
                            @else
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-[12px] font-bold text-white" style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                    {{ mb_substr($u->first_name, 0, 1) }}{{ mb_substr($u->last_name, 0, 1) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate">{{ $u->full_name }}</div>
                                <div class="text-xs text-ink-300 truncate">{{ '@'.$u->login }} · {{ $u->created_at?->format('d.m.Y') }}</div>
                            </div>
                            @if($u->isAdmin())
                                <span class="badge badge-admin">admin</span>
                            @else
                                <span class="badge badge-driver">driver</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-ink-300 py-4">Пока никого нет.</div>
                    @endforelse
                </div>

                <div class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-display font-semibold">Мои недавние транзакции</h2>
                    </div>
                    @forelse($stats['recent_transactions'] as $t)
                        @if($t->rental_id)
                            <a href="{{ route('rentals.show', $t->rental_id) }}" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0 -mx-1 px-1 rounded hover:bg-white/5 transition">
                        @else
                            <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0">
                        @endif
                            @if($t->type->value === 'deposit')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">
                                    {{ $t->created_at->format('d.m.Y H:i') }}
                                    @if($t->rental_id)
                                        · <span class="text-neon-cyan">аренда #{{ $t->rental_id }} →</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'deposit' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        @if($t->rental_id)
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="text-sm text-ink-300 py-4">Транзакций ещё нет.</div>
                    @endforelse
                </div>
            </div>
        @else
            {{-- Driver "app" screen --}}
            @php $rental = $stats['active_rental'] ?? null; @endphp

            {{-- Primary CTA: top up --}}
            <a href="{{ route('profile.edit') }}" class="btn btn-primary w-full justify-center mb-6 py-3.5 text-base">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm12 2h2v2h-2zm-2 2h2v2h-2zm2 0h2v2h-2zm-2-4h2v2h-2zm4-2h2v2h-2zm0 4h2v2h-2z"/></svg>
                Пополнить баланс по QR (СБП)
            </a>

            {{-- My rental --}}
            @if($rental)
                <a href="{{ route('rentals.show', $rental) }}" class="block glass card-lift rounded-2xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-display font-semibold">Моя аренда #{{ $rental->id }}</h2>
                        <span class="badge {{ $rental->status->badgeClass() }}">{{ $rental->status->label() }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="stat-label">Электровелосипед</div>
                            <div class="font-medium mt-0.5">{{ $rental->car?->display_name ?? '—' }}</div>
                            <div class="text-xs text-ink-300">{{ $rental->car?->license_plate }}@if($rental->batteries->isNotEmpty()) · АКБ {{ $rental->batteries->map(fn($b) => $b->callsign ?: $b->vin)->join(', ') }}@endif</div>
                        </div>
                        <div>
                            <div class="stat-label">Списание</div>
                            <div class="font-medium mt-0.5 font-mono text-neon-cyan">{{ number_format((float) $rental->amount, 2, '.', ' ') }} ₽</div>
                            <div class="text-xs text-ink-300">каждые {{ $rental->period_count }} {{ $rental->period->label() }}</div>
                        </div>
                        @if($rental->next_charge_at && $rental->isOpen())
                            <div>
                                <div class="stat-label">Следующее списание</div>
                                <div class="font-medium mt-0.5">{{ $rental->next_charge_at->format('d.m.Y H:i') }}</div>
                                <div class="text-xs text-ink-300">{{ $rental->next_charge_at->diffForHumans() }}</div>
                            </div>
                        @endif
                        @if($rental->is_buyout)
                            <div>
                                <div class="stat-label">Выкуп</div>
                                <div class="font-medium mt-0.5 text-neon-violet">осталось {{ number_format((float) ($rental->buyout_remaining ?? 0), 0, '.', ' ') }} ₽</div>
                                <div class="mt-1 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                    <div class="h-full bg-neon-violet" style="width: {{ $rental->buyout_progress_percent }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </a>
            @else
                <div class="glass rounded-2xl p-6 mb-6 text-center text-ink-300">
                    Активной аренды нет. Обратитесь к администратору, чтобы оформить аренду велосипеда.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6">
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-display font-semibold mb-4">Мои транзакции</h2>
                    @forelse($stats['recent_transactions'] as $t)
                        @if($t->rental_id)
                            <a href="{{ route('rentals.show', $t->rental_id) }}" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0 -mx-1 px-1 rounded hover:bg-white/5 transition">
                        @else
                            <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0">
                        @endif
                            @if($t->type->value === 'deposit')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">
                                    {{ $t->created_at->format('d.m.Y H:i') }}
                                    @if($t->rental_id)
                                        · <span class="text-neon-cyan">аренда #{{ $t->rental_id }} →</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'deposit' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        @if($t->rental_id)
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="text-sm text-ink-300 py-4">Транзакций ещё нет.</div>
                    @endforelse
                </div>

                <div class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-display font-semibold mb-4">Быстрые действия</h2>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('profile.edit') }}" class="btn btn-ghost justify-start">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Перейти в профиль
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
