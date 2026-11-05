<x-layouts.app title="Negocios pendientes">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel central</p>
            <h1 class="text-xl font-extrabold">Negocios pendientes</h1>
            <p class="text-sm text-brand-dark/70">Solicitudes del auto-registro. Al aprobar, el negocio abre en su dirección y se crea la cuenta de su dueño.</p>
        </header>

    <div>
        <x-button variant="outline" :href="route('negocios.index')">Ver todos los negocios</x-button>
    </div>

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
        <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
            No hay negocios pendientes.
        </p>
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
                        <div class="flex flex-wrap gap-2">
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
                        </div>
                    </x-tabla.celda>
                </x-tabla.fila>
            @endforeach
        </x-tabla>

        {{ $negocios->links() }}
    @endif
    </div>
</x-layouts.app>
