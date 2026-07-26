@php
    $tabs = [
        ''        => ['label' => 'Все',            'count' => $total],
        'booked'  => ['label' => 'Записи',         'count' => (int) ($counts['booked'] ?? 0)],
        'handoff' => ['label' => 'К менеджеру',    'count' => (int) ($counts['handoff'] ?? 0)],
        'think'   => ['label' => 'Думают',         'count' => (int) ($counts['think'] ?? 0)],
        'reject'  => ['label' => 'Отказы',         'count' => (int) ($counts['reject'] ?? 0)],
    ];

    $resultStyle = fn (string $r) => match ($r) {
        'booked'  => 'bg-neon-lime/15 text-neon-lime',
        'handoff' => 'bg-neon-cyan/15 text-neon-cyan',
        'reject'  => 'bg-neon-red/15 text-neon-red',
        'think'   => 'bg-white/10 text-ink-100',
        default   => 'bg-white/5 text-ink-300',
    };
@endphp
<x-app-layout>
    @section('title', 'Заявки')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6">
            <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
            <h1 class="text-3xl font-display font-bold tracking-tight">Заявки от AI-менеджера</h1>
            <p class="text-ink-300 text-sm mt-1">
                Всего: <span class="text-neon-cyan font-semibold">{{ $total }}</span>
                · записей <span class="text-neon-lime font-semibold">{{ (int) ($counts['booked'] ?? 0) }}</span>
                · отказов <span class="text-neon-red font-semibold">{{ (int) ($counts['reject'] ?? 0) }}</span>
            </p>
        </div>

        {{-- Фильтр по результату --}}
        <div class="flex flex-wrap gap-1.5 mb-5">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('leads.index', array_filter(['result' => $key])) }}"
                   class="px-3 py-1.5 rounded-full text-sm border transition
                          {{ (string) $result === (string) $key
                             ? 'border-neon-cyan/50 bg-neon-cyan/10 text-neon-cyan'
                             : 'border-white/10 bg-white/5 text-ink-300 hover:text-ink-100' }}">
                    {{ $tab['label'] }}
                    <span class="ml-1 text-xs opacity-70">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-36 whitespace-nowrap">Время</th>
                            <th class="w-40 whitespace-nowrap">Клиент</th>
                            <th class="w-36 whitespace-nowrap">Телефон</th>
                            <th class="w-48 whitespace-nowrap">Модель / тариф</th>
                            <th class="w-40 whitespace-nowrap">Когда приедет</th>
                            <th class="w-36 whitespace-nowrap">Результат</th>
                            <th>Выжимка</th>
                            <th class="w-20 whitespace-nowrap">Источник</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $lead)
                            <tr>
                                <td class="whitespace-nowrap text-ink-300 text-sm">{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                                <td class="whitespace-nowrap font-medium">{{ $lead->name ?: '—' }}</td>
                                <td class="whitespace-nowrap font-mono text-sm">
                                    @if($lead->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->phone) }}" class="text-neon-cyan hover:underline">{{ $lead->phone }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-sm">
                                    <div>{{ $lead->model ?: '—' }}</div>
                                    @if($lead->tariff)<div class="text-ink-300 text-xs">{{ $lead->tariff }}</div>@endif
                                </td>
                                <td class="text-sm">{{ $lead->visit_at ?: '—' }}</td>
                                <td class="whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $resultStyle($lead->result) }}">
                                        {{ $lead->result_label }}
                                    </span>
                                    @if($lead->reason)<div class="text-ink-300 text-xs mt-1">{{ $lead->reason }}</div>@endif
                                </td>
                                <td class="text-sm text-ink-100 max-w-md">{{ $lead->summary ?: '—' }}</td>
                                <td class="whitespace-nowrap text-ink-300 text-xs uppercase">{{ $lead->source }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-10 text-ink-300">Пока нет заявок</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </div>
</x-app-layout>
