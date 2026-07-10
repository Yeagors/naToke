<x-app-layout>
    @section('title', 'Авто')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Парк авто</h1>
                <p class="text-ink-300 text-sm mt-1">Всего в базе: <span class="text-neon-violet font-semibold">{{ $cars->total() }}</span></p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('cars.index') }}" class="relative">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Поиск по марке / номеру"
                           class="w-72 rounded-xl border-white/10 bg-ink-800/70 pl-9 text-sm">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                </form>
                <a href="{{ route('cars.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Добавить
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Авто</th>
                            <th>Номер</th>
                            <th>Год</th>
                            <th>Аккумулятор</th>
                            <th class="text-right">Баланс</th>
                            <th>Аренда</th>
                            <th>Добавлено</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cars as $c)
                            <tr onclick="location.href='{{ route('cars.show', $c) }}'" class="cursor-pointer group">
                                <td class="text-ink-300 font-mono text-xs">{{ $c->id }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($c->photo)
                                            <img src="{{ $c->photo_url }}" class="w-12 h-9 rounded-md object-cover ring-1 ring-white/10" alt="">
                                        @else
                                            <div class="w-12 h-9 rounded-md flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                                <svg class="w-5 h-5 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium group-hover:text-neon-cyan transition">{{ $c->display_name }}</div>
                                            <div class="text-xs text-ink-300">id: {{ $c->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono">{{ $c->license_plate }}</td>
                                <td class="text-sm">{{ $c->year ?? '—' }}</td>
                                <td>
                                    <div class="text-sm">
                                        {{ $c->battery_capacity ?: '—' }}
                                    </div>
                                    @if($c->battery_number)
                                        <div class="text-xs text-ink-300 font-mono">№ {{ $c->battery_number }}</div>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-semibold {{ (float) $c->balance >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                                    {{ number_format((float) $c->balance, 2, '.', ' ') }} ₽
                                </td>
                                <td>
                                    @if($c->active_rentals_count > 0)
                                        <span class="badge badge-deposit">в аренде</span>
                                    @else
                                        <span class="badge badge-driver">свободен</span>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-300">{{ $c->created_at?->format('d.m.Y') }}</td>
                                <td class="text-right pr-4">
                                    <svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-10 text-ink-300">Парк пуст</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cars->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $cars->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
