<x-app-layout>
    @section('title', 'Тарифы')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Тарифы аренды</h1>
                <p class="text-ink-300 text-sm mt-1">Всего тарифов: <span class="text-neon-violet font-semibold">{{ $tariffs->total() }}</span></p>
            </div>
            <a href="{{ route('tariffs.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Новый тариф
            </a>
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
                            <th>Название</th>
                            <th>Период</th>
                            <th class="text-right">Сумма</th>
                            <th class="text-right">Депозит</th>
                            <th>Доп.</th>
                            <th>Активен</th>
                            <th>Аренд</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tariffs as $t)
                            <tr onclick="location.href='{{ route('tariffs.show', $t) }}'" class="cursor-pointer group">
                                <td class="text-ink-300 font-mono text-xs">{{ $t->id }}</td>
                                <td>
                                    <div class="font-medium group-hover:text-neon-cyan transition">{{ $t->name }}</div>
                                    @if($t->description)
                                        <div class="text-xs text-ink-300 truncate max-w-xs">{{ $t->description }}</div>
                                    @endif
                                </td>
                                <td class="text-sm">{{ $t->period_human }}</td>
                                <td class="text-right font-mono font-semibold text-neon-cyan">{{ number_format((float) $t->amount, 2, '.', ' ') }} ₽</td>
                                <td class="text-right font-mono">{{ number_format((float) $t->deposit_amount, 2, '.', ' ') }} ₽</td>
                                <td class="text-sm">{{ count($t->extras ?? []) }}</td>
                                <td>
                                    @if($t->is_active)
                                        <span class="badge badge-deposit">active</span>
                                    @else
                                        <span class="badge badge-withdrawal">archived</span>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-200">{{ $t->rentals_count }}</td>
                                <td class="text-right pr-4">
                                    <svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-10 text-ink-300">Тарифов ещё нет. <a href="{{ route('tariffs.create') }}" class="text-neon-cyan underline">Создать первый</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tariffs->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $tariffs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
