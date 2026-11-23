@props(['producto'])

<article @class([
    'flex items-start gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4',
    'opacity-60' => ! $producto->disponible_hoy,
])>
    {{-- US-12b: la información enlaza al detalle; el botón del carrito va fuera del link --}}
    <a href="{{ route('menu.detalle', $producto) }}"
        class="flex min-w-0 flex-1 items-start gap-4 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-terracotta">
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

    @if ($producto->disponible_hoy)
        {{-- US-13: zona del carrito; carrito.js la convierte en stepper cuando ya está en el carrito --}}
        <div class="shrink-0 self-center" data-zona-carrito="{{ $producto->id }}">
            <button type="button" class="carrito-boton-agregar" data-carrito-agregar="{{ $producto->id }}">
                Agregar
            </button>
        </div>
    @endif
</article>
