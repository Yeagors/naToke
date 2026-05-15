<x-app-layout>
    @section('title', 'Статистика')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Статистика</h1>
                <p class="text-ink-300 text-sm mt-1">
                    Период: <span class="text-neon-cyan font-semibold">{{ $monthStart->translatedFormat('F Y') }}</span>
                    · сегодня {{ $now->format('d.m.Y H:i') }} MSK
                </p>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            {{-- Receivable --}}
            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">Дебиторская задолженность</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-red/10 text-neon-red">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold text-neon-red drop-shadow-[0_0_20px_rgba(255,59,107,0.35)]">
                    {{ number_format($receivable, 2, '.', ' ') }} <span class="text-neon-red/70 text-xl">₽</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    @if($debtorsCount > 0)
                        Должников: {{ $debtorsCount }}
                    @else
                        Без должников 🎉
                    @endif
                </div>
            </div>

            {{-- Revenue MTD --}}
            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">Выручка с начала месяца</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-lime/10 text-neon-lime">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold text-neon-lime drop-shadow-[0_0_20px_rgba(194,255,69,0.35)]">
                    {{ number_format($totalRevenue, 2, '.', ' ') }} <span class="text-neon-lime/70 text-xl">₽</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    @if($bestDay && (float) $bestDay['amount'] > 0)
                        Лучший день: {{ $bestDay['label'] }} — {{ number_format((float) $bestDay['amount'], 2, '.', ' ') }} ₽
                    @else
                        Пока без операций
                    @endif
                </div>
            </div>

            {{-- Rented now --}}
            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">В аренде сейчас</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-cyan/10 text-neon-cyan">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold">
                    {{ $rentedNow }}<span class="text-ink-300 text-2xl"> / {{ $totalCars }}</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    @if($totalCars > 0)
                        утилизация: <span class="text-neon-cyan font-semibold">{{ round($rentedNow / $totalCars * 100) }}%</span>
                    @else
                        нет авто в парке
                    @endif
                </div>
            </div>

            {{-- Idle now --}}
            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">В простое сейчас</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-white/5 text-ink-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold text-ink-100">
                    {{ $idleNow }}<span class="text-ink-300 text-2xl"> / {{ $totalCars }}</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    @if($totalCars > 0 && $idleNow > 0)
                        простаивает <span class="text-neon-amber font-semibold">{{ round($idleNow / $totalCars * 100) }}%</span> парка
                    @else
                        весь парк работает
                    @endif
                </div>
            </div>
        </div>

        {{-- Revenue per day chart --}}
        <section class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Выручка по дням</h2>
                    <p class="text-xs text-ink-300">Сумма приходов на балансы авто, связанных с арендой</p>
                </div>
                <div class="text-sm font-mono text-neon-lime">
                    Итого: {{ number_format($totalRevenue, 2, '.', ' ') }} ₽
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        {{-- Cars utilization (rented vs idle) per day --}}
        <section class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Авто: аренда / простой по дням</h2>
                    <p class="text-xs text-ink-300">
                        Стек: бирюзовый — было в аренде, серый — простой. Сумма равна {{ $totalCars }} (всего авто в парке).
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-sm bg-neon-cyan"></span>
                        <span class="text-ink-200">в аренде</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-sm bg-white/15"></span>
                        <span class="text-ink-200">простой</span>
                    </div>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="carsChart"></canvas>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js" defer></script>
    <script>
    (function () {
        const revenueRows = @json($revenueByDay);
        const carsRows = @json($daysSeries);
        const totalCars = @json($totalCars);

        const dark = {
            grid: 'rgba(255,255,255,0.06)',
            tick: '#a4a8c4',
            tooltipBg: 'rgba(10,11,22,0.95)',
            tooltipBorder: 'rgba(255,255,255,0.10)',
            font: { family: 'Inter, sans-serif', size: 11 },
        };

        function moneyFmt(v) {
            return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) + ' ₽';
        }

        function start() {
            if (typeof Chart === 'undefined') {
                // Wait for Chart.js to load
                return setTimeout(start, 50);
            }

            // ---- Revenue per day ---------------------------------------------
            new Chart(document.getElementById('revenueChart'), {
                type: 'bar',
                data: {
                    labels: revenueRows.map(r => r.label),
                    datasets: [{
                        label: 'Выручка',
                        data: revenueRows.map(r => r.amount),
                        backgroundColor: revenueRows.map(r => r.is_past
                            ? (r.is_today ? '#a855f7' : '#c2ff45')
                            : 'rgba(194,255,69,0.10)'),
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 36,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: dark.tooltipBg,
                            borderColor: dark.tooltipBorder,
                            borderWidth: 1,
                            titleColor: '#e8e8f5',
                            bodyColor: '#c2ff45',
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => moneyFmt(ctx.parsed.y),
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: dark.grid, drawBorder: false },
                            ticks: { color: dark.tick, font: dark.font },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: dark.grid, drawBorder: false },
                            ticks: {
                                color: dark.tick,
                                font: dark.font,
                                callback: (v) => v >= 1000 ? (v/1000).toFixed(0)+'k' : v,
                            },
                        }
                    }
                }
            });

            // ---- Cars: rented vs idle (stacked) ------------------------------
            new Chart(document.getElementById('carsChart'), {
                type: 'bar',
                data: {
                    labels: carsRows.map(r => r.label),
                    datasets: [
                        {
                            label: 'В аренде',
                            data: carsRows.map(r => r.rented),
                            backgroundColor: '#00e5ff',
                            stack: 'cars',
                            borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                            maxBarThickness: 36,
                        },
                        {
                            label: 'Простой',
                            data: carsRows.map(r => r.idle),
                            backgroundColor: 'rgba(255,255,255,0.15)',
                            stack: 'cars',
                            borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                            maxBarThickness: 36,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: dark.tooltipBg,
                            borderColor: dark.tooltipBorder,
                            borderWidth: 1,
                            titleColor: '#e8e8f5',
                            bodyColor: '#e8e8f5',
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y}`,
                            }
                        }
                    },
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { color: dark.grid, drawBorder: false },
                            ticks: { color: dark.tick, font: dark.font },
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            suggestedMax: totalCars > 0 ? totalCars : 1,
                            grid: { color: dark.grid, drawBorder: false },
                            ticks: {
                                color: dark.tick,
                                font: dark.font,
                                stepSize: 1,
                                precision: 0,
                            },
                        }
                    }
                }
            });
        }
        start();
    })();
    </script>
</x-app-layout>
