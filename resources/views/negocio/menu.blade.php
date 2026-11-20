@php
    // ISO-8601: 1 = lunes … 7 = domingo. El 0 cubre la convención alternativa (0 = domingo).
    $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
@endphp

<x-layouts.app :title="$negocio->nombre">
    <div class="flex flex-col gap-8">
        {{-- Cabecera del negocio: perfil (US-05) y horarios (US-19a) --}}
        <header class="flex flex-col items-center gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-6 py-8 text-center">
            @if ($negocio->logo_path)
                <img src="{{ Storage::url($negocio->logo_path) }}" alt="Logo de {{ $negocio->nombre }}"
                    class="h-20 w-20 rounded-full border border-brand-beigeBorder object-cover">
            @endif

            <h1 class="text-2xl font-extrabold">{{ $negocio->nombre }}</h1>

            @if ($negocio->descripcion)
                <p class="max-w-prose text-sm text-brand-dark/70">{{ $negocio->descripcion }}</p>
            @endif

            <div class="mt-2 space-y-1 text-sm">
                @if ($negocio->direccion)
                    <p>{{ $negocio->direccion }}</p>
                @endif

                @if ($negocio->telefono_whatsapp)
                    <p>WhatsApp: {{ $negocio->telefono_whatsapp }}</p>
                @endif
            </div>

            @if ($horarios->isNotEmpty())
                <div class="flex flex-col gap-0.5 text-sm text-brand-dark/80">
                    @foreach ($horarios as $dia => $rangos)
                        <p>
                            <span class="font-semibold">{{ $dias[$dia] }}:</span>
                            {{ $rangos->map(fn ($h) => substr($h->hora_apertura, 0, 5).' a '.substr($h->hora_cierre, 0, 5))->implode(' y ') }}
                        </p>
                    @endforeach
                </div>
            @endif
        </header>

        @if ($categorias->isNotEmpty())
            {{-- Navegación entre categorías: chips con scroll horizontal, primero celular --}}
            @if ($categorias->count() > 1)
                <nav aria-label="Categorías del menú" class="flex gap-2 overflow-x-auto pb-1">
                    @foreach ($categorias as $categoria)
                        <a href="#categoria-{{ $categoria->id }}"
                            class="shrink-0 rounded-full border border-brand-beigeBorder bg-brand-beigeInput px-4 py-2 text-sm font-semibold text-brand-dark">
                            {{ $categoria->nombre }}
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- Productos agrupados por categoría, en el orden del dueño (US-06) --}}
            @foreach ($categorias as $categoria)
                <section id="categoria-{{ $categoria->id }}" class="flex scroll-mt-4 flex-col gap-3">
                    <h2 class="text-lg font-extrabold">{{ $categoria->nombre }}</h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($categoria->items as $producto)
                            <x-producto-tarjeta :producto="$producto" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        @else
            <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                Estamos preparando la carta de este negocio. Vuelve pronto.
            </p>
        @endif
    </div>
    <x-carrito-scripts :negocio="$negocio" :items="$itemsMenu" />
</x-layouts.app>
