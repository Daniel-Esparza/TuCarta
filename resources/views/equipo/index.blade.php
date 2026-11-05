<x-layouts.app title="Equipo de trabajo">
    <div class="flex flex-col gap-6">
        {{-- Encabezado --}}
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Equipo de trabajo</h1>
            <p class="text-sm text-brand-dark/70">
                Crea cuentas de empleado para tu negocio y desactiva a quien ya no trabaje aquí.
                Un empleado puede avanzar pedidos y marcar agotados. Nada más.
            </p>
        </header>

        {{-- Mensaje de éxito --}}
        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif

        {{-- Lista del equipo --}}
        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Tu equipo</h2>
            @forelse ($equipo as $miembro)
                <article class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ $miembro->nombre }} {{ $miembro->apellidos }}</p>
                            <p class="truncate text-xs text-brand-dark/70">{{ $miembro->email }}</p>
                        </div>
                        @if ($miembro->id === $duenoId)
                            <span class="shrink-0 rounded-full border border-brand-beigeBorder bg-brand-beigeCard px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Dueño</span>
                        @elseif ($miembro->estado === 'inactivo')
                            <span class="shrink-0 rounded-full border border-brand-terracotta/40 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-terracotta">Inactivo</span>
                        @else
                            <span class="shrink-0 rounded-full border border-brand-teal/40 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-teal">Empleado</span>
                        @endif
                    </div>

                    @if ($miembro->id !== $duenoId)
                        @if ($miembro->permisos->isNotEmpty())
                            <p class="mt-2 text-xs text-brand-dark/70">
                                Permisos: {{ $miembro->permisos->map(fn ($p) => $p->permiso?->clave)->filter()->implode(', ') }}
                            </p>
                        @else
                            <p class="mt-2 text-xs text-brand-dark/50">Sin permisos asignados.</p>
                        @endif
                    @endif

                    @if ($miembro->id !== $duenoId && $miembro->estado === 'activo')
                        <div class="mt-3">
                            <x-confirmar :accion="route('equipo.desactivar', $miembro)" metodo="PATCH"
                                         etiqueta="Desactivar"
                                         :titulo="'Desactivar a ' . $miembro->nombre"
                                         mensaje="Ya no podrá iniciar sesión."
                                         confirmar="Desactivar" />
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Todavía no hay nadie más en tu equipo.
                </p>
            @endforelse
        </section>

        {{-- Alta de empleado --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Nuevo empleado</h2>
            <form method="POST" action="{{ route('equipo.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-campo nombre="nombre" etiqueta="Nombre" required />
                <x-campo nombre="apellidos" etiqueta="Apellidos" required />
                <x-campo nombre="email" etiqueta="Correo" tipo="email" placeholder="empleado@correo.com" required />
                <x-campo nombre="telefono_whatsapp" etiqueta="Teléfono de WhatsApp" tipo="tel" placeholder="+52 55 1234 5678" required />
                <x-campo nombre="password" etiqueta="Contraseña temporal" tipo="password" placeholder="Mínimo 8 caracteres" required />
                <x-button type="submit" variant="terracotta">Crear empleado</x-button>
            </form>
        </section>

        <a href="{{ route('panel.negocio') }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-brand-beigeBorder px-4 py-2.5 font-medium text-brand-dark transition-colors duration-200 hover:bg-brand-beigeCard/50 focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2">
            Volver
        </a>
    </div>
</x-layouts.app>
