<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a0b16">

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
                    <div>© {{ date('Y') }} <span class="text-gradient font-semibold">naToke</span> — CRM проката электровелосипедов</div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-neon-lime animate-pulse"></span>
                        <span>сервер на связи · {{ now()->format('d.m.Y H:i') }} MSK</span>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
