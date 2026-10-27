@php
    // Errores de los campos del formulario de alta se muestran junto a cada campo (x-campo).
    // Los demas (por ejemplo los de agregar un permiso) se juntan en un solo aviso arriba.
    $camposDeAlta = ['nombre', 'apellidos', 'email', 'telefono_whatsapp', 'password'];
    $otrosErrores = collect($errors->keys())->diff($camposDeAlta)->flatMap(fn ($campo) => $errors->get($campo));
@endphp

<x-layouts.app title="Moderadores y permisos">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel central</p>
            <h1 class="text-xl font-extrabold">Moderadores y permisos</h1>
            <p class="text-sm text-brand-dark/70">
                Un moderador solo puede hacer lo que le asignes aqui. Sin permisos no ve ni hace nada.
            </p>
        </header>

        @if (session('estado'))
            <x-alerta>{{ session('estado') }}</x-alerta>
        @endif

        @if ($otrosErrores->isNotEmpty())
            <x-alerta tipo="error">
                <ul class="list-disc pl-5">
                    @foreach ($otrosErrores as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        {{-- Alta de moderador --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Crear moderador</h2>
            <form method="POST" action="{{ route('moderadores.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-campo nombre="nombre" etiqueta="Nombre" required />
                <x-campo nombre="apellidos" etiqueta="Apellidos" required />
                <x-campo nombre="email" etiqueta="Correo de acceso" tipo="email" required />
                <x-campo nombre="telefono_whatsapp" etiqueta="WhatsApp" tipo="tel" placeholder="+52 55 1234 5678" required />
                <x-campo nombre="password" etiqueta="Contraseña temporal" tipo="password" required />
                <x-button type="submit" variant="terracotta">Crear moderador</x-button>
            </form>
        </section>

        {{-- Lista de moderadores y sus permisos --}}
        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Moderadores</h2>

            @forelse ($moderadores as $moderador)
                <article class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold">{{ $moderador->nombre }} {{ $moderador->apellidos }}</h3>
                        <p class="truncate text-xs text-brand-dark/70">{{ $moderador->email }}</p>
                    </div>

                    @if ($moderador->permisos->isEmpty())
                        <p class="text-sm text-brand-dark/60">Sin permisos. Este moderador no puede ver ni hacer nada en el panel.</p>
                    @else
                        <x-tabla :encabezados="['Permiso', 'Alcance', 'Acciones']">
                            @foreach ($moderador->permisos as $asignacion)
                                <x-tabla.fila>
                                    <x-tabla.celda etiqueta="Permiso">{{ $asignacion->permiso->clave }}</x-tabla.celda>
                                    <x-tabla.celda etiqueta="Alcance">{{ $asignacion->negocio?->nombre ?? 'Todos los negocios' }}</x-tabla.celda>
                                    <x-tabla.celda etiqueta="Acciones">
                                        <x-confirmar :accion="route('moderadores.permisos.destroy', [$moderador, $asignacion])" metodo="DELETE"
                                                     etiqueta="Quitar" titulo="Quitar permiso"
                                                     :mensaje="'Se quitara el permiso ' . $asignacion->permiso->clave . ' a ' . $moderador->nombre . '. Surte efecto de inmediato.'"
                                                     confirmar="Quitar" />
                                    </x-tabla.celda>
                                </x-tabla.fila>
                            @endforeach
                        </x-tabla>
                    @endif

                    <form method="POST" action="{{ route('moderadores.permisos.store', $moderador) }}"
                          class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                        @csrf
                        <x-select nombre="permiso_id" etiqueta="Permiso" :id="'permiso-' . $moderador->id" :mostrar-error="false" required>
                            @foreach ($permisos as $permiso)
                                <option value="{{ $permiso->id }}">{{ $permiso->clave }}</option>
                            @endforeach
                        </x-select>

                        <x-select nombre="negocio_id" etiqueta="Negocio" :id="'negocio-' . $moderador->id" :mostrar-error="false">
                            <option value="">Todos los negocios</option>
                            @foreach ($negocios as $negocio)
                                <option value="{{ $negocio->id }}">{{ $negocio->nombre }}</option>
                            @endforeach
                        </x-select>

                        <x-button type="submit" class="w-full md:w-auto">Agregar permiso</x-button>
                    </form>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Todavía no hay moderadores.
                </p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
