<x-app-layout>
    @section('title', 'Пополнения СБП')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Пополнения · СБП</h1>
                <p class="text-ink-300 text-sm mt-1">Всего: <span class="text-neon-cyan font-semibold">{{ $payments->total() }}</span></p>
            </div>
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-40 whitespace-nowrap">Дата</th>
                            <th>Пользователь</th>
                            <th class="text-right w-44 whitespace-nowrap">Сумма</th>
                            <th class="w-36 whitespace-nowrap">Статус</th>
                            <th class="whitespace-nowrap">Чек</th>
                            <th class="text-right w-32 whitespace-nowrap">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                            <tr>
                                <td class="text-xs font-mono text-ink-200 whitespace-nowrap" title="{{ $p->created_at->format('d.m.Y H:i:s') }}">
                                    {{ $p->created_at->format('d.m.Y H:i') }}
                                    <div class="text-[10px] text-ink-300">{{ $p->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if($p->user)
                                        <a href="{{ route('users.show', $p->user) }}" class="text-sm text-neon-cyan hover:underline">{{ $p->user->full_name }}</a>
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono whitespace-nowrap">
                                    <span class="text-neon-lime font-semibold">{{ number_format((float) $p->amount, 2, '.', ' ') }} ₽</span>
                                    @if($p->fee_amount > 0)
                                        <div class="text-[10px] text-ink-300">к оплате {{ number_format($p->payable_amount, 2, '.', ' ') }} ₽</div>
                                    @endif
                                </td>
                                <td><span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span></td>
                                <td class="text-xs text-ink-200">{{ $p->user?->email ?: $p->user?->phone ?: '—' }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @if($p->isConfirmed())
                                        <form method="POST" action="{{ route('payments.refund', $p) }}"
                                              onsubmit="return confirm('Вернуть платёж #{{ $p->id }} на {{ number_format((float) $p->amount, 2, '.', ' ') }} ₽? Сумма спишется с баланса пользователя.')">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost text-xs text-neon-red border-neon-red/30 hover:bg-neon-red/10">Возврат</button>
                                        </form>
                                    @else
                                        <span class="text-ink-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-10 text-ink-300">Пополнений пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
