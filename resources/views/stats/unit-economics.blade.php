@php
    // Build chart data here so the script block stays clean.
    $netByCarChart = $perCar->take(20)->map(function ($r) {
        return [
            'label' => $r['car']->display_name,
            'plate' => $r['car']->license_plate,
            'net' => $r['net'],
            'income' => $r['income'],
            'expense' => $r['expense'],
        ];
    })->values()->all();
@endphp
<x-app-layout>
    @section('title', 'Юнит-экономика')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">

        <div class="mb-6">
            <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
            <h1 class="text-3xl font-display font-bold tracking-tight">Статистика</h1>
            <p class="text-ink-300 text-sm mt-1">Юнит-экономика парка · обновлено {{ $now->format('d.m.Y H:i') }} MSK</p>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap items-center gap-2 mb-6 border-b border-white/5">
            <a href="{{ route('stats.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-ink-300 hover:text-ink-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Общее
            </a>
            <a href="{{ route('stats.unit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 border-neon-cyan text-neon-cyan">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Юнит-экономика
            </a>
        </div>

        {{-- ---- KPI cards ---- --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">Инвестиции в парк</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-violet/10 text-neon-violet">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold text-neon-violet">
                    {{ number_format((float) $kpi['investment'], 0, '.', ' ') }} <span class="opacity-70 text-xl">₽</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    у {{ $kpi['cars_with_purchase'] }} авто известна стоимость закупа
                </div>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">Чистая прибыль парка</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $kpi['net'] >= 0 ? 'bg-neon-lime/10 text-neon-lime' : 'bg-neon-red/10 text-neon-red' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8m9 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold {{ $kpi['net'] >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                    {{ $kpi['net'] >= 0 ? '+' : '' }}{{ number_format((float) $kpi['net'], 0, '.', ' ') }} <span class="opacity-70 text-xl">₽</span>
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    +{{ number_format((float) $kpi['income'], 0, '.', ' ') }} ₽ доход · −{{ number_format((float) $kpi['expense'], 0, '.', ' ') }} ₽ расход
                </div>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">ROI парка</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-cyan/10 text-neon-cyan">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold text-neon-cyan">
                    @if($kpi['fleet_roi_pct'] !== null)
                        {{ $kpi['fleet_roi_pct'] > 0 ? '+' : '' }}{{ $kpi['fleet_roi_pct'] }}<span class="opacity-70 text-xl">%</span>
                    @else
                        <span class="text-ink-400 text-2xl">—</span>
                    @endif
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    окупилось: <span class="text-neon-lime font-semibold">{{ $kpi['paid_back_cars'] }}</span>
                    / {{ $kpi['cars_with_purchase'] }} авто
                </div>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div class="stat-label">Активные аренды</div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-neon-pink/10 text-neon-pink">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-display font-bold">
                    {{ $kpi['open_rentals'] }}
                </div>
                <div class="text-xs text-ink-300 mt-1">
                    водителей в аренде: {{ $kpi['active_drivers'] }}
                    @if($kpi['avg_rental_days'] !== null)
                        · ср. длина {{ $kpi['avg_rental_days'] }} дн.
                    @endif
                </div>
            </div>
        </div>

        {{-- ---- Cashflow chart (60 days) ---- --}}
        <section class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Денежный поток парка (60 дней)</h2>
                    <p class="text-xs text-ink-300">Зелёные столбцы — приходы (аренда + ручные доходы), красные — расходы (ремонт, ТО). Синяя линия — накопленная чистая прибыль.</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-neon-lime"></span><span>доход</span></div>
                    <div class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm bg-neon-red"></span><span>расход</span></div>
                    <div class="flex items-center gap-1.5"><span class="inline-block w-4 h-0.5 bg-neon-cyan"></span><span>накопл.</span></div>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="cashflowChart"></canvas>
            </div>
        </section>

        {{-- ---- Per-car table ---- --}}
        <section class="glass rounded-2xl overflow-hidden mb-6">
            <div class="px-6 pt-6 pb-4 flex items-center justify-between flex-wrap gap-2 border-b border-white/5">
                <div>
                    <h2 class="text-lg font-display font-bold">Каждое авто — отдельно</h2>
                    <p class="text-xs text-ink-300">Отсортировано по чистой прибыли. Клик по строке — профиль авто с деталями.</p>
                </div>
                <div class="text-xs text-ink-300">{{ $perCar->count() }} авто в парке</div>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Авто</th>
                            <th class="text-right whitespace-nowrap">Закуп</th>
                            <th class="text-right whitespace-nowrap">Доход</th>
                            <th class="text-right whitespace-nowrap">Расход</th>
                            <th class="text-right whitespace-nowrap">Чистая</th>
                            <th class="text-right whitespace-nowrap">ROI</th>
                            <th>Окупаемость</th>
                            <th class="text-right whitespace-nowrap">Загр. 30д</th>
                            <th class="text-right whitespace-nowrap">Ср. ₽/день</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($perCar as $row)
                            @php $c = $row['car']; @endphp
                            <tr onclick="location.href='{{ route('cars.show', $c) }}'" class="cursor-pointer group">
                                <td class="font-mono text-xs text-ink-300">{{ $c->id }}</td>
                                <td>
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if($c->photo)
                                            <img src="{{ $c->photo_url }}" class="w-12 h-9 rounded-md object-cover ring-1 ring-white/10" alt="">
                                        @else
                                            <div class="w-12 h-9 rounded-md flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                                <svg class="w-5 h-5 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-medium truncate group-hover:text-neon-cyan transition">{{ $c->display_name }}</div>
                                            <div class="text-xs text-ink-300 font-mono">
                                                {{ $c->license_plate }}
                                                @if($row['in_use_now']) · <span class="text-neon-lime">в аренде</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right font-mono text-sm">
                                    @if($row['purchase'] !== null)
                                        {{ number_format($row['purchase'], 0, '.', ' ') }} ₽
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-semibold text-neon-lime whitespace-nowrap">+{{ number_format($row['income'], 0, '.', ' ') }} ₽</td>
                                <td class="text-right font-mono font-semibold text-neon-red whitespace-nowrap">−{{ number_format($row['expense'], 0, '.', ' ') }} ₽</td>
                                <td class="text-right font-mono font-bold whitespace-nowrap {{ $row['net'] >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                                    {{ $row['net'] >= 0 ? '+' : '−' }}{{ number_format(abs($row['net']), 0, '.', ' ') }} ₽
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @if($row['roi_pct'] !== null)
                                        <span class="font-mono font-bold {{ $row['roi_pct'] >= 100 ? 'text-neon-lime' : ($row['roi_pct'] >= 0 ? 'text-neon-cyan' : 'text-neon-red') }}">
                                            {{ $row['roi_pct'] > 0 ? '+' : '' }}{{ $row['roi_pct'] }}%
                                        </span>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="min-w-[140px]">
                                    @if($row['payback_pct'] !== null)
                                        <div class="h-2 bg-white/5 rounded-full overflow-hidden mb-1">
                                            <div class="h-full {{ $row['payback_pct'] >= 100 ? 'bg-neon-lime' : 'bg-gradient-to-r from-neon-cyan to-neon-violet' }}" style="width: {{ $row['payback_pct'] }}%"></div>
                                        </div>
                                        <div class="text-[10px] text-ink-300">
                                            {{ $row['is_paid_back'] ? '✓ окупилось' : 'до выкупа ещё ' . number_format(max(0, $row['purchase'] - $row['net']), 0, '.', ' ') . ' ₽' }}
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-400">укажите стоимость</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <span class="text-sm font-mono {{ $row['util_30_pct'] >= 50 ? 'text-neon-lime' : 'text-ink-200' }}">{{ $row['util_30_pct'] }}%</span>
                                    <div class="text-[10px] text-ink-300">{{ $row['active_days_30'] }}/30 дней</div>
                                </td>
                                <td class="text-right font-mono text-sm whitespace-nowrap">
                                    {{ number_format($row['daily_avg_net'], 0, '.', ' ') }} ₽
                                    <div class="text-[10px] text-ink-300">{{ $row['days_in_fleet'] }} дн. в парке</div>
                                </td>
                                <td class="text-right pr-4">
                                    <svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center py-10 text-ink-300">В парке нет ни одного авто.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ---- Net profit per car chart ---- --}}
        @if($perCar->isNotEmpty())
            <section class="glass rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="text-lg font-display font-bold">Чистая прибыль по каждому авто</h2>
                        <p class="text-xs text-ink-300">Доход минус расходы за всю историю авто.</p>
                    </div>
                </div>
                <div class="relative h-80">
                    <canvas id="netByCarChart"></canvas>
                </div>
            </section>
        @endif

        {{-- ---- Useful metrics block (advice) ---- --}}
        <section class="glass rounded-2xl p-6 mb-12">
            <h2 class="text-lg font-display font-bold mb-3">📊 Дополнительные метрики</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                    <div class="stat-label mb-1">Активные водители</div>
                    <div class="text-2xl font-display font-bold text-neon-cyan">{{ $kpi['active_drivers'] }}</div>
                    <div class="text-xs text-ink-300 mt-1">арендуют прямо сейчас</div>
                </div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                    <div class="stat-label mb-1">Ср. длина аренды</div>
                    <div class="text-2xl font-display font-bold text-neon-violet">
                        @if($kpi['avg_rental_days'] !== null)
                            {{ $kpi['avg_rental_days'] }} <span class="text-base text-ink-300">дн.</span>
                        @else
                            <span class="text-ink-400 text-base">пока нет закрытых аренд</span>
                        @endif
                    </div>
                    <div class="text-xs text-ink-300 mt-1">по закрытым арендам</div>
                </div>
                <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                    <div class="stat-label mb-1">Ср. баланс водителя</div>
                    <div class="text-2xl font-display font-bold {{ $kpi['avg_user_balance'] >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                        {{ $kpi['avg_user_balance'] >= 0 ? '+' : '' }}{{ number_format($kpi['avg_user_balance'], 0, '.', ' ') }} ₽
                    </div>
                    <div class="text-xs text-ink-300 mt-1">только водители (без админов)</div>
                </div>
            </div>

            <details class="mt-4 group">
                <summary class="cursor-pointer text-sm text-neon-cyan flex items-center gap-2 select-none">
                    <svg class="w-3 h-3 transition group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    Что я могу добавить (если интересно)
                </summary>
                <div class="mt-3 space-y-2 text-xs text-ink-200">
                    <div>• <b>Когорты водителей</b> — кто пришёл в каком месяце, сколько в среднем потратил, retention</div>
                    <div>• <b>CAC / LTV</b> — стоимость привлечения водителя vs его жизненный доход для парка</div>
                    <div>• <b>Прогноз окупаемости</b> — линейный тренд от текущего темпа: когда конкретное авто закроет покупку</div>
                    <div>• <b>Карта ремонтов</b> — частота expense-операций по авто, средний межремонтный интервал</div>
                    <div>• <b>Сезонность</b> — DoW/час, когда выручка максимальна (для планирования зарядки/ТО в простой)</div>
                    <div>• <b>Финансовый отчёт за месяц</b> — экспорт PDF/XLSX с детализацией</div>
                </div>
            </details>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js" defer></script>
    <script>
    (function () {
        const cashflow = @json($cashflow);
        const cars = @json($netByCarChart);

        const dark = {
            grid: 'rgba(255,255,255,0.06)',
            tick: '#a4a8c4',
            tooltipBg: 'rgba(10,11,22,0.95)',
            tooltipBorder: 'rgba(255,255,255,0.10)',
            font: { family: 'Inter, sans-serif', size: 11 },
        };
        const fmt = (v) => new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(v) + ' ₽';

        function ready(fn) {
            if (typeof Chart === 'undefined') return setTimeout(() => ready(fn), 60);
            fn();
        }

        ready(() => {
            // Cashflow chart — income/expense bars + cumulative line
            new Chart(document.getElementById('cashflowChart'), {
                data: {
                    labels: cashflow.map(r => r.label),
                    datasets: [
                        { type: 'bar', label: 'Доход', data: cashflow.map(r => r.income), backgroundColor: '#c2ff45', borderRadius: 3, stack: 'flow', maxBarThickness: 16 },
                        { type: 'bar', label: 'Расход', data: cashflow.map(r => -r.expense), backgroundColor: '#ff3b6b', borderRadius: 3, stack: 'flow', maxBarThickness: 16 },
                        { type: 'line', label: 'Накопленная чистая', data: cashflow.map(r => r.cumulative), borderColor: '#00e5ff', backgroundColor: 'rgba(0,229,255,.10)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true, yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: dark.tooltipBg, borderColor: dark.tooltipBorder, borderWidth: 1, titleColor: '#e8e8f5', bodyColor: '#e8e8f5',
                            callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmt(Math.abs(ctx.parsed.y))}` } }
                    },
                    scales: {
                        x: { grid: { color: dark.grid }, ticks: { color: dark.tick, font: dark.font, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
                        y: { stacked: true, grid: { color: dark.grid }, ticks: { color: dark.tick, font: dark.font, callback: v => fmt(v) } },
                        y1: { position: 'right', grid: { display: false }, ticks: { color: '#00e5ff', font: dark.font, callback: v => fmt(v) } }
                    }
                }
            });

            // Net profit per car
            if (cars.length > 0) {
                new Chart(document.getElementById('netByCarChart'), {
                    type: 'bar',
                    data: {
                        labels: cars.map(c => c.label + ' / ' + c.plate),
                        datasets: [{
                            label: 'Чистая прибыль',
                            data: cars.map(c => c.net),
                            backgroundColor: cars.map(c => c.net >= 0 ? '#c2ff45' : '#ff3b6b'),
                            borderRadius: 4,
                            maxBarThickness: 32,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: dark.tooltipBg, borderColor: dark.tooltipBorder, borderWidth: 1, titleColor: '#e8e8f5', bodyColor: '#e8e8f5',
                                callbacks: { label: (ctx) => fmt(ctx.parsed.x) } }
                        },
                        scales: {
                            x: { grid: { color: dark.grid }, ticks: { color: dark.tick, font: dark.font, callback: v => fmt(v) } },
                            y: { grid: { display: false }, ticks: { color: dark.tick, font: dark.font } }
                        }
                    }
                });
            }
        });
    })();
    </script>
</x-app-layout>
