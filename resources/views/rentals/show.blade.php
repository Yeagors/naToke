@php $isAdmin = auth()->user()->isAdmin(); @endphp
<x-app-layout>
    @section('title', 'Аренда #'.$rental->id)

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                @if($isAdmin)
                    <a href="{{ route('cars.show', $rental->car) }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        к авто {{ $rental->car->display_name }}
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        к дашборду
                    </a>
                @endif
                <h1 class="text-3xl font-display font-bold tracking-tight">Аренда #{{ $rental->id }}</h1>
                <p class="text-ink-300 text-sm">создана {{ $rental->created_at->format('d.m.Y H:i') }}@if($rental->creator && $isAdmin) · оформил {{ $rental->creator->short_name }}@endif</p>
            </div>
            <span class="badge {{ $rental->status->badgeClass() }} self-start sm:self-auto">{{ $rental->status->label() }}</span>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-red bg-neon-red/10 border border-neon-red/30">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Action bar (admin only) --}}
        @if($isAdmin)
            <div class="glass rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-2">
                @if($rental->isOpen())
                    <form method="POST" action="{{ route('rentals.pause', $rental) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Приостановить
                        </button>
                    </form>
                @elseif($rental->isPaused())
                    <form method="POST" action="{{ route('rentals.resume', $rental) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            Возобновить
                        </button>
                    </form>
                @endif
                @if(! $rental->isClosed())
                    <form method="POST" action="{{ route('rentals.close', $rental) }}" onsubmit="return confirm('Закрыть аренду навсегда?')" class="ml-auto">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Закрыть аренду
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: info --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                {{-- Car --}}
                <{{ $isAdmin ? 'a' : 'div' }} @if($isAdmin) href="{{ route('cars.show', $rental->car) }}" @endif class="glass {{ $isAdmin ? 'card-lift' : '' }} rounded-2xl p-5 block">
                    <div class="stat-label mb-2">Авто</div>
                    <div class="flex items-center gap-3">
                        @if($rental->car->photo)
                            <img src="{{ $rental->car->photo_url }}" class="w-14 h-10 rounded-md object-cover ring-1 ring-white/10" alt="">
                        @else
                            <div class="w-14 h-10 rounded-md flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                <svg class="w-5 h-5 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div>
                            <div class="font-medium">{{ $rental->car->display_name }}</div>
                            <div class="text-xs text-ink-300 font-mono">{{ $rental->car->license_plate }}</div>
                        </div>
                    </div>
                </{{ $isAdmin ? 'a' : 'div' }}>

                {{-- Renter --}}
                <{{ $isAdmin ? 'a' : 'div' }} @if($isAdmin) href="{{ route('users.show', $rental->user) }}" @endif class="glass {{ $isAdmin ? 'card-lift' : '' }} rounded-2xl p-5 block">
                    <div class="stat-label mb-2">Арендатор</div>
                    <div class="flex items-center gap-3">
                        @if($rental->user->photo)
                            <img src="{{ $rental->user->photo_url }}" class="w-10 h-10 rounded-full object-cover" alt="">
                        @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-[12px] font-bold text-white" style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                {{ mb_substr($rental->user->first_name, 0, 1) }}{{ mb_substr($rental->user->last_name, 0, 1) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ $rental->user->full_name }}</div>
                            <div class="text-xs text-ink-300 truncate">{{ '@'.$rental->user->login }}@if($rental->user->phone) · {{ $rental->user->phone }}@endif</div>
                            <div class="text-xs mt-0.5">Баланс: <span class="font-mono {{ (float) $rental->user->balance >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">{{ number_format((float) $rental->user->balance, 2, '.', ' ') }} ₽</span></div>
                        </div>
                    </div>
                </{{ $isAdmin ? 'a' : 'div' }}>

                {{-- Buyout progress (lease-to-own) --}}
                @if($rental->is_buyout)
                    <div class="glass rounded-2xl p-5 border border-neon-violet/30 shadow-glow-violet">
                        <div class="flex items-center justify-between mb-2">
                            <div class="stat-label">Раскат / выкуп</div>
                            @if($rental->isBuyoutCompleted())
                                <span class="badge badge-deposit">✓ выкуплено</span>
                            @endif
                        </div>
                        <div class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-ink-300">Выкупная стоимость</span><span class="font-mono text-neon-violet">{{ number_format((float) $rental->buyout_price, 2, '.', ' ') }} ₽</span></div>
                            <div class="flex justify-between"><span class="text-ink-300">Выплачено</span><span class="font-mono text-neon-lime">{{ number_format($rental->buyout_paid, 2, '.', ' ') }} ₽</span></div>
                            <div class="flex justify-between"><span class="text-ink-300">Остаток к выплате</span><span class="font-mono">{{ number_format((float) ($rental->buyout_remaining ?? 0), 2, '.', ' ') }} ₽</span></div>
                            <div class="flex justify-between"><span class="text-ink-300">Срок (всего)</span><span>{{ $rental->buyout_days_total ?? 0 }} периодов</span></div>
                            <div class="flex justify-between"><span class="text-ink-300">Осталось периодов</span><span class="font-mono text-neon-cyan">{{ $rental->buyout_days_remaining ?? 0 }}</span></div>
                            @if($rental->buyout_completed_at)
                                <div class="flex justify-between"><span class="text-ink-300">Завершено</span><span class="font-mono text-neon-lime">{{ $rental->buyout_completed_at->format('d.m.Y H:i') }}</span></div>
                            @endif
                        </div>
                        <div class="mt-3">
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-neon-cyan via-neon-violet to-neon-pink transition-all duration-500"
                                     style="width: {{ $rental->buyout_progress_percent }}%"></div>
                            </div>
                            <div class="text-xs text-ink-300 mt-1 text-center">{{ $rental->buyout_progress_percent }}% выплачено</div>
                        </div>
                    </div>
                @endif

                {{-- Tariff snapshot --}}
                <div class="glass rounded-2xl p-5">
                    <div class="stat-label mb-2">Тариф (снапшот)</div>
                    @if($rental->tariff)
                        <div class="font-medium">{{ $rental->tariff->name }}</div>
                    @endif
                    <div class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-ink-300">Сумма списания</span><span class="font-mono">{{ number_format((float) $rental->amount, 2, '.', ' ') }} ₽</span></div>
                        <div class="flex justify-between"><span class="text-ink-300">Период</span><span>{{ $rental->period_count }} {{ $rental->period->label() }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-300">Депозит</span><span class="font-mono">{{ number_format((float) $rental->deposit_amount, 2, '.', ' ') }} ₽</span></div>
                    </div>
                    @if(! empty($rental->extras))
                        <div class="mt-3 pt-3 border-t border-white/5">
                            <div class="text-xs uppercase tracking-wider text-ink-300 mb-1">Доп. транзакции</div>
                            @foreach($rental->extras as $ex)
                                <div class="flex justify-between text-sm">
                                    <span>{{ $ex['label'] ?? '—' }}</span>
                                    <span class="font-mono">{{ number_format((float) ($ex['amount'] ?? 0), 2, '.', ' ') }} ₽</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Timeline --}}
                <div class="glass rounded-2xl p-5">
                    <div class="stat-label mb-3">Хронология</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-ink-300">Старт</span><span class="font-mono text-neon-cyan">{{ $rental->started_at?->format('d.m.Y H:i') }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-300">Последнее списание</span><span class="font-mono">{{ $rental->last_charged_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-300">Следующее списание</span><span class="font-mono text-neon-cyan">{{ $rental->next_charge_at?->format('d.m.Y H:i') ?? '—' }}</span></div>
                        @if($rental->paused_at)
                            <div class="flex justify-between"><span class="text-ink-300">Приостановлена</span><span class="font-mono">{{ $rental->paused_at->format('d.m.Y H:i') }}</span></div>
                        @endif
                        @if($rental->closed_at)
                            <div class="flex justify-between"><span class="text-ink-300">Закрыта</span><span class="font-mono">{{ $rental->closed_at->format('d.m.Y H:i') }}</span></div>
                        @endif
                    </div>
                    @if($rental->comment)
                        <div class="mt-3 pt-3 border-t border-white/5">
                            <div class="text-xs uppercase tracking-wider text-ink-300 mb-1">Комментарий</div>
                            <p class="text-sm">{{ $rental->comment }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: transactions --}}
            <div class="lg:col-span-2 flex flex-col gap-6">

                <section class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-display font-bold mb-4">Транзакции арендатора (по этой аренде)</h2>
                    @forelse($rental->userTransactions as $t)
                        <div class="flex items-center gap-3 py-2.5 border-b border-white/5 last:border-b-0">
                            @if($t->type->value === 'deposit')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">{{ $t->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'deposit' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-ink-300 py-2">По этой аренде ещё не было операций.</div>
                    @endforelse
                </section>

                <section class="glass rounded-2xl p-6">
                    <h2 class="text-lg font-display font-bold mb-4">Транзакции авто (по этой аренде)</h2>
                    @forelse($rental->carTransactions as $t)
                        <div class="flex items-center gap-3 py-2.5 border-b border-white/5 last:border-b-0">
                            @if($t->type->value === 'income')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">{{ $t->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'income' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-ink-300 py-2">По этой аренде ещё не было приходов на авто.</div>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
