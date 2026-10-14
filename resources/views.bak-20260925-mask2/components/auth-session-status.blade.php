@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success rounded-4 mb-0']) }}>
        {{ $status }}
    </div>
@endif
