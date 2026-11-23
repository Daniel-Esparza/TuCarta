<x-layouts.app :title="$item->nombre . ' — ' . $negocio->nombre">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Regresar al menú --}}
        <a href="{{ url('/') }}"
           class="inline-flex items-center gap-1 text-sm text-brand-dark/60 hover:text-brand-terracotta">
            ← Regresar al menú
        </a>

        {{-- Foto del producto --}}
        @if ($item->imagen)
            <img src="{{ Storage::url($item->imagen) }}"
                 alt="{{ $item->nombre }}"
                 class="w-full rounded-2xl object-cover max-h-72">
        @else
            <div class="w-full rounded-2xl bg-brand-beigeBorder max-h-72 h-48 flex items-center justify-center text-brand-dark/30 text-sm">
                Sin fotografía
            </div>
        @endif

        {{-- Nombre, precio y descripción --}}
        <div class="space-y-1">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-extrabold">{{ $item->nombre }}</h1>
                <span class="shrink-0 text-xl font-bold text-brand-terracotta">
                    ${{ number_format($item->precio, 2) }}
                </span>
            </div>

            @if ($item->descripcion)
                <p class="text-sm text-brand-dark/70">{{ $item->descripcion }}</p>
            @endif

            @unless ($item->disponible_hoy)
                <span class="inline-block rounded-full bg-brand-beigeBorder px-3 py-0.5 text-xs font-semibold text-brand-dark/50">
                    Agotado
                </span>
            @endunless
        </div>

        {{-- Grupos de opciones (US-18b) --}}
        @if ($item->gruposOpciones->isNotEmpty())
            <div class="space-y-4">
                @foreach ($item->gruposOpciones as $grupo)
                    <div class="rounded-xl border border-brand-beigeBorder bg-brand-beigeCard p-4 space-y-2">
                        <p class="font-semibold text-sm">
                            {{ $grupo->nombre }}
                            @if ($grupo->es_obligatorio)
                                <span class="ml-1 text-xs text-brand-terracotta font-normal">Obligatorio</span>
                            @endif
                        </p>

                        <ul class="space-y-1">
                            @foreach ($grupo->opciones as $opcion)
                                <li class="flex justify-between text-sm text-brand-dark/80">
                                    <span>{{ $opcion->nombre }}</span>
                                    @if ($opcion->incremento_precio > 0)
                                        <span class="text-brand-terracotta">+${{ number_format($opcion->incremento_precio, 2) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- US-13: agregar al carrito (la lógica vive en carrito-ui.js) --}}
        @if ($item->disponible_hoy)
            <div class="pt-2" data-zona-carrito="{{ $item->id }}">
                <button type="button" class="carrito-boton-agregar" data-carrito-agregar="{{ $item->id }}">
                    Agregar al carrito
                </button>
            </div>
        @endif

    </div>

    {{-- US-13: datos y scripts del carrito para este producto --}}
    <x-carrito-scripts :negocio="$negocio" :items="collect([$item])" />
</x-layouts.app>
