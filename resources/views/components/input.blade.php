@props(['disabled' => false])

<input 
    {{ $disabled ? 'disabled' : '' }} 
    {{ $attributes->merge([
        'class' => 'w-full min-h-[44px] px-3.5 py-2.5 bg-brand-beigeInput text-brand-dark border border-brand-beigeBorder rounded-lg focus:border-brand-terracotta focus:ring-1 focus:ring-brand-terracotta outline-none transition-colors duration-200'
    ]) }}
>
