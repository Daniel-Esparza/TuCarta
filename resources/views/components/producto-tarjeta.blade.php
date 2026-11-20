@props(['producto'])

<a href="{{ route('menu.detalle', $producto) }}"
   @class([
       'flex items-start gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4 transition-colors duration-200 hover:border-brand-terracotta focus:outline-none focus:ring-2 focus:ring-brand-terracotta',
       'opacity-60' => ! $producto->disponible_hoy,
   ])>
    @if ($producto->miniatura)
        <img src="{{ Storage::url($producto->miniatura) }}" alt="{{ $producto->nombre }}"
            class="h-20 w-20 shrink-0 rounded-xl border border-brand-beigeBorder object-cover" />
    @else
        {{-- Sin foto: espacio neutro del mismo tamaño para no romper el diseño --}}
        <div aria-hidden="true" class="h-20 w-20 shrink-0 rounded-xl border border-dashed border-brand-beigeBorder bg-brand-beigeInput"></div>
    @endif

    <div class="flex min-w-0 flex-col gap-1">
        <h3 class="text-sm font-bold">{{ $producto->nombre }}</h3>
        @if ($producto->descripcion)
            <p class="text-xs text-brand-dark/70">{{ $producto->descripcion }}</p>
        @endif
        <p class="text-sm font-extrabold text-brand-terracotta">${{ number_format($producto->precio, 2) }}</p>
        @unless ($producto->disponible_hoy)
            <span class="w-fit rounded-full bg-brand-beigeBorder px-3 py-0.5 text-xs font-semibold text-brand-dark/70">Agotado</span>
        @endunless
    </div>
</a>
