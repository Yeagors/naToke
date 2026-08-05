@php
    $L = config('legal');
    $v = function ($key) use ($L) {
        $val = trim((string) ($L[$key] ?? ''));
        return $val === ''
            ? '<span class="text-neon-red/80 font-mono">__________</span>'
            : e($val);
    };
    $isOoo     = ($L['legal_form'] ?? 'ip') === 'ooo';
    $ogrnLabel = $isOoo ? 'ОГРН' : 'ОГРНИП';
    $brand     = $L['brand'] ?? 'naToke';
    $backUrl   = auth()->check() ? route('profile.edit') : route('login');
@endphp
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0b16">
    <title>{{ $brand }} — Контакты и реквизиты</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-ink-100 min-h-screen">
    <div class="relative min-h-screen flex flex-col">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-32 -left-40 w-[420px] h-[420px] rounded-full bg-neon-violet/20 blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 w-[480px] h-[480px] rounded-full bg-neon-cyan/15 blur-3xl"></div>
        </div>

        <main class="relative z-10 flex-1">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

                <a href="{{ $backUrl }}" class="text-sm text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-6">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ auth()->check() ? 'в профиль' : 'на вход' }}
                </a>

                <div class="mb-6">
                    <div class="text-xs uppercase tracking-[0.32em] text-ink-300">О компании</div>
                    <h1 class="text-3xl font-display font-bold tracking-tight mt-1">Контакты и реквизиты</h1>
                    <p class="text-ink-300 text-sm mt-2">Сервис проката электровелосипедов «{{ $brand }}».</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="glass rounded-2xl p-6 space-y-2 text-sm">
                        <h2 class="font-display font-bold text-base mb-2">Контакты</h2>
                        <div><span class="text-ink-300">Наименование:</span><br>{!! $v('seller_name') !!}</div>
                        <div><span class="text-ink-300">Адрес:</span><br>{!! $v('legal_address') !!}</div>
                        <div><span class="text-ink-300">Режим работы:</span><br>{!! $v('work_hours') !!}</div>
                        <div><span class="text-ink-300">Телефон:</span> {!! $v('phone') !!}</div>
                        <div><span class="text-ink-300">E-mail:</span> {!! $v('email') !!}</div>
                    </div>

                    <div class="glass rounded-2xl p-6 space-y-2 text-sm">
                        <h2 class="font-display font-bold text-base mb-2">Реквизиты</h2>
                        <div><span class="text-ink-300">ИНН:</span> {!! $v('inn') !!}</div>
                        <div><span class="text-ink-300">{{ $ogrnLabel }}:</span> {!! $v('ogrn') !!}</div>
                        @if($isOoo)
                            <div><span class="text-ink-300">КПП:</span> {!! $v('kpp') !!}</div>
                        @endif
                        <div class="pt-2 mt-2 border-t border-white/10"><span class="text-ink-300">Банк:</span> {!! $v('bank_name') !!}</div>
                        <div><span class="text-ink-300">Расчётный счёт:</span> {!! $v('bank_account') !!}</div>
                        <div><span class="text-ink-300">БИК:</span> {!! $v('bank_bik') !!}</div>
                        <div><span class="text-ink-300">Корр. счёт:</span> {!! $v('bank_corr') !!}</div>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 mt-4 text-sm">
                    <h2 class="font-display font-bold text-base mb-2">Возврат средств</h2>
                    <p class="text-ink-100/85 leading-relaxed">
                        Для возврата неиспользованных средств направьте заявление на e-mail {!! $v('email') !!}
                        с указанием ФИО, номера/даты платежа, суммы и реквизитов для перечисления.
                        Порядок и сроки возврата описаны в разделе 6
                        <a href="{{ route('offer') }}" class="text-neon-cyan hover:underline">публичной оферты</a>.
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
