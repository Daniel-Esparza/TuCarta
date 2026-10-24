@props(['tipo' => 'exito'])

@php
    $colores = $tipo === 'error'
        ? 'bg-brand-terracotta/10 text-brand-terracottaHover border-brand-terracotta/50'
        : 'bg-brand-teal/10 text-brand-teal border-brand-teal/40';
@endphp

<div role="{{ $tipo === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 text-sm font-medium $colores"]) }}>
    {{ $slot }}
</div>
