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
                                            <div class="font-medium truncate group-hover:text-neon-cyan transition">{{ $c->license_plate }}</div>
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

        {{-- ---- Aux metrics ---- --}}
        <section class="glass rounded-2xl p-6 mb-6">
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
        </section>

        {{-- ---- Payback forecast ---- --}}
        @php $forecastCars = $perCar->filter(fn ($r) => $r['purchase'] !== null && $r['purchase'] > 0); @endphp
        <section class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Прогноз окупаемости</h2>
                    <p class="text-xs text-ink-300">Линейный тренд по среднедневному net за последние 30 дней. Если темп упадёт до нуля — прогноз заморозится.</p>
                </div>
            </div>
            @if($forecastCars->isEmpty())
                <div class="text-sm text-ink-300 py-6 text-center border border-dashed border-white/10 rounded-xl">
                    Чтобы появился прогноз — укажи <span class="text-ink-100 font-medium">стоимость закупа</span> хотя бы у одного авто (профиль авто → блок «Закуп»).
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($forecastCars as $row)
                        @php $c = $row['car']; @endphp
                        <a href="{{ route('cars.show', $c) }}" class="rounded-xl border border-white/10 bg-white/5 p-4 block hover:border-neon-cyan/40 transition">
                            <div class="flex items-center gap-2 mb-3">
                                @if($c->photo)
                                    <img src="{{ $c->photo_url }}" class="w-10 h-7 rounded-md object-cover ring-1 ring-white/10" alt="">
                                @else
                                    <div class="w-10 h-7 rounded-md bg-white/5 ring-1 ring-white/10"></div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium truncate">{{ $c->license_plate }}</div>
                                    <div class="text-xs text-ink-300 font-mono">{{ $c->license_plate }}</div>
                                </div>
                            </div>

                            @if($row['forecast_note'] === 'paid')
                                <div class="flex items-center gap-2 text-neon-lime">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span class="font-semibold">Окупилось</span>
                                </div>
                                <div class="text-xs text-ink-300 mt-1">Net = {{ number_format($row['net'], 0, '.', ' ') }} ₽, в плюсе на {{ number_format($row['net'] - $row['purchase'], 0, '.', ' ') }} ₽</div>
                            @elseif($row['forecast_note'] === 'projected')
                                <div class="text-3xl font-display font-bold text-neon-cyan">
                                    {{ $row['forecast_eta_days'] }} <span class="text-base text-ink-300">дн.</span>
                                </div>
                                <div class="text-xs text-ink-300 mt-1">
                                    окупится к <span class="text-ink-100 font-mono">{{ $row['forecast_eta_date']->locale('ru')->isoFormat('D MMMM YYYY') }}</span>
                                </div>
                                <div class="mt-2 h-1.5 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-neon-cyan to-neon-violet" style="width: {{ $row['payback_pct'] }}%"></div>
                                </div>
                                <div class="text-[10px] text-ink-300 mt-1">
                                    {{ $row['payback_pct'] }}% · темп +{{ number_format($row['recent_daily_net'], 0, '.', ' ') }} ₽/день
                                </div>
                            @elseif($row['forecast_note'] === 'stalled')
                                <div class="text-neon-amber font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Темп нулевой
                                </div>
                                <div class="text-xs text-ink-300 mt-1">За 30 дней расходы ≥ доход. Нужно либо в аренду, либо урезать расходы.</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ---- Cohorts: signup month + retention ---- --}}
        @if(! empty($cohorts['cohorts']))
            <section class="glass rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="text-lg font-display font-bold">Когорты водителей</h2>
                        <p class="text-xs text-ink-300">Группировка по месяцу регистрации. Retention — % водителей когорты с активностью (любая транзакция) в этом месяце-после-регистрации.</p>
                    </div>
                </div>

                <div class="overflow-x-auto -mx-6 px-6 mb-6">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Когорта</th>
                                <th class="text-right">Размер</th>
                                <th class="text-right whitespace-nowrap">Пополнено</th>
                                <th class="text-right whitespace-nowrap">Списано</th>
                                <th class="text-right whitespace-nowrap">ARPU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cohorts['cohorts'] as $c)
                                <tr>
                                    <td><span class="font-medium">{{ $c['label'] }}</span> <span class="text-xs text-ink-300 font-mono ml-1">{{ $c['cohort'] }}</span></td>
                                    <td class="text-right font-mono">{{ $c['size'] }}</td>
                                    <td class="text-right font-mono text-neon-lime">+{{ number_format($c['topup'], 0, '.', ' ') }} ₽</td>
                                    <td class="text-right font-mono text-neon-red">−{{ number_format($c['spend'], 0, '.', ' ') }} ₽</td>
                                    <td class="text-right font-mono text-neon-cyan">{{ number_format($c['arpu'], 0, '.', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h3 class="text-sm font-display font-semibold mb-3 uppercase tracking-wider text-ink-200">Retention</h3>
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="text-xs border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 font-medium text-ink-300">Когорта</th>
                                <th class="text-right px-3 py-2 font-medium text-ink-300">Размер</th>
                                @for($m = 0; $m <= $cohorts['maxMonths']; $m++)
                                    <th class="text-center px-2 py-2 font-medium text-ink-300 min-w-[44px]">M+{{ $m }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cohorts['retention'] as $r)
                                <tr>
                                    <td class="px-3 py-1.5 whitespace-nowrap">{{ $r['label'] }}</td>
                                    <td class="text-right px-3 py-1.5 font-mono">{{ $r['size'] }}</td>
                                    @for($m = 0; $m <= $cohorts['maxMonths']; $m++)
                                        @php
                                            $cell = $r['cells'][$m] ?? null;
                                        @endphp
                                        @if($cell)
                                            @php
                                                $pct = $cell['pct'];
                                                $opacity = max(0.10, $pct / 100);
                                            @endphp
                                            <td class="text-center px-1 py-1.5">
                                                <div class="rounded-md py-1.5 px-2 text-[11px] font-mono font-semibold"
                                                     style="background-color: rgba(0, 229, 255, {{ $opacity }}); color: {{ $pct >= 50 ? '#0a0b16' : '#e8e8f5' }};"
                                                     title="{{ $cell['count'] }} из {{ $r['size'] }}">
                                                    {{ $pct }}%
                                                </div>
                                            </td>
                                        @else
                                            <td class="text-center px-1 py-1.5 text-ink-400">·</td>
                                        @endif
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ---- Repair map ---- --}}
        <section class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Карта ремонтов</h2>
                    <p class="text-xs text-ink-300">Все car_transactions со знаком расхода — сгруппированы по авто. MTBR = средний интервал между ремонтами.</p>
                </div>
            </div>
            @if(empty($repairs))
                <div class="text-sm text-ink-300 py-6 text-center border border-dashed border-white/10 rounded-xl">
                    По авто пока не было ни одного расхода. Добавляй их через профиль авто → «Транзакция авто» → «Расход».
                </div>
            @else
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Авто</th>
                                <th class="text-right">Кол-во ремонтов</th>
                                <th class="text-right whitespace-nowrap">Всего потрачено</th>
                                <th class="text-right whitespace-nowrap">Ср. чек</th>
                                <th class="text-right whitespace-nowrap">MTBR</th>
                                <th class="whitespace-nowrap">Последний ремонт</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repairs as $r)
                                @php $c = $r['car']; @endphp
                                <tr onclick="location.href='{{ route('cars.show', $c) }}'" class="cursor-pointer group">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($c->photo)
                                                <img src="{{ $c->photo_url }}" class="w-10 h-7 rounded-md object-cover ring-1 ring-white/10" alt="">
                                            @else
                                                <div class="w-10 h-7 rounded-md bg-white/5 ring-1 ring-white/10"></div>
                                            @endif
                                            <div>
                                                <div class="font-medium group-hover:text-neon-cyan transition">{{ $c->display_name }}</div>
                                                <div class="text-xs text-ink-300 font-mono">{{ $c->license_plate }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right font-mono">{{ $r['count'] }}</td>
                                    <td class="text-right font-mono font-semibold text-neon-red">−{{ number_format($r['total'], 0, '.', ' ') }} ₽</td>
                                    <td class="text-right font-mono text-ink-200">{{ number_format($r['avg'], 0, '.', ' ') }} ₽</td>
                                    <td class="text-right font-mono">{{ $r['mtbr_days'] !== null ? $r['mtbr_days'].' дн.' : '—' }}</td>
                                    <td class="text-xs text-ink-300 font-mono whitespace-nowrap">{{ $r['last_at']->format('d.m.Y') }} <span class="text-ink-400">({{ $r['last_at']->diffForHumans() }})</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- ---- Seasonality heatmap ---- --}}
        @if($seasonality['max'] > 0)
            <section class="glass rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="text-lg font-display font-bold">Сезонность · день × час</h2>
                        <p class="text-xs text-ink-300">Сумма выручки парка по час-времени и дню недели за последние {{ $seasonality['window_days'] }} дней. Чем темнее cyan — тем больше денег прошло.</p>
                    </div>
                    <div class="text-xs text-ink-300">{{ $seasonality['from'] }} → {{ $seasonality['to'] }}</div>
                </div>

                <div class="overflow-x-auto -mx-2 px-2">
                    <div class="grid gap-0.5" style="grid-template-columns: 40px repeat(24, minmax(24px, 1fr)); min-width: 700px;">
                        {{-- Header: hours --}}
                        <div></div>
                        @for($h = 0; $h < 24; $h++)
                            <div class="text-[10px] text-ink-300 text-center font-mono">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</div>
                        @endfor

                        @foreach($seasonality['days'] as $d => $dayLabel)
                            <div class="text-[11px] text-ink-300 font-semibold flex items-center">{{ $dayLabel }}</div>
                            @for($h = 0; $h < 24; $h++)
                                @php
                                    $cell = $seasonality['matrix'][$d][$h];
                                    $val = (float) $cell['total'];
                                    $opacity = $seasonality['max'] > 0 ? max(0.03, $val / $seasonality['max']) : 0.03;
                                @endphp
                                <div class="aspect-square rounded-sm transition hover:ring-1 hover:ring-neon-cyan"
                                     style="background-color: rgba(0, 229, 255, {{ $opacity }});"
                                     title="{{ $dayLabel }} {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 · {{ number_format($val, 0, '.', ' ') }} ₽ ({{ $cell['cnt'] }} тр.)"></div>
                            @endfor
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-ink-300">
                    <span>меньше</span>
                    <div class="flex gap-0.5">
                        @foreach([0.05, 0.2, 0.4, 0.6, 0.8, 1.0] as $op)
                            <div class="w-5 h-3 rounded-sm" style="background: rgba(0, 229, 255, {{ $op }});"></div>
                        @endforeach
                    </div>
                    <span>больше</span>
                    <span class="ml-auto">Пик: <span class="font-mono text-neon-cyan">{{ number_format($seasonality['max'], 0, '.', ' ') }} ₽</span></span>
                </div>
            </section>
        @endif

        {{-- ---- Monthly report download ---- --}}
        <section class="glass rounded-2xl p-6 mb-12">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-display font-bold">Финансовый отчёт за месяц</h2>
                    <p class="text-xs text-ink-300">XLSX с 5 листами: Сводка · По авто · По водителям · Аренды · Транзакции. Откроется в Excel, Numbers, Google Sheets.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('reports.monthly') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[240px] max-w-md">
                    <x-input-label :value="'Период'" />
                    <x-select
                        name="month"
                        :value="$availableMonths[0] ?? now()->format('Y-m')"
                        required
                        :options="collect($availableMonths)->map(function ($m) {
                            try {
                                return ['value' => $m, 'label' => \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $m)->locale('ru')->isoFormat('MMMM YYYY'))];
                            } catch (\Throwable $e) {
                                return ['value' => $m, 'label' => $m];
                            }
                        })->all()" />
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Скачать XLSX
                </button>
            </form>
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
