@props([
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => '— выберите —',
    'required' => false,
    'id' => null,
    'disabled' => false,
])
@php
    $id = $id ?? $name;
    // Normalize options to objects with value/label/hint keys.
    // Accept either ['key' => 'Label'] or [['value' => x, 'label' => y, 'hint' => z]]
    $normalized = collect($options)
        ->map(function ($opt, $key) {
            if (is_array($opt)) {
                return [
                    'value' => (string) ($opt['value'] ?? $key),
                    'label' => (string) ($opt['label'] ?? $opt['value'] ?? $key),
                    'hint' => isset($opt['hint']) ? (string) $opt['hint'] : null,
                ];
            }
            return [
                'value' => (string) $key,
                'label' => (string) $opt,
                'hint' => null,
            ];
        })
        ->values()
        ->all();
    $currentValue = (string) old($name, $value ?? '');
@endphp

<div x-data="{
        open: false,
        value: @js($currentValue),
        options: @js($normalized),
        get selected() { return this.options.find(o => String(o.value) === String(this.value)); },
        get currentLabel() { return this.selected?.label ?? @js($placeholder); },
        get currentHint()  { return this.selected?.hint ?? ''; },
        pick(v) {
            const old = String(this.value);
            this.value = String(v);
            this.open = false;
            this.$el.dispatchEvent(new CustomEvent('select-changed', {
                detail: { name: @js($name), value: String(v), old },
                bubbles: true
            }));
        }
     }"
     @click.outside="open = false"
     @keydown.escape.window="open = false"
     class="relative">

    {{-- Hidden field that participates in form submission --}}
    <input type="hidden" name="{{ $name }}" id="{{ $id }}_hidden" :value="value" @if($required) required @endif>

    {{-- Trigger button styled like other inputs --}}
    <button type="button" id="{{ $id }}"
            @click="if (!{{ $disabled ? 'true' : 'false' }}) open = !open"
            @if($disabled) disabled @endif
            class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl border bg-ink-800/70 text-left transition-all duration-150
                   border-white/10 hover:border-white/20
                   {{ $disabled ? 'opacity-55 cursor-not-allowed' : 'cursor-pointer' }}
                   focus:border-neon-cyan focus:ring-2 focus:ring-neon-cyan/30 focus:outline-none">
        <span class="flex-1 min-w-0">
            <span class="block truncate text-ink-100" :class="!value && 'text-ink-300/70'" x-text="currentLabel"></span>
            <span x-show="currentHint" x-cloak class="block text-[11px] text-ink-300 truncate" x-text="currentHint"></span>
        </span>
        <svg class="w-4 h-4 text-neon-cyan flex-shrink-0 transition-transform duration-200" :class="open && 'rotate-180'"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    {{-- Dropdown panel — styled like the rest of the dark theme --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute left-0 right-0 top-full mt-1.5 z-[55] max-h-80 overflow-y-auto rounded-xl border border-white/10 bg-ink-900/95 backdrop-blur-xl shadow-[0_20px_60px_-10px_rgba(0,0,0,0.7)] divide-y divide-white/5">
        <template x-for="opt in options" :key="opt.value">
            <button type="button"
                    @click="pick(opt.value)"
                    class="w-full text-left px-3.5 py-2.5 flex items-start gap-2 transition-colors duration-100"
                    :class="String(value) === String(opt.value)
                        ? 'bg-neon-cyan/10 text-neon-cyan'
                        : 'text-ink-100 hover:bg-white/5 hover:text-neon-cyan'">
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-medium" x-text="opt.label"></span>
                    <span x-show="opt.hint" class="block text-[11px] text-ink-300 mt-0.5" x-text="opt.hint"></span>
                </span>
                <svg x-show="String(value) === String(opt.value)" x-cloak
                     class="w-4 h-4 text-neon-cyan flex-shrink-0 mt-0.5"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </template>
        <template x-if="options.length === 0">
            <div class="px-3.5 py-3 text-sm text-ink-300 text-center">Нет доступных вариантов</div>
        </template>
    </div>
</div>
