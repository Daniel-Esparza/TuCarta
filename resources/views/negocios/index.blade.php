<x-layouts.app>
    <h1>Negocios</h1>

    @if (session('estado'))
        <x-alerta>{{ session('estado') }}</x-alerta>
    @endif

    <form method="GET" action="{{ route('negocios.index') }}" class="flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o direccion web"
               class="border border-brand-beigeBorder bg-brand-beigeInput rounded-lg px-3 py-2">
        <select name="estado" class="border border-brand-beigeBorder bg-brand-beigeInput rounded-lg px-3 py-2">
            <option value="">Todos los estados</option>
            @foreach (['activo', 'pendiente', 'suspendido', 'rechazado'] as $estado)
                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg px-4 py-2">Buscar</button>
    </form>

    @if ($negocios->isEmpty())
        <p>No se encontraron negocios.</p>
    @else
        <x-tabla :encabezados="['Nombre', 'Direccion web', 'Estado', 'Productos', 'Fecha de alta', 'Acciones']">
            @foreach ($negocios as $negocio)
                <x-tabla.fila>
                    <x-tabla.celda etiqueta="Nombre">{{ $negocio->nombre }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Direccion web">{{ $negocio->subdominio }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Estado"><x-estado-negocio :estado="$negocio->estado" /></x-tabla.celda>
                    <x-tabla.celda etiqueta="Productos">{{ $negocio->items_count }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Fecha de alta">{{ $negocio->created_at->format('d/m/Y') }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Acciones">
                        @if ($negocio->estado === 'suspendido')
                            <x-confirmar :accion="route('negocios.reactivar', $negocio)" metodo="PATCH"
                                         etiqueta="Reactivar" titulo="Reactivar este negocio"
                                         mensaje="Su menu volvera a estar disponible para clientes."
                                         confirmar="Reactivar" />
                        @else
                            <x-confirmar :accion="route('negocios.suspender', $negocio)" metodo="PATCH"
                                         etiqueta="Suspender" titulo="Suspender este negocio"
                                         mensaje="Su menu mostrara un aviso. No se borra su informacion."
                                         confirmar="Suspender" />
                        @endif
                    </x-tabla.celda>
                </x-tabla.fila>
            @endforeach
        </x-tabla>

        {{ $negocios->links() }}
    @endif
</x-layouts.app>