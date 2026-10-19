@props([
    'variant' => 'primary', // 'primary' (terracotta) o 'secondary' (teal)
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-4 py-2.5 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variants = [
        'primary' => 'bg-brand-terracotta text-white hover:bg-brand-terracottaHover focus:ring-brand-terracotta',
        'secondary' => 'bg-brand-teal text-white hover:bg-brand-tealHover focus:ring-brand-teal',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
