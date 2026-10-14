<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-light border rounded-pill px-4']) }}>
    {{ $slot }}
</button>
