@props(['active'])

@php
$classes = ($active ?? false)
            ? 'd-block w-100 rounded-3 px-3 py-2 text-decoration-none fw-semibold bg-primary-subtle text-primary active'
            : 'd-block w-100 rounded-3 px-3 py-2 text-decoration-none text-body-secondary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
