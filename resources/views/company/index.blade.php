<x-app-layout>
    @section('title', 'Компания')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Финансы компании</h1>
                <p class="text-ink-300 text-sm mt-1">
                    Доход <span class="font-mono text-neon-lime">+{{ number_format($incomeTotal, 2, '.', ' ') }} ₽</span>
                    · расход <span class="font-mono text-neon-red">−{{ number_format($expenseTotal, 2, '.', ' ') }} ₽</span>
                    · итог <span class="font-mono {{ ($incomeTotal - $expenseTotal) >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">{{ number_format($incomeTotal - $expenseTotal, 2, '.', ' ') }} ₽</span>
                </p>
            </div>
            <a href="{{ route('company.create') }}" class="btn btn-primary self-start">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Добавить транзакцию
            </a>
        </div>

        {{-- Owners summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @foreach($owners as $o)
                <div class="glass rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-display font-semibold">{{ $o['name'] }}</div>
                        <span class="badge badge-driver">{{ rtrim(rtrim(number_format($o['percent'], 4, '.', ''), '0'), '.') }}%</span>
                    </div>
                    <div class="text-2xl font-mono font-bold {{ $o['balance'] >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                        {{ number_format($o['balance'], 2, '.', ' ') }} ₽
                    </div>
                    <div class="text-xs text-ink-300 mt-2">
                        доля дохода <span class="text-neon-lime font-mono">+{{ number_format($o['income_share'], 2, '.', ' ') }}</span><br>
                        доля расхода <span class="text-neon-red font-mono">−{{ number_format($o['expense_share'], 2, '.', ' ') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-40">Дата</th>
                            <th class="w-24">Тип</th>
                            <th class="text-right w-36">Сумма</th>
                            <th>Назначение</th>
                            <th>Доли владельцев</th>
                            <th class="w-28">Автор</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $t)
                            <tr>
                                <td class="text-xs font-mono text-ink-200 whitespace-nowrap">
                                    {{ $t->created_at->format('d.m.Y H:i') }}
                                    @if($t->source === 'sbp')<div class="text-[10px] text-neon-cyan">СБП</div>@endif
                                </td>
                                <td>
                                    @if($t->isIncome())
                                        <span class="badge badge-deposit">доход</span>
                                    @else
                                        <span class="badge badge-withdrawal">расход</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-semibold whitespace-nowrap {{ $t->isIncome() ? 'text-neon-lime' : 'text-neon-red' }}">
                                    {{ $t->signed_amount }} ₽
                                </td>
                                <td class="text-sm text-ink-200 max-w-xs truncate" title="{{ $t->comment }}">{{ $t->comment ?: '—' }}</td>
                                <td class="text-xs text-ink-300">
                                    @foreach(($t->splits ?? []) as $s)
                                        <div>{{ $s['name'] ?? $s['login'] }}: <span class="font-mono {{ $t->isIncome() ? 'text-neon-lime' : 'text-neon-red' }}">{{ number_format($s['amount'], 2, '.', ' ') }}</span></div>
                                    @endforeach
                                </td>
                                <td class="text-xs text-ink-300">{{ $t->creator?->short_name ?: 'система' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-10 text-ink-300">Транзакций компании пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-white/5">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
