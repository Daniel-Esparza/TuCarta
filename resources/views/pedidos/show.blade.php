<x-layouts.app :title="'Pedido '.$pedido->folio.' - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-extrabold">Pedido {{ $pedido->folio }}</h1>
                <x-estado-pedido :estado="$pedido->estado" />
            </div>
            <p class="text-sm text-brand-dark/70">Recibido el {{ $pedido->created_at->timezone($zona)->format('d/m/Y \a \l\a\s H:i') }}</p>
        </header>

        {{-- US-21: aquí van los botones para cambiar el estado del pedido. --}}
        @includeIf('pedidos._estado', ['pedido' => $pedido])

        <div class="grid gap-4 md:grid-cols-[1fr_2fr]">
            <section class="flex flex-col gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4 text-sm">
                <h2 class="text-xs font-bold uppercase tracking-widest">Cliente y entrega</h2>
                <dl class="flex flex-col gap-2">
                    <div>
                        <dt class="text-xs font-semibold text-brand-dark/60">Cliente</dt>
                        <dd class="font-semibold">{{ $pedido->cliente_nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-brand-dark/60">Tipo de pedido</dt>
                        <dd>{{ $pedido->tipo_entrega->etiqueta() }}</dd>
                    </div>
                    @if ($pedido->direccion_entrega)
                        <div>
                            <dt class="text-xs font-semibold text-brand-dark/60">Dirección</dt>
                            <dd>{{ $pedido->direccion_entrega }}</dd>
                        </div>
                    @endif
                    @if ($pedido->referencias_entrega)
                        <div>
                            <dt class="text-xs font-semibold text-brand-dark/60">Referencias</dt>
                            <dd>{{ $pedido->referencias_entrega }}</dd>
                        </div>
                    @endif
                    @if ($pedido->notas)
                        <div>
                            <dt class="text-xs font-semibold text-brand-dark/60">Nota del cliente</dt>
                            <dd>{{ $pedido->notas }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="flex flex-col gap-3">
                <h2 class="text-xs font-bold uppercase tracking-widest">Productos</h2>
                <x-tabla :encabezados="['Cantidad', 'Producto', 'Precio', 'Subtotal']">
                    @foreach ($pedido->items as $item)
                        <x-tabla.fila>
                            <x-tabla.celda etiqueta="Cantidad">{{ $item->cantidad }}</x-tabla.celda>
                            <x-tabla.celda etiqueta="Producto">
                                <span class="font-semibold">{{ $item->nombre_item }}</span>
                                @foreach ($item->opciones as $opcion)
                                    <span class="block text-xs text-brand-dark/70">
                                        {{ $opcion->nombre_grupo }}: {{ $opcion->nombre_opcion }}@if ((float) $opcion->incremento_precio > 0) (+${{ number_format($opcion->incremento_precio, 2) }})@endif
                                    </span>
                                @endforeach
                            </x-tabla.celda>
                            <x-tabla.celda etiqueta="Precio">${{ number_format($item->precio_unitario, 2) }}</x-tabla.celda>
                            <x-tabla.celda etiqueta="Subtotal">${{ number_format($item->subtotal, 2) }}</x-tabla.celda>
                        </x-tabla.fila>
                    @endforeach
                </x-tabla>
                <p class="text-right text-lg font-extrabold">Total: ${{ number_format($pedido->total, 2) }}</p>
            </section>
        </div>

        <section class="flex flex-col gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4 text-sm">
            <h2 class="text-xs font-bold uppercase tracking-widest">Historial del estado</h2>
            <ol class="flex flex-col gap-2">
                @foreach ($pedido->historial as $paso)
                    <li class="flex flex-wrap items-center gap-3">
                        <x-estado-pedido :estado="$paso->estado_nuevo" />
                        <span class="text-brand-dark/70">{{ \Illuminate\Support\Carbon::parse($paso->created_at)->timezone($zona)->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ol>
        </section>

        <x-button variant="outline" :href="route('pedidos.index')">Volver a pedidos</x-button>
    </div>
</x-layouts.app>
