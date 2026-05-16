@php
    $active = $car->activeRental;
@endphp
<x-app-layout>
    @section('title', $car->display_name)

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <a href="{{ route('cars.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    к списку авто
                </a>
                <h1 class="text-3xl font-display font-bold tracking-tight">{{ $car->display_name }}</h1>
                <p class="text-ink-300 text-sm font-mono">{{ $car->license_plate }} · id {{ $car->id }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($active)
                    <a href="{{ route('rentals.show', $active) }}" class="badge {{ $active->status->badgeClass() }}">
                        Аренда #{{ $active->id }} · {{ $active->status->label() }}
                    </a>
                @else
                    <span class="badge badge-driver">свободен</span>
                @endif
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: photo + balance + car transactions --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                {{-- Photo card with quick upload --}}
                <div class="glass rounded-2xl p-6">
                    <form method="POST" action="{{ route('cars.update', $car) }}" enctype="multipart/form-data" id="car-photo-form">
                        @csrf
                        @method('PATCH')
                        {{-- Preserve all other fields so validation passes and DB columns stay intact --}}
                        <input type="hidden" name="brand" value="{{ $car->brand }}">
                        <input type="hidden" name="model" value="{{ $car->model }}">
                        <input type="hidden" name="year" value="{{ $car->year }}">
                        <input type="hidden" name="license_plate" value="{{ $car->license_plate }}">
                        <input type="hidden" name="balance" value="{{ $car->balance }}">
                        <input type="hidden" name="battery_capacity" value="{{ $car->battery_capacity }}">
                        <input type="hidden" name="battery_number" value="{{ $car->battery_number }}">
                        <input type="hidden" name="comment" value="{{ $car->comment }}">

                        <div class="relative group">
                            @if($car->photo)
                                <img src="{{ $car->photo_url }}" class="w-full aspect-video rounded-xl object-cover ring-1 ring-white/10" alt="">
                            @else
                                <div class="w-full aspect-video rounded-xl flex items-center justify-center bg-white/5 ring-1 ring-white/10">
                                    <svg class="w-12 h-12 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10l1.5-4.5h-5L11 10m-1 4h4m-7 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <label for="car-photo-input"
                                   class="absolute inset-0 flex items-center justify-center bg-ink-950/70 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition cursor-pointer rounded-xl">
                                <div class="text-center">
                                    <svg class="w-10 h-10 mx-auto text-neon-cyan mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <div class="text-sm font-semibold text-neon-cyan">Сменить фото</div>
                                </div>
                            </label>
                        </div>

                        <input id="car-photo-input" type="file" name="photo" accept="image/*"
                               class="sr-only"
                               onchange="document.getElementById('car-photo-form').submit()">

                        <p class="text-xs text-ink-300 mt-3 text-center">
                            Наведите на фото → клик «Сменить фото».
                            Поддерживаются jpg/png/webp до 5 МБ. Загружается мгновенно.
                        </p>
                        <x-input-error :messages="$errors->get('photo')" />
                    </form>
                </div>

                <div class="glass rounded-2xl p-6">
                    <div class="stat-label">Баланс авто</div>
                    <div class="mt-2 text-4xl font-display font-bold {{ (float) $car->balance >= 0 ? 'text-neon-lime' : 'text-neon-red' }} drop-shadow-[0_0_20px_rgba(194,255,69,0.30)]">
                        {{ number_format((float) $car->balance, 2, '.', ' ') }} <span class="opacity-70 text-2xl">₽</span>
                    </div>
                    <p class="text-xs text-ink-300 mt-2">Заработано от аренд и расходы (ремонты и т.п.)</p>
                    <button x-data @click="$dispatch('open-modal', 'car-tx-modal')" class="btn btn-primary w-full mt-4 justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Транзакция авто
                    </button>
                </div>

                <div class="glass rounded-2xl p-6">
                    <h3 class="text-sm font-display font-semibold mb-3 uppercase tracking-wider text-ink-200">История транзакций авто</h3>
                    @forelse($car->carTransactions as $t)
                        <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0">
                            @if($t->type->value === 'income')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm truncate">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">
                                    {{ $t->created_at->format('d.m.Y H:i') }}
                                    @if($t->rental_id)
                                        · <a href="{{ route('rentals.show', $t->rental_id) }}" class="text-neon-cyan hover:underline">аренда #{{ $t->rental_id }}</a>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'income' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-ink-300 py-2">Пока пусто.</div>
                    @endforelse
                </div>
            </div>

            {{-- Right: active rental + history + edit form --}}
            <div class="lg:col-span-2 flex flex-col gap-6">

                {{-- Active rental block --}}
                <section class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-display font-bold">Текущая аренда</h2>
                        @if(! $active)
                            <button x-data @click="$dispatch('open-modal', 'create-rental-modal')" class="btn btn-primary">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Оформить аренду
                            </button>
                        @endif
                    </div>

                    @if($active)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="stat-label">Арендатор</div>
                                <a href="{{ route('users.show', $active->user) }}" class="mt-1 inline-flex items-center gap-2 hover:text-neon-cyan transition">
                                    @if($active->user->photo)
                                        <img src="{{ $active->user->photo_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                                    @else
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                            {{ mb_substr($active->user->first_name, 0, 1) }}{{ mb_substr($active->user->last_name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="font-medium">{{ $active->user->full_name }}</span>
                                </a>
                                <div class="text-xs text-ink-300 font-mono">{{ '@'.$active->user->login }} @if($active->user->phone) · {{ $active->user->phone }}@endif</div>
                            </div>
                            <div>
                                <div class="stat-label">Тариф</div>
                                <div class="mt-1">
                                    {{ $active->tariff?->name ?? '—' }} <span class="text-ink-300">·</span>
                                    <span class="font-mono">{{ number_format((float) $active->amount, 2, '.', ' ') }} ₽</span>
                                    <span class="text-ink-300">·</span>
                                    {{ $active->period_count }} {{ $active->period->label() }}
                                </div>
                                <div class="text-xs text-ink-300">Депозит: {{ number_format((float) $active->deposit_amount, 2, '.', ' ') }} ₽</div>
                            </div>
                            <div>
                                <div class="stat-label">Статус</div>
                                <div class="mt-1"><span class="badge {{ $active->status->badgeClass() }}">{{ $active->status->label() }}</span></div>
                            </div>
                            <div>
                                <div class="stat-label">Следующее списание</div>
                                <div class="mt-1 font-mono text-neon-cyan">
                                    {{ $active->next_charge_at ? $active->next_charge_at->format('d.m.Y H:i') : '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-5 pt-4 border-t border-white/5">
                            <a href="{{ route('rentals.show', $active) }}" class="btn btn-ghost">Открыть профиль аренды</a>
                            @if($active->isOpen())
                                <form method="POST" action="{{ route('rentals.pause', $active) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost">Приостановить</button>
                                </form>
                            @elseif($active->isPaused())
                                <form method="POST" action="{{ route('rentals.resume', $active) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost">Возобновить</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('rentals.close', $active) }}" onsubmit="return confirm('Закрыть аренду? Действие нельзя отменить.')">
                                @csrf
                                <button type="submit" class="btn btn-danger ml-auto">Закрыть аренду</button>
                            </form>
                        </div>
                    @else
                        <p class="text-sm text-ink-300">Активных аренд по этому авто нет. Чтобы оформить новую — нажмите «Оформить аренду».</p>
                    @endif
                </section>

                {{-- Rental history of THIS car --}}
                @php
                    $historyRentals = $car->rentals->reject(fn ($r) => $active && $r->id === $active->id)->values();
                    $totalRentals = $car->rentals->count();
                    $closedCount = $car->rentals->where('status.value', 'closed')->count();
                    $totalEarned = $car->carTransactions->where('type.value', 'income')->sum('amount');
                @endphp
                <section class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <h2 class="text-lg font-display font-bold">История аренд этого авто</h2>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-ink-300">Всего аренд:</span>
                            <span class="text-neon-cyan font-semibold">{{ $totalRentals }}</span>
                            <span class="text-ink-300">·</span>
                            <span class="text-ink-300">Закрытых:</span>
                            <span class="text-ink-100 font-semibold">{{ $closedCount }}</span>
                            <span class="text-ink-300">·</span>
                            <span class="text-ink-300">Заработано:</span>
                            <span class="text-neon-lime font-semibold font-mono">{{ number_format((float) $totalEarned, 2, '.', ' ') }} ₽</span>
                        </div>
                    </div>

                    @if($historyRentals->isEmpty())
                        <div class="text-sm text-ink-300 py-6 text-center border border-dashed border-white/10 rounded-xl">
                            @if($active)
                                Кроме текущей аренды #{{ $active->id }} истории пока нет.
                            @else
                                По этому авто ещё не было ни одной аренды.
                            @endif
                        </div>
                    @else
                        <div class="overflow-x-auto -mx-6 px-6">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Арендатор</th>
                                        <th>Тариф</th>
                                        <th>Период</th>
                                        <th>Статус</th>
                                        <th class="text-right">Сумма</th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historyRentals as $r)
                                        <tr onclick="location.href='{{ route('rentals.show', $r) }}'" class="cursor-pointer group">
                                            <td class="font-mono text-xs">{{ $r->id }}</td>
                                            <td class="text-sm">
                                                <div class="font-medium group-hover:text-neon-cyan transition">{{ $r->user?->short_name ?? '—' }}</div>
                                                @if($r->user?->phone)
                                                    <div class="text-xs text-ink-300 font-mono">{{ $r->user->phone }}</div>
                                                @endif
                                            </td>
                                            <td class="text-sm text-ink-200">{{ $r->tariff?->name ?? '—' }}</td>
                                            <td class="text-xs text-ink-300">
                                                {{ $r->started_at?->format('d.m.Y H:i') }} →
                                                {{ optional($r->closed_at)->format('d.m.Y H:i') ?? '…' }}
                                            </td>
                                            <td><span class="badge {{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                                            <td class="text-right font-mono">{{ number_format((float) $r->amount, 2, '.', ' ') }} ₽</td>
                                            <td class="text-right pr-4"><svg class="w-4 h-4 inline text-ink-400 group-hover:text-neon-cyan transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                {{-- Edit car form --}}
                <form method="POST" action="{{ route('cars.update', $car) }}" enctype="multipart/form-data" class="glass rounded-2xl p-6 space-y-5">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-display font-bold">Данные авто</h2>

                    <div>
                        <x-input-label for="photo" :value="'Новое фото'" />
                        <input id="photo" type="file" name="photo" accept="image/*" class="text-sm text-ink-200 w-full">
                        <p class="text-xs text-ink-300 mt-1">jpg/png/webp, до 5 МБ.</p>
                        <x-input-error :messages="$errors->get('photo')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="brand" :value="'Марка *'" />
                            <x-text-input id="brand" type="text" name="brand" :value="old('brand', $car->brand)" required />
                            <x-input-error :messages="$errors->get('brand')" />
                        </div>
                        <div>
                            <x-input-label for="model" :value="'Модель *'" />
                            <x-text-input id="model" type="text" name="model" :value="old('model', $car->model)" required />
                            <x-input-error :messages="$errors->get('model')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <x-input-label for="year" :value="'Год выпуска'" />
                            <x-text-input id="year" type="number" name="year" :value="old('year', $car->year)" min="1980" max="{{ date('Y') }}" />
                            <x-input-error :messages="$errors->get('year')" />
                        </div>
                        <div>
                            <x-input-label for="license_plate" :value="'Номер авто *'" />
                            <x-text-input id="license_plate" type="text" name="license_plate" :value="old('license_plate', $car->license_plate)" required maxlength="32" />
                            <x-input-error :messages="$errors->get('license_plate')" />
                        </div>
                        <div>
                            <x-input-label for="balance" :value="'Баланс (₽)'" />
                            <x-text-input id="balance" type="number" step="0.01" name="balance" :value="old('balance', $car->balance)" />
                            <x-input-error :messages="$errors->get('balance')" />
                        </div>
                    </div>

                    <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                        <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Аккумулятор</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div>
                                <x-input-label for="battery_capacity" :value="'Ёмкость (Вт·ч)'" />
                                <x-text-input id="battery_capacity" type="number" name="battery_capacity" :value="old('battery_capacity', $car->battery_capacity)" min="0" />
                                <x-input-error :messages="$errors->get('battery_capacity')" />
                            </div>
                            <div>
                                <x-input-label for="battery_number" :value="'Серийный номер'" />
                                <x-text-input id="battery_number" type="text" name="battery_number" :value="old('battery_number', $car->battery_number)" />
                                <x-input-error :messages="$errors->get('battery_number')" />
                            </div>
                        </div>
                    </fieldset>

                    <div>
                        <x-input-label for="comment" :value="'Комментарий'" />
                        <textarea id="comment" name="comment" rows="3" maxlength="2000"
                                  class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">{{ old('comment', $car->comment) }}</textarea>
                        <x-input-error :messages="$errors->get('comment')" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <a href="{{ route('cars.index') }}" class="btn btn-ghost">Отмена</a>
                        <x-primary-button>Сохранить</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Car transaction modal --}}
    <x-modal name="car-tx-modal" maxWidth="md">
        <form method="POST" action="{{ route('cars.transactions.store', $car) }}">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-display font-bold mb-1">Транзакция авто</h3>
                <p class="text-sm text-ink-300 mb-5">Приход (заработок, ручное пополнение) или расход (ремонт, ТО). Влияет на баланс авто.</p>

                <div class="grid grid-cols-2 gap-2 mb-4" x-data="{ type: '{{ old('type','income') }}' }">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="income" x-model="type" class="sr-only">
                        <div class="rounded-xl px-3 py-3 text-center border transition"
                             :class="type==='income' ? 'border-neon-lime/60 bg-neon-lime/10 text-neon-lime shadow-glow-lime' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                            <div class="text-xl">+</div>
                            <div class="text-xs uppercase tracking-wider">Приход</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="expense" x-model="type" class="sr-only">
                        <div class="rounded-xl px-3 py-3 text-center border transition"
                             :class="type==='expense' ? 'border-neon-red/60 bg-neon-red/10 text-neon-red shadow-glow-red' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                            <div class="text-xl">−</div>
                            <div class="text-xs uppercase tracking-wider">Расход</div>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('type')" />

                <div class="mb-4">
                    <x-input-label for="ct_amount" :value="'Сумма (₽)'" />
                    <x-text-input id="ct_amount" type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" />
                    <x-input-error :messages="$errors->get('amount')" />
                </div>

                <div class="mb-4">
                    <x-input-label for="ct_comment" :value="'Комментарий'" />
                    <textarea id="ct_comment" name="comment" rows="3" maxlength="1000"
                              class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan"
                              placeholder="Например: замена аккумулятора, плановое ТО">{{ old('comment') }}</textarea>
                    <x-input-error :messages="$errors->get('comment')" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-white/5">
                <button type="button" x-on:click="$dispatch('close')" class="btn btn-ghost">Отмена</button>
                <x-primary-button>Провести</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Create rental modal --}}
    @if(! $active)
        <x-modal name="create-rental-modal" maxWidth="xl">
            <form method="POST" action="{{ route('cars.rentals.store', $car) }}"
                  x-data="userPicker()" x-init="init()">
                @csrf
                <div class="p-6 space-y-5">
                    <div>
                        <h3 class="text-lg font-display font-bold mb-1">Оформление аренды</h3>
                        <p class="text-sm text-ink-300">Авто: <span class="text-ink-100 font-medium">{{ $car->display_name }}</span> · {{ $car->license_plate }}</p>
                    </div>

                    {{-- User search picker --}}
                    <div>
                        <x-input-label :value="'Арендатор *'" />
                        <input type="hidden" name="user_id" :value="selected?.id || ''">

                        <div x-show="!selected" x-cloak>
                            <div class="relative">
                                <input type="text" x-model="query" @input="search()" @focus="open = true"
                                       placeholder="Поиск по ФИО или телефону…"
                                       class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 pl-9 focus:border-neon-cyan focus:ring-neon-cyan">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>

                                {{-- Dropdown is absolutely positioned so it does NOT push the tariff select below it --}}
                                <div x-show="open && results.length > 0"
                                     @click.outside="open = false"
                                     x-cloak
                                     class="absolute left-0 right-0 top-full mt-1 z-[60] max-h-72 overflow-y-auto rounded-xl border border-white/10 bg-ink-900/95 backdrop-blur-xl shadow-2xl divide-y divide-white/5">
                                    <template x-for="u in results" :key="u.id">
                                        <button type="button" @click="pick(u)"
                                                class="w-full text-left flex items-center gap-3 px-3 py-2.5 hover:bg-white/5 transition">
                                            <template x-if="u.photo_url">
                                                <img :src="u.photo_url" class="w-8 h-8 rounded-full object-cover">
                                            </template>
                                            <template x-if="!u.photo_url">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white"
                                                     style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                                    <span x-text="u.initials"></span>
                                                </div>
                                            </template>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium truncate" x-text="u.full_name"></div>
                                                <div class="text-xs text-ink-300 truncate">
                                                    <span x-text="'@' + u.login"></span>
                                                    <template x-if="u.phone"><span> · <span x-text="u.phone"></span></span></template>
                                                </div>
                                            </div>
                                            <span class="badge shrink-0" :class="u.role === 'admin' ? 'badge-admin' : 'badge-driver'" x-text="u.role"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p x-show="open && !loading && query && results.length === 0" class="text-xs text-ink-300 mt-2">Ничего не нашлось.</p>
                            <p x-show="loading" x-cloak class="text-xs text-ink-300 mt-2">Ищу…</p>
                        </div>

                        <div x-show="selected" x-cloak class="flex items-center gap-3 rounded-xl border border-neon-cyan/30 bg-neon-cyan/5 px-3 py-2.5">
                            <template x-if="selected?.photo_url">
                                <img :src="selected.photo_url" class="w-8 h-8 rounded-full object-cover">
                            </template>
                            <template x-if="!selected?.photo_url">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white"
                                     style="background-image: linear-gradient(135deg, #00d4ff, #a855f7);">
                                    <span x-text="selected?.initials"></span>
                                </div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate" x-text="selected?.full_name"></div>
                                <div class="text-xs text-ink-300 truncate">
                                    <span x-text="'@' + selected?.login"></span>
                                    <template x-if="selected?.phone"><span> · <span x-text="selected?.phone"></span></span></template>
                                </div>
                            </div>
                            <button type="button" @click="selected = null; query = ''" class="text-ink-300 hover:text-neon-red p-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <x-input-error :messages="$errors->get('user_id')" />
                    </div>

                    <div x-data='{ tariffId: "{{ old('tariff_id') }}", tariffs: @json($tariffs->keyBy('id')) }'>
                        <x-input-label for="tariff_id" :value="'Тариф *'" />
                        <select id="tariff_id" name="tariff_id" required x-model="tariffId"
                                class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">
                            <option value="">— выберите —</option>
                            @forelse($tariffs as $t)
                                <option value="{{ $t->id }}" @selected(old('tariff_id') == $t->id)>
                                    {{ $t->name }} · {{ number_format((float) $t->amount, 2, '.', ' ') }} ₽ / {{ $t->period_count }} {{ $t->period->label() }}@if($t->is_buyout) · РАСКАТ{{ $t->buyout_price ? ' '.number_format((float)$t->buyout_price,0,'.',' ').' ₽ за '.$t->buyout_days.' дн.' : '' }}@elseif((float) $t->deposit_amount > 0) · депозит {{ number_format((float) $t->deposit_amount, 2, '.', ' ') }} ₽@endif
                                </option>
                            @empty
                                <option value="" disabled>Нет активных тарифов</option>
                            @endforelse
                        </select>
                        @if($tariffs->isEmpty())
                            <p class="text-xs text-neon-red mt-1">Нет ни одного активного тарифа. <a href="{{ route('tariffs.create') }}" class="underline">Создать</a>.</p>
                        @endif
                        <x-input-error :messages="$errors->get('tariff_id')" />

                        {{-- Buyout preview when a buyout tariff is selected --}}
                        <div x-show="tariffId && tariffs[tariffId] && tariffs[tariffId].is_buyout" x-cloak
                             class="mt-3 rounded-xl border border-neon-violet/30 bg-neon-violet/10 p-3 text-xs">
                            <div class="font-semibold text-neon-violet uppercase tracking-wider mb-1">⚡ Раскат на выкуп</div>
                            <div class="text-ink-100">
                                Авто перейдёт в собственность арендатора после
                                <template x-if="tariffs[tariffId]">
                                    <span><span class="font-mono text-neon-cyan" x-text="tariffs[tariffId]?.buyout_days"></span> платежей на сумму <span class="font-mono text-neon-violet" x-text="parseFloat(tariffs[tariffId]?.buyout_price || 0).toLocaleString('ru-RU', {minimumFractionDigits: 2})"></span> ₽.</span>
                                </template>
                                После полной выплаты аренда автоматически закрывается со статусом «выкуплено».
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="comment" :value="'Комментарий'" />
                        <textarea id="comment" name="comment" rows="2" maxlength="2000"
                                  class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">{{ old('comment') }}</textarea>
                        <p class="text-xs text-ink-300 mt-1">Аренда стартует прямо сейчас — первое периодическое списание произойдёт через один период.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-white/5">
                    <button type="button" x-on:click="$dispatch('close')" class="btn btn-ghost">Отмена</button>
                    <x-primary-button>Создать аренду</x-primary-button>
                </div>
            </form>
        </x-modal>

        <script>
        function userPicker() {
            return {
                query: '',
                results: [],
                selected: null,
                loading: false,
                open: false,
                debounceTimer: null,
                init() {
                    // Pre-load on first open
                    this.search();
                },
                async search() {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(async () => {
                        this.loading = true;
                        try {
                            const r = await fetch('/users/search.json?q=' + encodeURIComponent(this.query), {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });
                            this.results = await r.json();
                            this.open = true;
                        } catch (e) {
                            this.results = [];
                        } finally {
                            this.loading = false;
                        }
                    }, 250);
                },
                pick(u) {
                    this.selected = u;
                    this.query = '';
                    this.results = [];
                    this.open = false;
                }
            };
        }
        </script>
    @endif
</x-app-layout>
