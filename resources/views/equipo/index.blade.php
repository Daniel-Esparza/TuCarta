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
            <p class="rounded-xl border border-brand-teal/40 bg-brand-teal/10 px-4 py-3 text-sm font-semibold text-brand-teal">
                {{ session('exito') }}
            </p>
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
                        <form method="POST" action="{{ route('equipo.desactivar', $miembro) }}" class="mt-3"
                              onsubmit="return confirm('¿Desactivar a {{ $miembro->nombre }}? Ya no podrá iniciar sesión.')">
                            @csrf
                            @method('PATCH')
                            <x-button variant="outline">[ DESACTIVAR ]</x-button>
                        </form>
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

                <div>
                    <x-input name="nombre" label="Nombre" placeholder="Nombre" :value="old('nombre')" required />
                    @error('nombre')<p class="mt-1 text-xs font-semibold text-brand-terracotta">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-input name="apellidos" label="Apellidos" placeholder="Apellidos" :value="old('apellidos')" required />
                    @error('apellidos')<p class="mt-1 text-xs font-semibold text-brand-terracotta">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-input name="email" label="Correo" type="email" placeholder="empleado@correo.com" :value="old('email')" required />
                    @error('email')<p class="mt-1 text-xs font-semibold text-brand-terracotta">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-input name="telefono_whatsapp" label="Teléfono de WhatsApp" type="tel" placeholder="+52 55 1234 5678" :value="old('telefono_whatsapp')" required />
                    @error('telefono_whatsapp')<p class="mt-1 text-xs font-semibold text-brand-terracotta">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-input name="password" label="Contraseña temporal" type="password" placeholder="Mínimo 8 caracteres" required />
                    @error('password')<p class="mt-1 text-xs font-semibold text-brand-terracotta">{{ $message }}</p>@enderror
                </div>

                <x-button variant="terracotta">[ + CREAR EMPLEADO ]</x-button>
            </form>
        </section>

        <x-button variant="outline" href="/">[ VOLVER ]</x-button>
    </div>
</x-layouts.app>