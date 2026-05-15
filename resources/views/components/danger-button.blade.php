@props(['type' => 'submit'])

<button {{ $attributes->merge(['type' => $type, 'class' => 'btn btn-danger']) }}>
    {{ $slot }}
</button>
