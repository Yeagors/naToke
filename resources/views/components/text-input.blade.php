@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-xl border-white/10 bg-ink-800/70 text-ink-100 placeholder-ink-300/60 shadow-inner-glow focus:border-neon-cyan focus:ring-neon-cyan']) }}>
