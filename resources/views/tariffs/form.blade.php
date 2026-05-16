@php
    $isEdit = $tariff->exists;
    $action = $isEdit ? route('tariffs.update', $tariff) : route('tariffs.store');
    $extrasInitial = json_encode(old('extras', $tariff->extras ?? []), JSON_UNESCAPED_UNICODE);
@endphp
<x-app-layout>
    @section('title', $isEdit ? $tariff->name : 'Новый тариф')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex items-end justify-between gap-3">
            <div>
                <a href="{{ route('tariffs.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    к списку тарифов
                </a>
                <h1 class="text-3xl font-display font-bold tracking-tight">
                    {{ $isEdit ? $tariff->name : 'Новый тариф' }}
                </h1>
                @if($isEdit)
                    <p class="text-ink-300 text-sm">id {{ $tariff->id }} · аренд по нему: {{ $tariff->rentals()->count() }}</p>
                @endif
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="glass rounded-2xl p-6 space-y-5"
              x-data='{
                extras: {{ $extrasInitial }},
                isBuyout: {{ old('is_buyout', $tariff->is_buyout ?? false) ? 'true' : 'false' }},
                buyoutPrice: {{ json_encode((string) old('buyout_price', $tariff->buyout_price ?? '')) }},
                amount: {{ json_encode((string) old('amount', $tariff->amount ?? '')) }},
                periodCount: {{ json_encode((string) old('period_count', $tariff->period_count ?? 1)) }},
                periodUnit: {{ json_encode((string) old('period', optional($tariff->period)->value ?? 'hour')) }},
                periodLabels: { minute: "минут", hour: "часов", day: "дней", week: "недель", month: "месяцев" },
                get computedPayments() {
                    const a = parseFloat(this.amount) || 0;
                    const p = parseFloat(this.buyoutPrice) || 0;
                    if (a <= 0 || p <= 0) return 0;
                    return Math.ceil(p / a);
                },
                get periodLabel() {
                    return this.periodLabels[this.periodUnit] || this.periodUnit;
                },
                get estimatedDurationLabel() {
                    const n = this.computedPayments;
                    const pc = parseInt(this.periodCount) || 1;
                    return n * pc;
                }
              }'>
            @csrf
            @if($isEdit) @method('PATCH') @endif

            <div>
                <x-input-label for="name" :value="'Название *'" />
                <x-text-input id="name" type="text" name="name" :value="old('name', $tariff->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="amount" :value="'Сумма списания (₽) *'" />
                    <x-text-input id="amount" type="number" step="0.01" min="0" name="amount" x-model="amount" :value="old('amount', $tariff->amount)" required />
                    <x-input-error :messages="$errors->get('amount')" />
                </div>
                <div>
                    <x-input-label for="period_count" :value="'Каждые *'" />
                    <x-text-input id="period_count" type="number" min="1" name="period_count" x-model="periodCount" :value="old('period_count', $tariff->period_count ?? 1)" required />
                    <x-input-error :messages="$errors->get('period_count')" />
                </div>
                <div>
                    <x-input-label for="period" :value="'Период *'" />
                    <select id="period" name="period" required x-model="periodUnit"
                            class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">
                        @foreach($periods as $p)
                            <option value="{{ $p->value }}" @selected(old('period', optional($tariff->period)->value ?? 'hour') === $p->value)>{{ $p->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('period')" />
                </div>
            </div>

            <div>
                <x-input-label for="deposit_amount" :value="'Депозит при старте (₽)'" />
                <x-text-input id="deposit_amount" type="number" step="0.01" min="0" name="deposit_amount" :value="old('deposit_amount', $tariff->deposit_amount ?? 0)" />
                <p class="text-xs text-ink-300 mt-1">Списывается с баланса пользователя одной транзакцией в момент создания аренды.</p>
                <x-input-error :messages="$errors->get('deposit_amount')" />
            </div>

            <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Доп. транзакции (списываются вместе с каждым периодом)</legend>

                <div class="space-y-2 mt-2">
                    <template x-for="(row, idx) in extras" :key="idx">
                        <div class="flex items-center gap-2">
                            <input type="text" placeholder="название (страховка, бонус и т.п.)"
                                   :name="`extras[${idx}][label]`" x-model="row.label"
                                   class="flex-1 rounded-lg border-white/10 bg-ink-800/70 text-sm">
                            <input type="number" step="0.01" min="0" placeholder="₽"
                                   :name="`extras[${idx}][amount]`" x-model="row.amount"
                                   class="w-32 rounded-lg border-white/10 bg-ink-800/70 text-sm">
                            <button type="button" @click="extras.splice(idx, 1)" class="btn btn-ghost px-3 py-2 text-neon-red border-neon-red/30 hover:bg-neon-red/10">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="extras.push({label:'', amount:''})" class="btn btn-ghost mt-3 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Добавить доп. транзакцию
                </button>
                <x-input-error :messages="$errors->get('extras')" />
            </fieldset>

            <div>
                <x-input-label for="description" :value="'Описание / комментарий'" />
                <textarea id="description" name="description" rows="3" maxlength="2000"
                          class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">{{ old('description', $tariff->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>

            <label class="flex items-center gap-3 cursor-pointer select-none">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="rounded border-white/15 bg-ink-800/70 text-neon-cyan focus:ring-neon-cyan/40"
                       @checked(old('is_active', $tariff->is_active ?? true))>
                <span class="text-sm">Активен (доступен для новых аренд)</span>
            </label>

            {{-- Buyout / lease-to-own mode --}}
            <fieldset class="rounded-xl border border-neon-violet/20 px-4 py-3 bg-neon-violet/5">
                <legend class="px-2 text-xs uppercase tracking-[0.18em] text-neon-violet">Режим раската (выкуп авто)</legend>

                <label class="flex items-start gap-3 cursor-pointer select-none mt-2">
                    <input type="hidden" name="is_buyout" value="0">
                    <input type="checkbox" name="is_buyout" value="1" x-model="isBuyout"
                           class="mt-0.5 rounded border-white/15 bg-ink-800/70 text-neon-violet focus:ring-neon-violet/40">
                    <span>
                        <span class="text-sm font-medium">Этот тариф — раскат на выкуп</span>
                        <span class="block text-xs text-ink-300 mt-0.5">
                            Каждое периодическое списание уменьшает остаток выкупной стоимости. Когда остаток доходит до 0 — аренда автоматически закрывается, авто считается выкупленным.
                        </span>
                    </span>
                </label>

                <div x-show="isBuyout" x-collapse class="mt-4">
                    <x-input-label for="buyout_price" :value="'Выкупная стоимость авто (₽) *'" />
                    <x-text-input id="buyout_price" type="number" step="0.01" min="0.01" name="buyout_price"
                                  x-model="buyoutPrice"
                                  :value="old('buyout_price', $tariff->buyout_price)"
                                  placeholder="например 90000.00" />
                    <x-input-error :messages="$errors->get('buyout_price')" />
                </div>

                <div x-show="isBuyout" x-collapse class="mt-3 rounded-xl border border-white/8 bg-ink-900/40 px-4 py-3">
                    <template x-if="computedPayments > 0">
                        <div class="text-sm text-ink-100">
                            <span class="text-ink-300">При сумме списания</span>
                            <span class="font-mono text-neon-cyan" x-text="amount"></span>
                            <span class="text-ink-300"> ₽ каждые </span>
                            <span class="font-mono" x-text="periodCount"></span>
                            <span x-text="periodLabel"></span>
                            <span class="text-ink-300"> и выкупной стоимости </span>
                            <span class="font-mono text-neon-violet" x-text="buyoutPrice"></span>
                            <span class="text-ink-300"> ₽:</span>
                            <div class="mt-1">
                                Авто будет выкуплено за
                                <span class="font-mono text-neon-lime font-bold" x-text="computedPayments"></span>
                                списаний — это
                                <span class="font-mono text-neon-lime" x-text="estimatedDurationLabel"></span>
                                <span x-text="periodLabel"></span>
                                с момента старта аренды.
                            </div>
                        </div>
                    </template>
                    <template x-if="computedPayments === 0">
                        <div class="text-xs text-ink-300">Введите сумму списания и выкупную стоимость — посчитаю количество платежей автоматически.</div>
                    </template>
                </div>
            </fieldset>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('tariffs.index') }}" class="btn btn-ghost">Отмена</a>
                <x-primary-button>{{ $isEdit ? 'Сохранить' : 'Создать тариф' }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
