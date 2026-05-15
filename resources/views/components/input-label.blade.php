@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-[0.14em] text-ink-300 mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
