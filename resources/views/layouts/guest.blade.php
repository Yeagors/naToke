<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a0b16">

        <title>{{ config('app.name', 'naToke') }} — @yield('title', 'Вход')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-ink-100 min-h-screen">
        <div class="relative min-h-screen flex flex-col">

            {{-- Decorative orbs --}}
            <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
                <div class="absolute -top-32 left-1/4 w-[480px] h-[480px] rounded-full bg-neon-violet/25 blur-3xl animate-pulse-glow"></div>
                <div class="absolute top-1/2 right-1/4 w-[520px] h-[520px] rounded-full bg-neon-cyan/20 blur-3xl animate-pulse-glow" style="animation-delay: 1.5s"></div>
                <div class="absolute -bottom-40 left-0 w-[420px] h-[420px] rounded-full bg-neon-pink/15 blur-3xl animate-pulse-glow" style="animation-delay: 2.8s"></div>
            </div>

            <div class="relative z-10 flex-1 flex items-center justify-center px-4 py-10">
                <div class="w-full max-w-lg">
                    <div class="flex flex-col items-center mb-8">
                        <a href="/" class="group inline-flex items-center gap-3">
                            <svg class="w-12 h-12" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="lg" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                                        <stop offset="0" stop-color="#00e5ff"/>
                                        <stop offset="0.6" stop-color="#a855f7"/>
                                        <stop offset="1" stop-color="#ec4899"/>
                                    </linearGradient>
                                </defs>
                                <path d="M16 48 L32 16 L48 48 L40 48 L32 32 L24 48 Z" stroke="url(#lg)" stroke-width="3" stroke-linejoin="round" fill="rgba(168,85,247,0.10)"/>
                                <circle cx="32" cy="42" r="3" fill="#00e5ff"/>
                            </svg>
                            <div class="text-3xl font-display font-bold tracking-tight">
                                <span class="text-gradient">naToke</span>
                            </div>
                        </a>
                        <p class="mt-2 text-xs uppercase tracking-[0.32em] text-ink-300">CRM электровелопроката</p>
                    </div>

                    <div class="glass-strong rounded-2xl p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <footer class="relative z-10 border-t border-white/5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-center gap-2 text-xs text-ink-300">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-neon-lime animate-pulse"></span>
                    <span>© {{ date('Y') }} naToke · {{ now()->format('d.m.Y H:i') }} MSK</span>
                </div>
            </footer>
        </div>
    </body>
</html>
