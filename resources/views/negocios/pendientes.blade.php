<x-layouts.app title="Negocios pendientes">
    <h1>Negocios pendientes</h1>
    <p>Solicitudes del auto-registro. Al aprobar, el negocio abre en su dirección y se crea la cuenta de su dueño.</p>

    <x-button variant="outline" :href="route('negocios.index')">Ver todos los negocios</x-button>

    @if (session('aprobado'))
        <x-alerta>
            {{ session('aprobado.negocio') }} quedó activo en {{ session('aprobado.direccion') }}.
            Cuenta del dueño: {{ session('aprobado.correo') }}.
            Contraseña temporal: <strong>{{ session('aprobado.contrasena') }}</strong>.
            Compártela con el dueño: no se vuelve a mostrar.
        </x-alerta>
    @endif

    @if (session('exito'))
        <x-alerta>{{ session('exito') }}</x-alerta>
    @endif

    @if (session('error'))
        <x-alerta tipo="error">{{ session('error') }}</x-alerta>
    @endif

    @error('motivo')
        <x-alerta tipo="error">{{ $message }}</x-alerta>
    @enderror

    @if ($negocios->isEmpty())
        <p>No hay negocios pendientes.</p>
    @else
        <x-tabla :encabezados="['Nombre', 'Dirección web', 'WhatsApp', 'Correo', 'Solicitud', 'Estado', 'Acciones']">
            @foreach ($negocios as $negocio)
                <x-tabla.fila>
                    <x-tabla.celda etiqueta="Nombre">{{ $negocio->nombre }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Dirección web">{{ $negocio->subdominio }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="WhatsApp">{{ $negocio->telefono_whatsapp }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Correo">{{ $negocio->correo_contacto }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Solicitud">{{ $negocio->created_at->format('d/m/Y') }}</x-tabla.celda>
                    <x-tabla.celda etiqueta="Estado"><x-estado-negocio :estado="$negocio->estado" /></x-tabla.celda>
                    <x-tabla.celda etiqueta="Acciones">
                        <x-confirmar :accion="route('negocios.aprobar', $negocio)" metodo="PATCH"
                                     etiqueta="Aprobar" :titulo="'Aprobar '.$negocio->nombre"
                                     mensaje="El negocio queda activo y se crea la cuenta de su dueño con una contraseña temporal."
                                     confirmar="Aprobar" />

                        <x-confirmar :accion="route('negocios.rechazar', $negocio)" metodo="PATCH"
                                     etiqueta="Rechazar" :titulo="'Rechazar '.$negocio->nombre"
                                     mensaje="Su dirección web queda libre para quien la solicite después."
                                     confirmar="Rechazar">
                            <x-campo nombre="motivo" :id="'motivo-'.$negocio->id" etiqueta="Motivo del rechazo" />
                        </x-confirmar>
                    </x-tabla.celda>
                </x-tabla.fila>
            @endforeach
        </x-tabla>

        {{ $negocios->links() }}
    @endif
</x-layouts.app>
