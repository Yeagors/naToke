@php
    $isPending = $payment->isPending();
    $isConfirmed = $payment->isConfirmed();
@endphp
<x-app-layout>
    @section('title', 'Пополнение #'.$payment->id)

    <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-6">

        <div class="mb-6">
            <a href="{{ route('profile.edit') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                в профиль
            </a>
            <h1 class="text-3xl font-display font-bold tracking-tight">
                Пополнение баланса
            </h1>
            <p class="text-ink-300 text-sm mt-1">
                Запрос #{{ $payment->id }} · создан {{ $payment->created_at->format('d.m.Y H:i') }}
                @if($isFake)
                    · <span class="badge badge-driver">demo</span>
                @endif
            </p>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        <div class="glass rounded-2xl p-6"
             x-data='paymentPoller({
                 statusUrl: @json(route("payments.status", $payment)),
                 initialStatus: @json($payment->status->value),
                 amount: @json((float) $payment->amount)
             })'
             x-init="start()">

            {{-- AMOUNT --}}
            <div class="text-center mb-6">
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300 mb-1">К оплате</div>
                <div class="text-5xl font-display font-bold text-gradient drop-shadow-[0_0_30px_rgba(0,229,255,0.30)]">
                    {{ number_format($payment->payable_amount, 2, '.', ' ') }} <span class="opacity-70 text-3xl">₽</span>
                </div>
                @if($payment->fee_amount > 0)
                    <p class="text-xs text-ink-300 mt-2">
                        На баланс зачислится <span class="text-ink-100 font-semibold">{{ number_format((float) $payment->amount, 2, '.', ' ') }} ₽</span>
                        · комиссия сервиса {{ number_format($payment->fee_amount, 2, '.', ' ') }} ₽
                    </p>
                @endif
            </div>

            {{-- STATUS BADGE --}}
            <div class="flex items-center justify-center gap-2 mb-6">
                <template x-if="status === 'pending'">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 text-neon-cyan">
                        <span class="inline-block w-2 h-2 rounded-full bg-neon-cyan animate-pulse"></span>
                        <span class="text-sm font-medium">Ожидаем оплату…</span>
                    </div>
                </template>
                <template x-if="status === 'confirmed'">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-neon-lime/10 border border-neon-lime/40 text-neon-lime shadow-glow-lime">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm font-semibold">Платёж зачислен!</span>
                    </div>
                </template>
                <template x-if="status === 'failed' || status === 'cancelled' || status === 'refunded'">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-neon-red/10 border border-neon-red/40 text-neon-red">
                        <span class="text-sm font-semibold" x-text="statusLabel"></span>
                    </div>
                </template>
            </div>

            {{-- QR --}}
            @if($isPending && $qrSvg)
                <div x-show="status === 'pending'" class="flex flex-col items-center">
                    <div class="p-4 bg-white rounded-2xl shadow-2xl mb-4 w-72 h-72 flex items-center justify-center overflow-hidden">
                        {!! $qrSvg !!}
                    </div>

                    @if($isFake)
                        <p class="text-center text-xs text-ink-300 mb-3 max-w-md">
                            ⚡ <span class="text-neon-violet font-semibold">Демо-режим</span> · реальный платёж не списывается.
                            Отсканируй QR любым приложением (или нажми кнопку ниже) — статус мгновенно станет «зачислено».
                        </p>
                        <a href="{{ route('payments.fake.confirm', $payment) }}"
                           class="btn btn-primary w-full justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Имитировать оплату
                        </a>
                    @else
                        @if($payment->qr_url)
                            {{-- Primary CTA on mobile: tap to open in installed bank app via system Intent --}}
                            <a href="{{ $payment->qr_url }}" rel="external noopener"
                               class="btn btn-primary w-full justify-center mb-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Оплатить — открыть в банке
                            </a>
                        @endif
                        <p class="text-center text-xs text-ink-300 max-w-md leading-relaxed">
                            На телефоне — жми кнопку выше, откроется ваше банковское приложение.<br>
                            С другого устройства — отсканируйте QR из приложения банка.
                            Зачисление произойдёт автоматически, эта страница обновится сама.
                        </p>
                    @endif
                </div>
            @endif

            {{-- SUCCESS panel --}}
            <div x-show="status === 'confirmed'" x-cloak class="text-center pt-2">
                <div class="inline-flex w-24 h-24 rounded-full bg-neon-lime/15 items-center justify-center mb-4 shadow-glow-lime">
                    <svg class="w-14 h-14 text-neon-lime" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-lg font-display font-semibold text-ink-100">+{{ number_format((float) $payment->amount, 2, '.', ' ') }} ₽ зачислено</p>
                <p class="text-sm text-ink-300 mt-1">Баланс обновится автоматически.</p>
                <div class="flex items-center justify-center gap-2 mt-5">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Вернуться в профиль</a>
                    @if(auth()->user()?->isAdmin() && $payment->isConfirmed())
                        <form method="POST" action="{{ route('payments.refund', $payment) }}"
                              onsubmit="return confirm('Вернуть платёж #{{ $payment->id }}? Сумма спишется с баланса пользователя.')">
                            @csrf
                            <button type="submit" class="btn btn-ghost text-neon-red border-neon-red/30 hover:bg-neon-red/10">Возврат</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- ERROR / REFUND panel --}}
            <div x-show="status === 'failed' || status === 'cancelled'" x-cloak class="text-center pt-2">
                <p class="text-sm text-ink-300 mb-3">Платёж не прошёл. Попробуйте ещё раз.</p>
                <a href="{{ route('profile.edit') }}" class="btn btn-ghost">Назад в профиль</a>
            </div>
            <div x-show="status === 'refunded'" x-cloak class="text-center pt-2">
                <p class="text-sm text-ink-300 mb-3">Платёж возвращён. Сумма списана с баланса.</p>
                <a href="{{ route('profile.edit') }}" class="btn btn-ghost">Назад в профиль</a>
            </div>
        </div>
    </div>

    <script>
    function paymentPoller(cfg) {
        return {
            status: cfg.initialStatus,
            statusLabel: '',
            timer: null,
            async start() {
                if (this.status !== 'pending') return;
                this.timer = setInterval(() => this.tick(), 2500);
                // First check after 1s so UI feels live.
                setTimeout(() => this.tick(), 1000);
            },
            async tick() {
                try {
                    const r = await fetch(cfg.statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                    if (!r.ok) return;
                    const data = await r.json();
                    this.status = data.status;
                    this.statusLabel = data.status_label || '';
                    if (this.status !== 'pending') {
                        clearInterval(this.timer);
                        // Subtle reload after confirmation to show updated balance everywhere.
                        if (this.status === 'confirmed') {
                            setTimeout(() => { location.reload(); }, 1800);
                        } else {
                            // failed / cancelled / refunded → surface a toast
                            window.dispatchEvent(new CustomEvent('notify', {
                                detail: { type: 'error', message: 'Платёж не прошёл: ' + (this.statusLabel || 'ошибка') }
                            }));
                        }
                    }
                } catch (e) {
                    // ignore network blips; will retry next tick
                }
            }
        }
    }
    </script>
</x-app-layout>
