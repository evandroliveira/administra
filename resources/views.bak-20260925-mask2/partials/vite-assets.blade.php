@php
    $viteManifestPath = public_path('build/manifest.json');
    $viteHotPath = public_path('hot');
@endphp

@if (is_file($viteManifestPath) || is_file($viteHotPath))
    {!! app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']) !!}
@endif