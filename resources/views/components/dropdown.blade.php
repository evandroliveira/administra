@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'dropdown-menu d-block position-static border-0 shadow-sm rounded-4 p-2 m-0'])

@php
$alignmentClasses = match ($align) {
    'left' => 'start-0',
    'top' => 'start-50 translate-middle-x',
    default => 'end-0',
};

$panelWidth = is_numeric($width)
    ? rtrim(rtrim(number_format(((float) $width) / 4, 2, '.', ''), '0'), '.') . 'rem'
    : $width;
@endphp

<div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition.opacity.duration.200ms
            class="position-absolute mt-2 {{ $alignmentClasses }}"
            style="display: none; z-index: 1050; min-width: {{ $panelWidth }};"
            @click="open = false">
        <div class="{{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
