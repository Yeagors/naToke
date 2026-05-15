<x-app-layout>
    @section('title', 'Пользователи')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Раздел</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">Пользователи</h1>
                <p class="text-ink-300 text-sm mt-1">Всего в базе: <span class="text-neon-cyan font-semibold">{{ $users->total() }}</span></p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('users.index') }}" class="relative">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Поиск по ФИО / логину / паспорту"
                           class="w-72 rounded-xl border-white/10 bg-ink-800/70 pl-9 text-sm">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                </form>
                <a href="{{ route('users.create') }}" class="btn btn-primary">
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
                            <th>Пользователь</th>
                            <th>Логин</th>
                            <th>Паспорт</th>
                            <th>Дата рожд.</th>
                            <th>Роль</th>
                            <th class="text-right">Баланс</th>
                            <th>Создан</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr onclick="location.href='{{ route('users.show', $u) }}'" class="cursor-pointer group">
                                <td class="text-ink-300 font-mono text-xs">{{ $u->id }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($u->photo)
                                            <img src="{{ $u->photo_url }}" class="w-9 h-9 rounded-full object-cover" alt="">
                                        @else
                                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-[12px] font-bold text-white" style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                                {{ mb_substr($u->first_name, 0, 1) }}{{ mb_substr($u->last_name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium group-hover:text-neon-cyan transition">{{ $u->full_name }}</div>
                                            @if($u->passport_department_code)
                                                <div class="text-xs text-ink-300">подразд. {{ $u->passport_department_code }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono text-sm">{{ '@'.$u->login }}</td>
                                <td class="font-mono text-xs text-ink-200">
                                    @if($u->passport_series || $u->passport_number)
                                        {{ $u->passport_series }} {{ $u->passport_number }}
                                    @else
                                        <span class="text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-200">
                                    {{ optional($u->birth_date)->format('d.m.Y') ?? '—' }}
                                </td>
                                <td>
                                    @if($u->isAdmin())
                                        <span class="badge badge-admin">admin</span>
                                    @else
                                        <span class="badge badge-driver">driver</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono font-semibold {{ (float) $u->balance >= 0 ? 'text-neon-lime' : 'text-neon-red' }}">
                                    {{ number_format((float) $u->balance, 2, '.', ' ') }} ₽
                                </td>
                                <td class="text-sm text-ink-300">{{ $u->created_at?->format('d.m.Y') }}</td>
                                <td class="text-right pr-4">
                                    <svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-10 text-ink-300">Никого не нашлось</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-4 py-3 border-t border-white/5">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
