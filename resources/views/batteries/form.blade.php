@php $isEdit = $battery->exists; @endphp
<x-app-layout>
    @section('title', $isEdit ? 'АКБ '.$battery->vin : 'Новая АКБ')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6">
            <a href="{{ route('batteries.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                к списку АКБ
            </a>
            <h1 class="text-3xl font-display font-bold tracking-tight">{{ $isEdit ? 'Редактировать АКБ' : 'Добавить АКБ' }}</h1>
        </div>

        <form method="POST" action="{{ $isEdit ? route('batteries.update', $battery) : route('batteries.store') }}" class="glass rounded-2xl p-6 space-y-5">
            @csrf
            @if($isEdit) @method('PATCH') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="callsign" :value="'Позывной'" />
                    <x-text-input id="callsign" type="text" name="callsign" :value="old('callsign', $battery->callsign)" maxlength="50" placeholder="напр. 001 / В1" />
                    <x-input-error :messages="$errors->get('callsign')" />
                </div>
                <div>
                    <x-input-label for="capacity" :value="'Ёмкость'" />
                    <x-text-input id="capacity" type="text" name="capacity" :value="old('capacity', $battery->capacity)" maxlength="50" placeholder="напр. 60/45" />
                    <x-input-error :messages="$errors->get('capacity')" />
                </div>
            </div>

            <div>
                <x-input-label for="car_model" :value="'Для какой модели *'" />
                <input id="car_model" type="text" name="car_model" value="{{ old('car_model', $battery->car_model) }}" list="car-models" required maxlength="100"
                       class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan" placeholder="напр. Kugoo U5">
                <datalist id="car-models">
                    @foreach($models as $m)<option value="{{ $m }}">@endforeach
                </datalist>
                <p class="text-xs text-ink-300 mt-1">Совместимость: эта АКБ будет предлагаться при аренде авто такой модели.</p>
                <x-input-error :messages="$errors->get('car_model')" />
            </div>

            <div>
                <x-input-label for="vin" :value="'ВИН-номер *'" />
                <x-text-input id="vin" type="text" name="vin" :value="old('vin', $battery->vin)" required maxlength="150" placeholder="напр. JS/TJ/0033/LW/..." />
                <p class="text-xs text-ink-300 mt-1">Уникальный номер батареи.</p>
                <x-input-error :messages="$errors->get('vin')" />
            </div>

            <div>
                <x-input-label for="comment" :value="'Комментарий'" />
                <textarea id="comment" name="comment" rows="2" maxlength="500"
                          class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan">{{ old('comment', $battery->comment) }}</textarea>
                <x-input-error :messages="$errors->get('comment')" />
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('batteries.index') }}" class="btn btn-ghost">Отмена</a>
                <x-primary-button>{{ $isEdit ? 'Сохранить' : 'Добавить' }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
