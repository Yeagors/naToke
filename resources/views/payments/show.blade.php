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
                    {{ number_format((float) $payment->amount, 2, '.', ' ') }} <span class="opacity-70 text-3xl">₽</span>
                </div>
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
                <template x-if="status === 'failed' || status === 'cancelled'">
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
                        <p class="text-center text-xs text-ink-300 mb-3 max-w-md">
                            Отсканируй QR-код в приложении вашего банка и подтверди платёж.
                            Зачисление произойдёт автоматически, эта страница обновится сама.
                        </p>
                        @if($payment->qr_url)
                            <a href="{{ $payment->qr_url }}" target="_blank" rel="noopener"
                               class="btn btn-ghost w-full justify-center">
                                Открыть в банковском приложении
                            </a>
                        @endif
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
                <a href="{{ route('profile.edit') }}" class="btn btn-primary mt-5">
                    Вернуться в профиль
                </a>
            </div>

            {{-- ERROR panel --}}
            <div x-show="status === 'failed' || status === 'cancelled'" x-cloak class="text-center pt-2">
                <p class="text-sm text-ink-300 mb-3">Платёж не прошёл. Попробуйте ещё раз.</p>
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
