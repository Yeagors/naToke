@php
    $tabs = [
        'all' => 'Все',
        'auth' => 'Авторизация',
        'users' => 'Пользователи',
        'tariffs' => 'Тарифы',
        'cars' => 'Авто',
        'rentals' => 'Аренды',
        'money' => 'Деньги',
    ];
@endphp
<x-app-layout>
    @section('title', 'Логи активности')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Аудит</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Логи активности</h1>
                <p class="text-ink-300 text-sm mt-1">Всего записей в журнале: <span class="text-neon-cyan font-semibold">{{ $counts['all'] }}</span></p>
            </div>

            <form method="GET" action="{{ route('logs.index') }}" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="group" value="{{ $group }}">
                @if($actorId) <input type="hidden" name="actor_id" value="{{ $actorId }}"> @endif

                <div>
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">от</label>
                    <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-white/10 bg-ink-800/70 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">до</label>
                    <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-white/10 bg-ink-800/70 text-sm">
                </div>
                <div class="relative">
                    <label class="block text-[10px] uppercase tracking-[0.14em] text-ink-300 mb-1">поиск</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="по описанию / актёру / действию"
                           class="w-72 rounded-lg border-white/10 bg-ink-800/70 pl-3 pr-3 text-sm">
                </div>
                <button type="submit" class="btn btn-ghost text-sm">Применить</button>
                @if($from || $to || $q || $actorId)
                    <a href="{{ route('logs.index', $group === 'all' ? [] : ['group' => $group]) }}" class="btn btn-ghost text-sm text-neon-red border-neon-red/30">×</a>
                @endif
            </form>
        </div>

        {{-- Group chips --}}
        <div class="flex flex-wrap items-center gap-2 mb-6">
            @foreach($tabs as $key => $label)
                @php $isActive = $group === $key || ($key === 'all' && $group === 'all'); @endphp
                <a href="{{ route('logs.index', array_filter([
                        'group' => $key === 'all' ? null : $key,
                        'q' => $q ?: null,
                        'from' => $from ?: null,
                        'to' => $to ?: null,
                        'actor_id' => $actorId ?: null,
                   ])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm transition border
                          {{ $isActive ? 'bg-neon-cyan/10 border-neon-cyan/40 text-neon-cyan shadow-glow-cyan' : 'bg-white/5 border-white/10 text-ink-200 hover:border-white/20' }}">
                    <span>{{ $label }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono {{ $isActive ? 'bg-neon-cyan/20 text-neon-cyan' : 'bg-white/5 text-ink-300' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-40">Время</th>
                            <th class="w-56">Кто</th>
                            <th class="w-48">Действие</th>
                            <th>Описание</th>
                            <th class="w-40">Объект</th>
                            <th class="w-28">IP</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr x-data="{ open: false }" class="align-top">
                                <td class="text-xs font-mono text-ink-200 whitespace-nowrap" title="{{ $log->created_at->format('d.m.Y H:i:s') }}">
                                    {{ $log->created_at->format('d.m.Y H:i') }}
                                    <div class="text-[10px] text-ink-300">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($log->actor)
                                        <a href="{{ route('logs.index', ['actor_id' => $log->user_id]) }}" class="inline-flex items-center gap-2 hover:text-neon-cyan transition">
                                            @if($log->actor->photo)
                                                <img src="{{ $log->actor->photo_url }}" class="w-7 h-7 rounded-full object-cover">
                                            @else
                                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white"
                                                     style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                                    {{ mb_substr($log->actor->first_name, 0, 1) }}{{ mb_substr($log->actor->last_name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium truncate">{{ $log->actor->short_name }}</div>
                                                <div class="text-[10px] text-ink-300 truncate">{{ '@'.$log->actor->login }}</div>
                                            </div>
                                        </a>
                                    @else
                                        <div class="inline-flex items-center gap-2 text-ink-300">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-sm">system / cron</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $log->action_badge }}">{{ $log->action_label }}</span>
                                    <div class="text-[10px] text-ink-400 font-mono mt-1">{{ $log->action }}</div>
                                </td>
                                <td class="text-sm">
                                    {{ $log->description ?: '—' }}
                                    @if(! empty($log->changes))
                                        <button type="button" @click="open = !open" class="block text-[10px] text-neon-cyan hover:underline mt-1">
                                            <span x-show="!open">показать изменения ▾</span>
                                            <span x-show="open" x-cloak>скрыть ▴</span>
                                        </button>
                                        <div x-show="open" x-cloak class="mt-2 rounded-lg border border-white/10 bg-ink-900/60 p-3 text-xs space-y-1">
                                            @foreach($log->changes as $field => $diff)
                                                <div class="grid grid-cols-[140px_1fr] gap-2">
                                                    <span class="text-ink-300 font-mono">{{ $field }}</span>
                                                    <span>
                                                        <span class="text-neon-red line-through">{{ $diff['old'] !== null && $diff['old'] !== '' ? $diff['old'] : '∅' }}</span>
                                                        <span class="text-ink-300 mx-1">→</span>
                                                        <span class="text-neon-lime">{{ $diff['new'] !== null && $diff['new'] !== '' ? $diff['new'] : '∅' }}</span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($log->subject_url)
                                        <a href="{{ $log->subject_url }}" class="text-sm text-neon-cyan hover:underline truncate block max-w-[200px]" title="{{ $log->subject_label }}">{{ $log->subject_label ?: $log->subject_type }}</a>
                                    @elseif($log->subject_label)
                                        <span class="text-sm text-ink-200">{{ $log->subject_label }}</span>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-ink-300 font-mono">{{ $log->ip_address ?? '—' }}</td>
                                <td class="text-right pr-4 text-[10px] text-ink-400 font-mono">#{{ $log->id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-10 text-ink-300">
                                Логов по этим фильтрам нет.
                                @if($group !== 'all' || $q || $from || $to || $actorId)
                                    <a href="{{ route('logs.index') }}" class="text-neon-cyan underline ml-2">Сбросить фильтры</a>
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
