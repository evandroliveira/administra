<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger rounded-pill px-4']) }}>
    {{ $slot }}
</button>
