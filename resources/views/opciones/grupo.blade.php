<x-layouts.app :title="'Opciones de '.$grupo->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Opciones de {{ $grupo->nombre }}</h1>
            <p class="text-sm text-brand-dark/70">
                Producto: {{ $grupo->item->nombre }}. Cada opción puede sumar un precio extra, o ninguno.
            </p>
        </header>

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold">Disponibilidad del producto:</span>
            <x-producto-disponibilidad :producto="$grupo->item" />
        </div>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->editar->any())
            <x-alerta tipo="error">{{ $errors->editar->first() }}</x-alerta>
        @endif

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Opciones del grupo</h2>
            @forelse ($grupo->opciones as $opcion)
                <article class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3">
                    <p class="flex items-center justify-between gap-3 text-sm">
                        <span class="font-bold">{{ $opcion->nombre }}</span>
                        <span>+${{ number_format($opcion->incremento_precio, 2) }}</span>
                    </p>

                    <details class="mt-3">
                        <summary class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg border border-brand-beigeBorder px-4 text-sm font-medium">
                            Editar opción
                        </summary>
                        <div class="mt-3">
                            @include('opciones._form', [
                                'accion' => route('opciones.update', $opcion),
                                'metodo' => 'PUT',
                                'opcion' => $opcion,
                                'texto' => 'Guardar cambios',
                            ])
                        </div>
                    </details>

                    <div class="mt-3">
                        <x-confirmar :accion="route('opciones.destroy', $opcion)" metodo="DELETE"
                                     etiqueta="Eliminar"
                                     :titulo="'Eliminar '.$opcion->nombre"
                                     mensaje="Los pedidos ya hechos no cambian."
                                     confirmar="Eliminar" />
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Este grupo todavía no tiene opciones.
                </p>
            @endforelse
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Nueva opción</h2>
            @include('opciones._form', [
                'accion' => route('opciones.store', $grupo),
                'metodo' => 'POST',
                'opcion' => null,
                'texto' => 'Agregar opción',
            ])
        </section>

        <x-button variant="outline" :href="route('grupos.index', $grupo->item)">Volver a los grupos</x-button>
    </div>
</x-layouts.app>
