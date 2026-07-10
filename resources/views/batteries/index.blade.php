<x-app-layout>
    @section('title', 'АКБ')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Аккумуляторы</h1>
                <p class="text-ink-300 text-sm mt-1">Всего: <span class="text-neon-cyan font-semibold">{{ $batteries->total() }}</span></p>
            </div>
            <a href="{{ route('batteries.create') }}" class="btn btn-primary self-start">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Добавить АКБ
            </a>
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-24">Позывной</th>
                            <th>Модель</th>
                            <th>Ёмкость</th>
                            <th>ВИН</th>
                            <th class="w-28">Статус</th>
                            <th class="text-right w-24">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batteries as $b)
                            <tr>
                                <td class="font-mono text-sm">{{ $b->callsign ?: '—' }}</td>
                                <td class="text-sm">{{ $b->car_model }}</td>
                                <td class="text-sm">{{ $b->capacity ?: '—' }}</td>
                                <td class="font-mono text-xs text-ink-200">{{ $b->vin }}</td>
                                <td>
                                    @if($b->active_rentals_count > 0)
                                        <span class="badge badge-driver">на аренде</span>
                                    @else
                                        <span class="badge badge-deposit">свободна</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('batteries.edit', $b) }}" class="btn btn-ghost text-xs">Изменить</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-10 text-ink-300">Аккумуляторов пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($batteries->hasPages())
                <div class="px-4 py-3 border-t border-white/5">{{ $batteries->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
