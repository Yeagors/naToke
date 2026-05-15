@props(['type' => 'submit'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'btn btn-primary']) }}>
    {{ $slot }}
</button>
