<x-layouts.app :title="'Pedidos - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Pedidos</h1>
            <p class="text-sm text-brand-dark/70">Los más recientes primero. El estado de cada pedido lo cambia el negocio a mano.</p>
        </header>

        @if ($errors->any())
            <x-alerta tipo="error">{{ $errors->first() }}</x-alerta>
        @endif

        <form method="GET" action="{{ route('pedidos.index') }}"
              class="grid gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4 md:grid-cols-[12rem_10rem_10rem_1fr_auto] md:items-end">
            <x-select nombre="estado" etiqueta="Estado" :mostrar-error="false">
                <option value="">Todos</option>
                @foreach (App\Enums\EstadoPedido::cases() as $estado)
                    <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->etiqueta() }}</option>
                @endforeach
            </x-select>

            <x-campo nombre="desde" etiqueta="Desde" tipo="date" :valor="request('desde')" />
            <x-campo nombre="hasta" etiqueta="Hasta" tipo="date" :valor="request('hasta')" />
            <x-campo nombre="folio" etiqueta="Folio" :valor="request('folio')" placeholder="Ej. K7M2QX" maxlength="20" />

            <div class="flex gap-2">
                <x-button type="submit">Filtrar</x-button>
                @if ($hayFiltros)
                    <x-button variant="outline" :href="route('pedidos.index')">Limpiar</x-button>
                @endif
            </div>
        </form>

        @if ($pedidos->isEmpty())
            <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                {{ $hayFiltros ? 'No hay pedidos con esos filtros.' : 'Todavía no hay pedidos.' }}
            </p>
        @else
            <x-tabla :encabezados="['Folio', 'Fecha', 'Tipo', 'Productos', 'Total', 'Estado']">
                @foreach ($pedidos as $pedido)
                    <x-tabla.fila>
                        <x-tabla.celda etiqueta="Folio">
                            <a href="{{ route('pedidos.show', $pedido) }}"
                               class="inline-flex min-h-[44px] items-center font-bold text-brand-terracotta underline underline-offset-2">{{ $pedido->folio }}</a>
                        </x-tabla.celda>
                        <x-tabla.celda etiqueta="Fecha">{{ $pedido->created_at->timezone($zona)->format('d/m/Y H:i') }}</x-tabla.celda>
                        <x-tabla.celda etiqueta="Tipo">{{ $pedido->tipo_entrega->etiqueta() }}</x-tabla.celda>
                        <x-tabla.celda etiqueta="Productos">
                            {{ $pedido->items->map(fn ($item) => $item->cantidad.' × '.$item->nombre_item)->implode(', ') }}
                        </x-tabla.celda>
                        <x-tabla.celda etiqueta="Total">${{ number_format($pedido->total, 2) }}</x-tabla.celda>
                        <x-tabla.celda etiqueta="Estado"><x-estado-pedido :estado="$pedido->estado" /></x-tabla.celda>
                    </x-tabla.fila>
                @endforeach
            </x-tabla>

            {{ $pedidos->links() }}
        @endif

        <x-button variant="outline" :href="route('panel.negocio')">Volver al panel</x-button>
    </div>
</x-layouts.app>
