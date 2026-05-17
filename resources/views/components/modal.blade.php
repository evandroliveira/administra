@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => '360px',
    'md' => '520px',
    'lg' => '720px',
    'xl' => '920px',
    '2xl' => '1140px',
][$maxWidth] ?? '1140px';
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    class="position-fixed top-0 start-0 w-100 h-100 overflow-auto p-4"
    style="z-index: 1055; display: {{ $show ? 'block' : 'none' }};"
>
    <div
        x-show="show"
        x-transition.opacity.duration.200ms
        class="position-fixed top-0 start-0 w-100 h-100 bg-dark"
        x-on:click="show = false"
        style="opacity: 0.55;"
    ></div>

    <div class="d-flex min-vh-100 align-items-center justify-content-center">
        <div
            x-show="show"
            x-transition.opacity.duration.200ms
            class="bg-white rounded-4 shadow-lg overflow-hidden w-100"
            style="max-width: {{ $maxWidth }};"
        >
            {{ $slot }}
        </div>
    </div>
</div>
