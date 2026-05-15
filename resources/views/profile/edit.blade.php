<x-app-layout>
    @section('title', 'Профиль')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <div class="text-xs uppercase tracking-[0.32em] text-ink-300">Профиль</div>
                <h1 class="text-3xl font-display font-bold tracking-tight">{{ $user->full_name }}</h1>
            </div>
            @if($user->isAdmin())
                <span class="badge badge-admin self-start sm:self-auto">admin · полный доступ</span>
            @else
                <span class="badge badge-driver self-start sm:self-auto">driver</span>
            @endif
        </div>

        @if(session('status') && is_string(session('status')) && session('status') !== 'profile-updated' && session('status') !== 'password-updated')
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                {{ session('status') }}
            </div>
        @endif
        @if(session('status') === 'profile-updated')
            <div class="mb-6 px-4 py-2.5 rounded-xl text-sm text-neon-lime bg-neon-lime/10 border border-neon-lime/30">
                Профиль обновлён.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: avatar + balance + actions --}}
            <div class="lg:col-span-1 flex flex-col gap-6">
                {{-- Avatar card --}}
                <div class="glass rounded-2xl p-6 text-center">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="photo-form">
                        @csrf
                        @method('PATCH')
                        {{-- Hidden current fields to preserve them when only updating photo --}}
                        @foreach(['login','last_name','first_name','middle_name','birth_date','passport_series','passport_number','passport_issued_by','passport_issued_at','passport_department_code'] as $f)
                            @php $v = old($f, optional($user->{$f})->format ? $user->{$f}->format('Y-m-d') : $user->{$f}); @endphp
                            <input type="hidden" name="{{ $f }}" value="{{ $v }}">
                        @endforeach

                        <div class="relative inline-block group">
                            @if($user->photo)
                                <img src="{{ $user->photo_url }}" class="w-32 h-32 rounded-full object-cover ring-2 ring-white/10 shadow-glow-cyan" alt="">
                            @else
                                <div class="w-32 h-32 rounded-full flex items-center justify-center text-4xl font-display font-bold text-white shadow-glow-violet"
                                     style="background-image: linear-gradient(135deg, #00d4ff, #a855f7, #ec4899);">
                                    {{ mb_substr($user->first_name, 0, 1) }}{{ mb_substr($user->last_name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="mt-4">
                            <input type="file" name="photo" id="photo" accept="image/*" class="text-sm text-ink-200 w-full" onchange="document.getElementById('photo-form').submit()">
                            <x-input-error :messages="$errors->get('photo')" />
                        </div>
                    </form>
                </div>

                {{-- Balance card --}}
                <div class="glass rounded-2xl p-6">
                    <div class="stat-label">Текущий баланс</div>
                    <div class="mt-2 text-4xl font-display font-bold text-neon-lime drop-shadow-[0_0_20px_rgba(194,255,69,0.35)]">
                        {{ number_format((float) $user->balance, 2, '.', ' ') }} <span class="text-neon-lime/70 text-2xl">₽</span>
                    </div>
                    @if($user->isAdmin())
                        <button x-data @click="$dispatch('open-modal', 'balance-modal')" class="btn btn-primary w-full mt-4 justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Пополнить / списать
                        </button>
                    @else
                        <p class="text-xs text-ink-300 mt-3">Изменение баланса доступно только администратору.</p>
                    @endif
                </div>

                {{-- Recent transactions --}}
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

            {{-- Right: info form --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>

    {{-- Balance modal --}}
    @if($user->isAdmin())
        <x-modal name="balance-modal" maxWidth="md">
            <form method="POST" action="{{ route('transactions.store', $user) }}">
                @csrf
                <div class="p-6">
                    <h3 class="text-lg font-display font-bold mb-1">Транзакция по балансу</h3>
                    <p class="text-sm text-ink-300 mb-5">Операция мгновенно изменит баланс пользователя {{ $user->short_name }} и оставит запись в истории.</p>

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
    @endif
</x-app-layout>
