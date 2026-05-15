@php
    $dirTabs = [
        'all'  => ['label' => 'Все',          'count' => $counts['all']],
        'user' => ['label' => 'Пользователи', 'count' => $counts['user']],
        'car'  => ['label' => 'Авто',         'count' => $counts['car']],
    ];
    $signTabs = [
        'all' => 'Все',
        'in'  => 'Приход',
        'out' => 'Расход',
    ];
@endphp
<x-app-layout>
    @section('title', 'Транзакции')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Транзакции</h1>
                <p class="text-ink-300 text-sm mt-1">
                    Найдено: <span class="text-neon-cyan font-semibold">{{ $items->total() }}</span>
                    · приходов <span class="font-mono text-neon-lime">+{{ number_format($totals['in'], 2, '.', ' ') }} ₽</span>
                    · расходов <span class="font-mono text-neon-red">−{{ number_format($totals['out'], 2, '.', ' ') }} ₽</span>
                </p>
            </div>

            <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="sign" value="{{ $sign }}">
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">от</label>
                    <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-white/10 bg-ink-800/70 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">до</label>
                    <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-white/10 bg-ink-800/70 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">поиск</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="комментарий / ФИО / номер"
                           class="w-72 rounded-lg border-white/10 bg-ink-800/70 text-sm">
                </div>
                <button type="submit" class="btn btn-ghost text-sm">Применить</button>
                @if($from || $to || $q || $direction !== 'all' || $sign !== 'all')
                    <a href="{{ route('transactions.index') }}" class="btn btn-ghost text-sm text-neon-red border-neon-red/30">×</a>
                @endif
            </form>
        </div>

        @php
            $chip = fn(bool $on) => $on
                ? 'bg-neon-cyan/10 border-neon-cyan/40 text-neon-cyan shadow-glow-cyan'
                : 'bg-white/5 border-white/10 text-ink-200 hover:border-white/20';
            $chipCount = fn(bool $on) => $on
                ? 'bg-neon-cyan/20 text-neon-cyan'
                : 'bg-white/5 text-ink-300';
            $qsBase = array_filter([
                'q' => $q ?: null,
                'from' => $from ?: null,
                'to' => $to ?: null,
            ]);
        @endphp

        {{-- Direction filter --}}
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="text-[10px] uppercase tracking-[0.18em] text-ink-300 mr-1">Направление:</span>
            @foreach($dirTabs as $key => $t)
                @php $on = $direction === $key; @endphp
                <a href="{{ route('transactions.index', array_merge($qsBase, [
                        'direction' => $key === 'all' ? null : $key,
                        'sign' => $sign === 'all' ? null : $sign,
                   ])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm transition border {{ $chip($on) }}">
                    <span>{{ $t['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono {{ $chipCount($on) }}">{{ $t['count'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Sign filter --}}
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <span class="text-[10px] uppercase tracking-[0.18em] text-ink-300 mr-1">Тип:</span>
            @foreach($signTabs as $key => $label)
                @php $on = $sign === $key; @endphp
                <a href="{{ route('transactions.index', array_merge($qsBase, [
                        'direction' => $direction === 'all' ? null : $direction,
                        'sign' => $key === 'all' ? null : $key,
                   ])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm transition border
                          @if($on)
                              @if($key === 'in')  bg-neon-lime/10 border-neon-lime/40 text-neon-lime shadow-glow-lime
                              @elseif($key === 'out') bg-neon-red/10 border-neon-red/40 text-neon-red shadow-glow-red
                              @else {{ $chip(true) }}
                              @endif
                          @else
                              {{ $chip(false) }}
                          @endif">
                    @if($key === 'in')<span class="font-bold">+</span>@elseif($key === 'out')<span class="font-bold">−</span>@endif
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-40 whitespace-nowrap">Время</th>
                            <th class="w-24 whitespace-nowrap">Источник</th>
                            <th>Объект</th>
                            <th class="w-40 whitespace-nowrap">Действие</th>
                            <th class="text-right w-44 whitespace-nowrap">Сумма</th>
                            <th class="text-right w-44 whitespace-nowrap">Баланс&nbsp;после</th>
                            <th class="w-24 whitespace-nowrap">Аренда</th>
                            <th>Комментарий</th>
                            <th class="w-32 whitespace-nowrap">Автор</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $row)
                            <tr>
                                <td class="text-xs font-mono text-ink-200 whitespace-nowrap" title="{{ $row['created_at']->format('d.m.Y H:i:s') }}">
                                    {{ $row['created_at']->format('d.m.Y H:i') }}
                                    <div class="text-[10px] text-ink-300">{{ $row['created_at']->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($row['kind'] === 'user')
                                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-200">
                                            <svg class="w-3.5 h-3.5 text-neon-cyan" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            user
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-200">
                                            <svg class="w-3.5 h-3.5 text-neon-violet" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                            car
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['subject_url'])
                                        <a href="{{ $row['subject_url'] }}" class="text-sm text-neon-cyan hover:underline truncate block max-w-[260px]">{{ $row['subject_name'] }}</a>
                                    @else
                                        <span class="text-sm text-ink-300">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $row['type_class'] }}">{{ $row['type_label'] }}</span>
                                </td>
                                <td class="text-right font-mono font-semibold whitespace-nowrap {{ $row['sign'] === '+' ? 'text-neon-lime' : 'text-neon-red' }}">
                                    {{ $row['sign'] }}{{ number_format($row['amount'], 2, '.', ' ') }} ₽
                                </td>
                                <td class="text-right font-mono text-xs whitespace-nowrap {{ ($row['balance_after'] ?? 0) >= 0 ? 'text-ink-200' : 'text-neon-red' }}">
                                    @if($row['balance_after'] !== null)
                                        {{ number_format($row['balance_after'], 2, '.', ' ') }} ₽
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['rental_id'])
                                        <a href="{{ route('rentals.show', $row['rental_id']) }}" class="text-sm text-neon-cyan hover:underline font-mono">#{{ $row['rental_id'] }}</a>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-200 max-w-md truncate" title="{{ $row['comment'] }}">{{ $row['comment'] ?: '—' }}</td>
                                <td class="text-xs text-ink-300">{{ $row['created_by'] ?: 'system' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-10 text-ink-300">
                                Транзакций по этим фильтрам нет.
                                @if($direction !== 'all' || $sign !== 'all' || $q || $from || $to)
                                    <a href="{{ route('transactions.index') }}" class="text-neon-cyan underline ml-2">Сбросить фильтры</a>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
