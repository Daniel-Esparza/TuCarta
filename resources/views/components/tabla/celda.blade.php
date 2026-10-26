@props(['etiqueta' => null])

<td {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 py-1 before:font-semibold before:text-brand-dark/70 before:content-[attr(data-etiqueta)] md:table-cell md:px-4 md:py-3 md:before:content-none']) }}
    data-etiqueta="{{ $etiqueta }}">
    <span class="text-right md:text-left">{{ $slot }}</span>
</td>
