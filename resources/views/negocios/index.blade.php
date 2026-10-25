<x-layouts.app>
    <h1>Negocios</h1>

    @if ($negocios->isEmpty())
        <p>Todavía no hay negocios.</p>
    @else
        <x-tabla :encabezados="['Nombre', 'Direccion web', 'Estado']">
            @foreach ($negocios as $negocio)
                <x-tabla.fila>
                    <x-tabla.celda etiqueta="Nombre">{{ $negocio->nombre }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Direccion web">{{ $negocio->subdominio }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Estado"><x-estado-negocio :estado="$negocio->estado" /></x-tabla.celda>
                </x-tabla.fila>
            @endforeach
        </x-tabla>
    @endif
</x-layouts.app>