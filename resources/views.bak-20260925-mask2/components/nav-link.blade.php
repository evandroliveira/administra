@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-link rounded-pill px-3 py-2 fw-semibold text-primary bg-primary-subtle active'
            : 'nav-link rounded-pill px-3 py-2 text-body-secondary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
