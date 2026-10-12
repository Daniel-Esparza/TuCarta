{{--
    Botón de TuCarta.
    Uso:
        <x-button>[ ENVIAR ]</x-button>                          botón principal (oscuro)
        <x-button variant="terracotta">[+]</x-button>            énfasis
        <x-button variant="teal" href="/carrito">CARRITO</x-button>   acción de carrito (si lleva href, es un enlace)
        <x-button variant="outline">[ VOLVER ]</x-button>        secundario
    Sin emojis: usa texto, corchetes o SVG inline.
--}}
@props(['variant' => 'dark', 'href' => null])

@php
    $variants = [
        'dark'       => 'bg-brand-dark text-white hover:bg-brand-darkHover',
        'terracotta' => 'bg-brand-terracotta text-white hover:bg-brand-terracottaHover',
        'teal'       => 'bg-brand-teal text-white hover:bg-brand-tealHover',
        'outline'    => 'bg-transparent text-brand-dark border border-brand-beigeBorder hover:bg-brand-beigeInput',
    ];

    $classes = 'inline-flex w-full items-center justify-center rounded-2xl px-5 py-3 text-sm font-bold transition-colors '
        . ($variants[$variant] ?? $variants['dark']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
