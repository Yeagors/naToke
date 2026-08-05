<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a0b16">

        {{-- PWA --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="naToke">

        <title>{{ config('app.name', 'naToke') }} — @yield('title', 'CRM')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-ink-100 min-h-screen">
        <div class="relative min-h-screen flex flex-col">

            {{-- Decorative orbs --}}
            <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
                <div class="absolute -top-32 -left-40 w-[420px] h-[420px] rounded-full bg-neon-violet/20 blur-3xl animate-pulse-glow"></div>
                <div class="absolute top-1/3 -right-40 w-[480px] h-[480px] rounded-full bg-neon-cyan/15 blur-3xl animate-pulse-glow" style="animation-delay: 1.2s"></div>
                <div class="absolute -bottom-40 left-1/3 w-[520px] h-[520px] rounded-full bg-neon-pink/10 blur-3xl animate-pulse-glow" style="animation-delay: 2.4s"></div>
            </div>

            @include('layouts.navigation')

            @if (isset($header))
                <header class="relative z-10">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="relative z-10 flex-1 pb-12">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            <footer class="relative z-10 border-t border-white/5 mt-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-ink-300">
                    <div class="flex items-center gap-3">
                        <span>© {{ date('Y') }} <span class="text-gradient font-semibold">naToke</span> — CRM проката электровелосипедов</span>
                        <a href="{{ route('offer') }}" class="hover:text-neon-cyan">Оферта</a>
                        <a href="{{ route('contacts') }}" class="hover:text-neon-cyan">Контакты</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-neon-lime animate-pulse"></span>
                        <span>сервер на связи · {{ now()->format('d.m.Y H:i') }} MSK</span>
                    </div>
                </div>
            </footer>
        </div>

        {{-- Toaster: server flash (session('toast')) + client-side window 'notify' events --}}
        <div
            x-data="{
                items: [],
                add(t) {
                    const id = Date.now() + Math.random();
                    this.items.push({ id, type: t.type || 'info', message: t.message || '' });
                    setTimeout(() => this.remove(id), t.timeout || 6000);
                },
                remove(id) { this.items = this.items.filter(i => i.id !== id); }
            }"
            x-init="@if(session('toast')) add({ type: @js(session('toast.type', 'info')), message: @js(session('toast.message')) }) @endif"
            x-on:notify.window="add($event.detail)"
            class="fixed top-20 right-4 z-[100] flex flex-col gap-2 w-80 max-w-[90vw]"
        >
            <template x-for="t in items" :key="t.id">
                <div
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="glass-strong rounded-xl px-4 py-3 text-sm flex items-start gap-3 border shadow-lg"
                    :class="t.type === 'error' ? 'border-neon-red/40 text-neon-red' : (t.type === 'success' ? 'border-neon-lime/40 text-neon-lime' : 'border-white/10 text-ink-100')"
                >
                    <span class="flex-1 leading-snug" x-text="t.message"></span>
                    <button type="button" x-on:click="remove(t.id)" class="opacity-60 hover:opacity-100 leading-none">✕</button>
                </div>
            </template>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
            }
        </script>
    </body>
</html>
