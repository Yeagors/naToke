@php
    $tabs = [
        'all' => ['label' => 'Все', 'cls' => 'badge-driver'],
        'open' => ['label' => 'Открытые', 'cls' => 'badge-deposit'],
        'paused' => ['label' => 'Приостановлены', 'cls' => 'badge-driver'],
        'closed' => ['label' => 'Закрытые', 'cls' => 'badge-withdrawal'],
    ];
@endphp
<x-app-layout>
    @section('title', 'Аренды')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Аренды</h1>
                <p class="text-ink-300 text-sm mt-1">Всего записей в базе: <span class="text-neon-cyan font-semibold">{{ $counts['all'] }}</span></p>
            </div>
            <form method="GET" action="{{ route('rentals.index') }}" class="relative">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Поиск по арендатору / номеру авто / марке"
                       class="w-80 rounded-xl border-white/10 bg-ink-800/70 pl-9 text-sm">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
            </form>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        {{-- Status filter chips --}}
        <div class="flex flex-wrap items-center gap-2 mb-6">
            @foreach($tabs as $key => $tab)
                @php $isActive = $status === $key || ($key === 'all' && ! in_array($status, ['open','paused','closed'])); @endphp
                <a href="{{ route('rentals.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm transition border
                          {{ $isActive ? 'bg-neon-cyan/10 border-neon-cyan/40 text-neon-cyan shadow-glow-cyan' : 'bg-white/5 border-white/10 text-ink-200 hover:border-white/20' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono {{ $isActive ? 'bg-neon-cyan/20 text-neon-cyan' : 'bg-white/5 text-ink-300' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach

            @if($q !== '')
                <a href="{{ route('rentals.index', array_filter(['status' => $status === 'all' ? null : $status])) }}"
                   class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs bg-neon-red/10 text-neon-red border border-neon-red/30 hover:bg-neon-red/20">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    «{{ $q }}»
                </a>
            @endif
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Авто</th>
                            <th>Арендатор</th>
                            <th>Тариф</th>
                            <th>Статус</th>
                            <th>Старт</th>
                            <th>След. списание</th>
                            <th class="text-right">Сумма</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rentals as $r)
                            <tr onclick="location.href='{{ route('rentals.show', $r) }}'" class="cursor-pointer group">
                                <td class="text-ink-300 font-mono text-xs">{{ $r->id }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($r->car?->photo)
                                            <img src="{{ $r->car->photo_url }}" class="w-10 h-7 rounded-md object-cover ring-1 ring-white/10" alt="">
                                        @else
                                            <div class="w-10 h-7 rounded-md flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                                <svg class="w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-medium truncate group-hover:text-neon-cyan transition">{{ $r->car?->display_name ?? '—' }}</div>
                                            <div class="text-xs text-ink-300 font-mono">{{ $r->car?->license_plate }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm">{{ $r->user?->short_name ?? '—' }}</div>
                                    @if($r->user?->phone)
                                        <div class="text-xs text-ink-300 font-mono">{{ $r->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-200">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $r->tariff?->name ?? '—' }}</span>
                                        @if($r->is_buyout)
                                            <span class="badge"
                                                  style="background:rgba(168,85,247,0.12);color:#a855f7;box-shadow:inset 0 0 0 1px rgba(168,85,247,0.30)"
                                                  title="Раскат">⚡</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-ink-300">
                                        {{ $r->period_count }} {{ $r->period->label() }}
                                    </div>
                                </td>
                                <td><span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                                <td class="text-xs text-ink-300 font-mono">{{ $r->started_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                <td class="text-xs font-mono">
                                    @if($r->next_charge_at && $r->status->value === 'open')
                                        <span class="text-neon-cyan">{{ $r->next_charge_at->format('d.m.Y H:i') }}</span>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-semibold text-neon-lime">{{ number_format((float) $r->amount, 2, '.', ' ') }} ₽</td>
                                <td class="text-right pr-4">
                                    <svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-10 text-ink-300">
                                Ничего не нашлось.
                                @if(in_array($status, ['open','paused','closed']) || $q)
                                    <a href="{{ route('rentals.index') }}" class="text-neon-cyan underline ml-2">Сбросить фильтры</a>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rentals->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $rentals->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
