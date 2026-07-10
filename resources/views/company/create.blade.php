<x-app-layout>
    @section('title', 'Транзакция компании')

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6">
            <a href="{{ route('company.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                к финансам компании
            </a>
            <h1 class="text-3xl font-display font-bold tracking-tight">Транзакция компании</h1>
            <p class="text-ink-300 text-sm">Сумма разделится между владельцами по их долям.</p>
        </div>

        <form method="POST" action="{{ route('company.store') }}" class="glass rounded-2xl p-6 space-y-5"
              x-data="{ type: '{{ old('type', 'expense') }}', amount: {{ (float) old('amount', 0) }} }">
            @csrf

            <div class="grid grid-cols-2 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="expense" x-model="type" class="sr-only">
                    <div class="rounded-xl px-3 py-3 text-center border transition"
                         :class="type==='expense' ? 'border-neon-red/60 bg-neon-red/10 text-neon-red' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                        <div class="text-xl">−</div>
                        <div class="text-xs uppercase tracking-wider">Расход</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="income" x-model="type" class="sr-only">
                    <div class="rounded-xl px-3 py-3 text-center border transition"
                         :class="type==='income' ? 'border-neon-lime/60 bg-neon-lime/10 text-neon-lime' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                        <div class="text-xl">+</div>
                        <div class="text-xs uppercase tracking-wider">Доход</div>
                    </div>
                </label>
            </div>
            <x-input-error :messages="$errors->get('type')" />

            <div>
                <x-input-label for="amount" :value="'Сумма (₽) *'" />
                <x-text-input id="amount" type="number" step="0.01" min="0.01" name="amount" x-model.number="amount" :value="old('amount')" required />
                <x-input-error :messages="$errors->get('amount')" />
            </div>

            <div>
                <x-input-label for="comment" :value="'Назначение *'" />
                <textarea id="comment" name="comment" rows="2" maxlength="500" required
                          class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan"
                          placeholder="напр. аренда офиса, реклама, запчасти">{{ old('comment') }}</textarea>
                <x-input-error :messages="$errors->get('comment')" />
            </div>

            {{-- Live split preview --}}
            <div class="rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm">
                <div class="text-xs uppercase tracking-wider text-ink-300 mb-2">Разбивка по владельцам</div>
                @foreach(config('owners.shares') as $o)
                    <div class="flex items-center justify-between text-ink-200">
                        <span>{{ $o['login'] }} ({{ rtrim(rtrim(number_format($o['percent'], 4, '.', ''), '0'), '.') }}%)</span>
                        <span class="font-mono" x-text="((parseFloat(amount)||0) * {{ $o['percent'] }} / 100).toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₽'"></span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('company.index') }}" class="btn btn-ghost">Отмена</a>
                <x-primary-button>Добавить</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
