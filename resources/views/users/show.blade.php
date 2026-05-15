<x-app-layout>
    @section('title', $user->full_name)

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <a href="{{ route('users.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    к списку пользователей
                </a>
                <h1 class="text-3xl font-display font-bold tracking-tight">{{ $user->full_name }}</h1>
                <p class="text-ink-300 text-sm">{{ '@'.$user->login }} · id {{ $user->id }}</p>
            </div>
            @if($user->isAdmin())
                <span class="badge badge-admin self-start sm:self-auto">admin · полный доступ</span>
            @else
                <span class="badge badge-driver self-start sm:self-auto">driver</span>
            @endif
        </div>

        @if(session('status'))
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: avatar + balance + history --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                <div class="glass rounded-2xl p-6 text-center">
                    @if($user->photo)
                        <img src="{{ $user->photo_url }}" class="w-32 h-32 rounded-full object-cover ring-2 ring-white/10 shadow-glow-cyan mx-auto" alt="">
                    @else
                        <div class="w-32 h-32 mx-auto rounded-full flex items-center justify-center text-4xl font-display font-bold text-white shadow-glow-violet"
                             style="background-image: linear-gradient(135deg, #00d4ff, #a855f7, #ec4899);">
                            {{ mb_substr($user->first_name, 0, 1) }}{{ mb_substr($user->last_name, 0, 1) }}
                        </div>
                    @endif
                    <div class="mt-3 text-sm text-ink-300">Сменить фото — в форме справа</div>
                </div>

                <div class="glass rounded-2xl p-6">
                    <div class="stat-label">Баланс пользователя</div>
                    <div class="mt-2 text-4xl font-display font-bold {{ (float) $user->balance >= 0 ? 'text-neon-lime' : 'text-neon-red' }} drop-shadow-[0_0_20px_rgba(194,255,69,0.35)]">
                        {{ number_format((float) $user->balance, 2, '.', ' ') }} <span class="opacity-70 text-2xl">₽</span>
                    </div>
                    <button x-data @click="$dispatch('open-modal', 'balance-modal')" class="btn btn-primary w-full mt-4 justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Пополнить / списать
                    </button>
                </div>

                <div class="glass rounded-2xl p-6">
                    <h3 class="text-sm font-display font-semibold mb-3 uppercase tracking-wider text-ink-200">Последние транзакции</h3>
                    @forelse($user->transactions as $t)
                        <div class="flex items-center gap-3 py-2 border-b border-white/5 last:border-b-0">
                            @if($t->type->value === 'deposit')
                                <span class="badge badge-deposit">+</span>
                            @else
                                <span class="badge badge-withdrawal">−</span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm truncate">{{ $t->comment ?: $t->type->label() }}</div>
                                <div class="text-xs text-ink-300">{{ $t->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                            <div class="text-sm font-mono font-semibold {{ $t->type->value === 'deposit' ? 'text-neon-lime' : 'text-neon-red' }}">
                                {{ $t->signed_amount }} ₽
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-ink-300 py-2">Пока пусто.</div>
                    @endforelse
                </div>
            </div>

            {{-- Right: edit form --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" class="glass rounded-2xl p-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    <header class="mb-1">
                        <h2 class="text-lg font-display font-bold">Данные пользователя</h2>
                        <p class="text-sm text-ink-300">Редактирование доступно администратору. Все изменения логируются.</p>
                    </header>

                    <div>
                        <x-input-label for="photo" :value="'Фото профиля'" />
                        <input id="photo" type="file" name="photo" accept="image/*" class="text-sm text-ink-200 w-full">
                        <p class="text-xs text-ink-300 mt-1">jpg/png/webp, до 5 МБ.</p>
                        <x-input-error :messages="$errors->get('photo')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <x-input-label for="last_name" :value="'Фамилия *'" />
                            <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                            <x-input-error :messages="$errors->get('last_name')" />
                        </div>
                        <div>
                            <x-input-label for="first_name" :value="'Имя *'" />
                            <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                            <x-input-error :messages="$errors->get('first_name')" />
                        </div>
                        <div>
                            <x-input-label for="middle_name" :value="'Отчество'" />
                            <x-text-input id="middle_name" type="text" name="middle_name" :value="old('middle_name', $user->middle_name)" />
                            <x-input-error :messages="$errors->get('middle_name')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <x-input-label for="birth_date" :value="'Дата рождения'" />
                            <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date', optional($user->birth_date)->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('birth_date')" />
                        </div>
                        <div>
                            <x-input-label for="login" :value="'Логин *'" />
                            <x-text-input id="login" type="text" name="login" :value="old('login', $user->login)" required />
                            <x-input-error :messages="$errors->get('login')" />
                        </div>
                        <div>
                            <x-input-label for="role" :value="'Уровень доступов *'" />
                            <select id="role" name="role" required
                                    class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">
                                @foreach($roles as $r)
                                    <option value="{{ $r->value }}" @selected(old('role', $user->role->value) === $r->value)>
                                        {{ $r->label() }} ({{ $r->value }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" />
                        </div>
                    </div>

                    <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                        <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Паспортные данные</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div>
                                <x-input-label for="passport_series" :value="'Серия'" />
                                <x-text-input id="passport_series" type="text" name="passport_series" :value="old('passport_series', $user->passport_series)" maxlength="10" />
                                <x-input-error :messages="$errors->get('passport_series')" />
                            </div>
                            <div>
                                <x-input-label for="passport_number" :value="'Номер'" />
                                <x-text-input id="passport_number" type="text" name="passport_number" :value="old('passport_number', $user->passport_number)" maxlength="20" />
                                <x-input-error :messages="$errors->get('passport_number')" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="passport_issued_by" :value="'Кем выдан'" />
                                <x-text-input id="passport_issued_by" type="text" name="passport_issued_by" :value="old('passport_issued_by', $user->passport_issued_by)" />
                                <x-input-error :messages="$errors->get('passport_issued_by')" />
                            </div>
                            <div>
                                <x-input-label for="passport_issued_at" :value="'Когда выдан'" />
                                <x-text-input id="passport_issued_at" type="date" name="passport_issued_at" :value="old('passport_issued_at', optional($user->passport_issued_at)->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('passport_issued_at')" />
                            </div>
                            <div>
                                <x-input-label for="passport_department_code" :value="'Код подразделения'" />
                                <x-text-input id="passport_department_code" type="text" name="passport_department_code" :value="old('passport_department_code', $user->passport_department_code)" maxlength="10" />
                                <x-input-error :messages="$errors->get('passport_department_code')" />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                        <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Сбросить пароль (опционально)</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div>
                                <x-input-label for="password" :value="'Новый пароль'" />
                                <x-text-input id="password" type="password" name="password" autocomplete="new-password" placeholder="оставьте пустым, чтобы не менять" />
                                <x-input-error :messages="$errors->get('password')" />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" :value="'Повторите пароль'" />
                                <x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" />
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <a href="{{ route('users.index') }}" class="btn btn-ghost">Отмена</a>
                        <x-primary-button>Сохранить изменения</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Balance modal --}}
    <x-modal name="balance-modal" maxWidth="md">
        <form method="POST" action="{{ route('transactions.store', $user) }}">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-display font-bold mb-1">Транзакция по балансу</h3>
                <p class="text-sm text-ink-300 mb-5">Операция мгновенно изменит баланс пользователя <span class="text-ink-100 font-semibold">{{ $user->short_name }}</span> и оставит запись в истории.</p>

                <div class="grid grid-cols-2 gap-2 mb-4" x-data="{ type: '{{ old('type','deposit') }}' }">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="deposit" x-model="type" class="sr-only">
                        <div class="rounded-xl px-3 py-3 text-center border transition"
                             :class="type==='deposit' ? 'border-neon-lime/60 bg-neon-lime/10 text-neon-lime shadow-glow-lime' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                            <div class="text-xl">+</div>
                            <div class="text-xs uppercase tracking-wider">Пополнение</div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="withdrawal" x-model="type" class="sr-only">
                        <div class="rounded-xl px-3 py-3 text-center border transition"
                             :class="type==='withdrawal' ? 'border-neon-red/60 bg-neon-red/10 text-neon-red shadow-glow-red' : 'border-white/10 bg-white/5 text-ink-200 hover:border-white/20'">
                            <div class="text-xl">−</div>
                            <div class="text-xs uppercase tracking-wider">Списание</div>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('type')" />

                <div class="mb-4">
                    <x-input-label for="amount" :value="'Сумма (₽)'" />
                    <x-text-input id="amount" type="number" step="0.01" min="0.01" name="amount" :value="old('amount')" required placeholder="0.00" />
                    <x-input-error :messages="$errors->get('amount')" />
                </div>

                <div class="mb-4">
                    <x-input-label for="comment" :value="'Комментарий'" />
                    <textarea id="comment" name="comment" rows="3" maxlength="1000"
                              class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 placeholder-ink-300/60 focus:border-neon-cyan focus:ring-neon-cyan"
                              placeholder="Причина / источник">{{ old('comment') }}</textarea>
                    <x-input-error :messages="$errors->get('comment')" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-white/5">
                <button type="button" x-on:click="$dispatch('close')" class="btn btn-ghost">Отмена</button>
                <x-primary-button>Провести</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
