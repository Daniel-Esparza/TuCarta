<x-layouts.app title="Negocios">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel central</p>
            <h1 class="text-xl font-extrabold">Negocios</h1>
            <p class="text-sm text-brand-dark/70">Busca, filtra, suspende o reactiva los negocios de la plataforma.</p>
        </header>

        @if (session('estado'))
            <x-alerta>{{ session('estado') }}</x-alerta>
        @endif

        <form method="GET" action="{{ route('negocios.index') }}" class="grid gap-3 md:grid-cols-[1fr_16rem_auto] md:items-end">
            <x-campo nombre="q" etiqueta="Buscar" tipo="search" :valor="request('q')" placeholder="Nombre o direccion web" />

            <x-select nombre="estado" etiqueta="Estado">
                <option value="">Todos los estados</option>
                @foreach (App\Enums\EstadoNegocio::cases() as $estado)
                    <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->etiqueta() }}</option>
                @endforeach
            </x-select>

            <x-button type="submit" class="w-full md:w-auto">Buscar</x-button>
        </form>

        @if ($negocios->isEmpty())
            <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                No se encontraron negocios.
            </p>
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
                            @if ($negocio->estado === App\Enums\EstadoNegocio::Suspendido)
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
    </div>
</x-layouts.app>
