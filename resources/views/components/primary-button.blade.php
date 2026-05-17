<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary rounded-pill px-4']) }}>
    {{ $slot }}
</button>
