<x-app-layout>
    @section('title', 'Новое авто')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="mb-6">
            <a href="{{ route('cars.index') }}" class="text-xs text-ink-300 hover:text-neon-cyan inline-flex items-center gap-1 mb-1">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                к списку
            </a>
            <h1 class="text-3xl font-display font-bold tracking-tight">Добавить авто</h1>
            <p class="text-ink-300 text-sm">Электровелосипед в парк проката</p>
        </div>

        <form method="POST" action="{{ route('cars.store') }}" enctype="multipart/form-data" class="glass rounded-2xl p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="brand" :value="'Марка *'" />
                    <x-text-input id="brand" type="text" name="brand" :value="old('brand')" required autofocus />
                    <x-input-error :messages="$errors->get('brand')" />
                </div>
                <div>
                    <x-input-label for="model" :value="'Модель *'" />
                    <x-text-input id="model" type="text" name="model" :value="old('model')" required />
                    <x-input-error :messages="$errors->get('model')" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="year" :value="'Год выпуска'" />
                    <x-text-input id="year" type="number" name="year" :value="old('year')" min="1980" max="{{ date('Y') }}" />
                    <x-input-error :messages="$errors->get('year')" />
                </div>
                <div>
                    <x-input-label for="license_plate" :value="'Номер авто *'" />
                    <x-text-input id="license_plate" type="text" name="license_plate" :value="old('license_plate')" required maxlength="32" placeholder="A001AA77" />
                    <x-input-error :messages="$errors->get('license_plate')" />
                </div>
                <div>
                    <x-input-label for="balance" :value="'Баланс авто (₽)'" />
                    <x-text-input id="balance" type="number" step="0.01" name="balance" :value="old('balance', 0)" />
                    <x-input-error :messages="$errors->get('balance')" />
                </div>
            </div>

            <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Закуп (для юнит-экономики)</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    <div>
                        <x-input-label for="purchase_price" :value="'Стоимость закупа (₽)'" />
                        <x-text-input id="purchase_price" type="number" step="0.01" min="0" name="purchase_price" :value="old('purchase_price')" placeholder="например 60000.00" />
                        <p class="text-xs text-ink-300 mt-1">Используется в расчёте ROI / окупаемости.</p>
                        <x-input-error :messages="$errors->get('purchase_price')" />
                    </div>
                    <div>
                        <x-input-label for="purchase_date" :value="'Дата покупки'" />
                        <x-text-input id="purchase_date" type="date" name="purchase_date" :value="old('purchase_date')" :max="date('Y-m-d')" />
                        <x-input-error :messages="$errors->get('purchase_date')" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="rounded-xl border border-white/8 px-4 py-3">
                <legend class="px-2 text-xs uppercase tracking-[0.18em] text-ink-300">Идентификаторы</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    <div>
                        <x-input-label for="frame_number" :value="'Номер рамы'" />
                        <x-text-input id="frame_number" type="text" name="frame_number" :value="old('frame_number')" maxlength="100" placeholder="напр. JL20250500125" />
                        <x-input-error :messages="$errors->get('frame_number')" />
                    </div>
                    <div>
                        <x-input-label for="battery_capacity" :value="'Ёмкость аккумулятора'" />
                        <x-text-input id="battery_capacity" type="text" name="battery_capacity" :value="old('battery_capacity')" maxlength="50" placeholder="напр. 60/45" />
                        <x-input-error :messages="$errors->get('battery_capacity')" />
                    </div>
                    <div>
                        <x-input-label for="battery_number" :value="'Номер аккумулятора'" />
                        <x-text-input id="battery_number" type="text" name="battery_number" :value="old('battery_number')" maxlength="100" />
                        <x-input-error :messages="$errors->get('battery_number')" />
                    </div>
                </div>
            </fieldset>

            <div>
                <x-input-label for="photo" :value="'Фото авто'" />
                <input id="photo" type="file" name="photo" accept="image/*" class="text-sm text-ink-200 w-full">
                <x-input-error :messages="$errors->get('photo')" />
            </div>

            <div>
                <x-input-label for="comment" :value="'Комментарий'" />
                <textarea id="comment" name="comment" rows="3" maxlength="2000"
                          class="block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 focus:border-neon-cyan focus:ring-neon-cyan"
                          placeholder="Особенности, состояние, история">{{ old('comment') }}</textarea>
                <x-input-error :messages="$errors->get('comment')" />
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('cars.index') }}" class="btn btn-ghost">Отмена</a>
                <x-primary-button>Добавить авто</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
