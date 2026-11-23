<x-layouts.app :title="'Métricas — '.$negocio->nombre">
    <div class="flex flex-col gap-6">

        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Métricas</h1>
            <p class="text-sm text-brand-dark/70">
                Pedidos de {{ $negocio->nombre }} en el periodo elegido.
            </p>
        </header>

        {{-- US-10: filtro por rango de fechas (por defecto, el mes en curso) --}}
        <form method="GET" action="{{ route('metricas.index') }}"
              class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4 md:flex-row md:items-end md:gap-3">
            <div class="md:w-44">
                <x-campo nombre="desde" etiqueta="Desde" tipo="date" :valor="$desde" />
            </div>
            <div class="md:w-44">
                <x-campo nombre="hasta" etiqueta="Hasta" tipo="date" :valor="$hasta" />
            </div>
            <x-button type="submit">Aplicar</x-button>
        </form>

        @if (! $hayPedidos)
            {{-- US-10: sin pedidos en el periodo, aviso en lugar de gráficas vacías --}}
            <x-alerta tipo="exito">
                No hay pedidos entre el {{ $desde }} y el {{ $hasta }}. Prueba con otro rango de fechas.
            </x-alerta>
        @else
            {{-- Resumen del periodo --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="flex flex-col gap-1 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark/60">Pedidos del periodo</p>
                    <p class="text-3xl font-extrabold">{{ $totalPedidos }}</p>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark/60">Ventas (sin cancelados)</p>
                    <p class="text-3xl font-extrabold">${{ number_format($ventas, 2) }}</p>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark/60">Ticket promedio</p>
                    <p class="text-3xl font-extrabold">
                        {{ $ticketPromedio !== null ? '$'.number_format($ticketPromedio, 2) : '—' }}
                    </p>
                </div>
            </div>

            {{-- Distribución por estado --}}
            <section class="flex flex-col gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4">
                <h2 class="text-xs font-bold uppercase tracking-widest">Pedidos por estado</h2>
                @foreach (\App\Enums\EstadoPedido::cases() as $estado)
                    @php
                        $cantidad = $porEstado[$estado->value] ?? 0;
                        $porcentaje = (int) round($cantidad * 100 / max(1, $totalPedidos));
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-36 shrink-0 text-sm font-semibold">{{ $estado->etiqueta() }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-brand-beigeBorder">
                            <div class="h-full rounded-full bg-brand-teal" style="width: {{ $porcentaje }}%"></div>
                        </div>
                        <span class="w-8 shrink-0 text-right text-sm font-bold">{{ $cantidad }}</span>
                    </div>
                @endforeach
            </section>

            {{-- Los 10 más pedidos, por cantidad vendida --}}
            <section class="flex flex-col gap-3">
                <h2 class="text-xs font-bold uppercase tracking-widest">Los 10 productos más pedidos</h2>

                <div class="hidden md:grid md:grid-cols-[3rem_1fr_8rem] md:gap-2 md:border-b md:border-brand-beigeBorder md:px-4 md:pb-2 text-xs font-bold uppercase tracking-widest text-brand-dark/60">
                    <span>#</span>
                    <span>Producto</span>
                    <span class="text-right">Cantidad</span>
                </div>

                <div class="flex flex-col gap-2">
                    @forelse ($topProductos as $indice => $producto)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-4 py-3 md:grid md:grid-cols-[3rem_1fr_8rem] md:gap-2 md:rounded-none md:border-0 md:border-b md:bg-transparent">
                            <span class="text-sm font-bold text-brand-dark/50">{{ $indice + 1 }}</span>
                            <span class="text-sm font-semibold">{{ $producto->nombre_item }}</span>
                            <span class="text-sm font-bold text-brand-terracotta md:text-right">{{ $producto->cantidad_total }}</span>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                            Sin productos vendidos en el periodo.
                        </p>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
