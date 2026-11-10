<x-layouts.app :title="'Opciones de '.$producto->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Opciones de {{ $producto->nombre }}</h1>
            <p class="text-sm text-brand-dark/70">
                Aquí defines lo que el cliente puede elegir al pedir <strong>solo este producto</strong>.
                Primero crea un grupo, como "Tamaño", y dentro de él sus opciones, como "Chico" o "Grande".
                Después puedes copiarlo a muchos productos a la vez.
            </p>
        </header>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif
        @if ($errors->opcion->any())
            <x-alerta tipo="error">{{ $errors->opcion->first() }}</x-alerta>
        @endif
        @foreach (['nombre', 'min_selecciones', 'destinos', 'grupos'] as $campo)
            @error($campo)
                <x-alerta tipo="error">{{ $message }}</x-alerta>
            @enderror
        @endforeach

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Grupos de este producto</h2>
            @forelse ($grupos as $grupo)
                <article id="grupo-{{ $grupo->id }}" class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-4">
                    <div>
                        <h3 class="text-base font-bold">{{ $grupo->nombre }}</h3>
                        <p class="text-xs text-brand-dark/70">
                            @if ($grupo->es_multiple)
                                El cliente elige de {{ $grupo->min_selecciones }} a {{ $grupo->max_selecciones }}
                            @else
                                El cliente elige solo una
                            @endif
                            · {{ $grupo->es_obligatorio ? 'Obligatorio' : 'Opcional' }}
                        </p>
                    </div>

                    <ul class="flex flex-col gap-2">
                        @forelse ($grupo->opciones as $opcion)
                            <li class="rounded-lg border border-brand-beigeBorder bg-brand-beigeCard px-3 py-2">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-semibold">{{ $opcion->nombre }}</span>
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span>+${{ number_format($opcion->incremento_precio, 2) }}</span>
                                        <x-confirmar :accion="route('opciones.destroy', $opcion)" metodo="DELETE"
                                                     etiqueta="Quitar"
                                                     :titulo="'Quitar '.$opcion->nombre"
                                                     mensaje="Los pedidos ya hechos no cambian."
                                                     confirmar="Quitar" />
                                    </span>
                                </div>
                                <details>
                                    <summary class="inline-flex min-h-[44px] cursor-pointer items-center text-sm font-medium underline underline-offset-2">Editar</summary>
                                    <div class="mt-2">
                                        @include('opciones._form', [
                                            'accion' => route('opciones.update', $opcion),
                                            'metodo' => 'PUT',
                                            'opcion' => $opcion,
                                            'sufijo' => 'o'.$opcion->id,
                                            'texto' => 'Guardar',
                                        ])
                                    </div>
                                </details>
                            </li>
                        @empty
                            <li class="text-sm text-brand-dark/60">Este grupo todavía no tiene opciones. Agrega la primera aquí abajo.</li>
                        @endforelse
                    </ul>

                    @include('opciones._form', [
                        'accion' => route('opciones.store', $grupo),
                        'metodo' => 'POST',
                        'opcion' => null,
                        'sufijo' => 'g'.$grupo->id,
                        'texto' => 'Agregar opción',
                    ])

                    <div class="flex flex-wrap items-start gap-3 border-t border-brand-beigeBorder pt-3">
                        <details class="w-full">
                            <summary class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg border border-brand-beigeBorder px-4 text-sm font-medium">
                                Cambiar nombre o reglas del grupo
                            </summary>
                            <div class="mt-3">
                                @include('productos._form-grupo', [
                                    'accion' => route('grupos.update', [$producto, $grupo]),
                                    'metodo' => 'PUT',
                                    'grupo' => $grupo,
                                    'texto' => 'Guardar cambios',
                                ])
                            </div>
                        </details>
                        <x-confirmar :accion="route('grupos.destroy', [$producto, $grupo])" metodo="DELETE"
                                     etiqueta="Eliminar grupo"
                                     :titulo="'Eliminar '.$grupo->nombre"
                                     mensaje="También se borran sus opciones. El producto no se borra."
                                     confirmar="Eliminar" />
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Este producto no tiene grupos de opciones. Sin grupos, se vende igual que antes.
                </p>
            @endforelse
        </section>

        <details class="rounded-2xl border border-brand-beigeBorder p-4" @if ($grupos->isEmpty()) open @endif>
            <summary class="flex min-h-[44px] cursor-pointer items-center text-sm font-bold">Crear un grupo nuevo</summary>
            <div class="mt-3">
                @include('productos._form-grupo', [
                    'accion' => route('grupos.store', $producto),
                    'metodo' => 'POST',
                    'grupo' => null,
                    'texto' => 'Crear grupo',
                ])
            </div>
        </details>

        @if ($grupos->isNotEmpty() && $categorias->isNotEmpty())
            <details class="rounded-2xl border border-brand-beigeBorder p-4">
                <summary class="flex min-h-[44px] cursor-pointer items-center text-sm font-bold">Copiar estos grupos a otros productos</summary>

                <form method="POST" action="{{ route('grupos.copiar', $producto) }}" class="mt-3 flex flex-col gap-5">
                    @csrf

                    <fieldset class="flex flex-col gap-1">
                        <legend class="text-sm font-semibold">¿Qué grupos quieres copiar?</legend>
                        @foreach ($grupos as $grupo)
                            <label class="flex min-h-[44px] items-center gap-3 text-sm">
                                <input type="checkbox" name="grupos[]" value="{{ $grupo->id }}" checked class="size-6 accent-brand-terracotta">
                                {{ $grupo->nombre }} ({{ $grupo->opciones->count() }} opciones)
                            </label>
                        @endforeach
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="text-sm font-semibold">¿A qué productos?</legend>
                        @foreach ($categorias as $categoria)
                            <div class="rounded-lg border border-brand-beigeBorder px-3 py-1">
                                <label class="flex min-h-[44px] items-center gap-3 text-sm font-medium">
                                    <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}" class="size-6 accent-brand-terracotta">
                                    Toda la categoría {{ $categoria->nombre }} ({{ $categoria->items->count() }})
                                </label>
                                <details>
                                    <summary class="inline-flex min-h-[44px] cursor-pointer items-center text-sm underline underline-offset-2">Elegir solo algunos</summary>
                                    <div class="flex flex-col">
                                        @foreach ($categoria->items as $otro)
                                            <label class="flex min-h-[44px] items-center gap-3 text-sm">
                                                <input type="checkbox" name="productos[]" value="{{ $otro->id }}" class="size-6 accent-brand-terracotta">
                                                {{ $otro->nombre }}
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            </div>
                        @endforeach
                    </fieldset>

                    <label class="flex min-h-[44px] items-center gap-3 text-sm">
                        <input type="checkbox" name="reemplazar" value="1" class="size-6 accent-brand-terracotta">
                        Si un producto ya tiene un grupo con el mismo nombre, reemplazarlo
                    </label>

                    <x-button type="submit">Copiar grupos</x-button>
                </form>
            </details>
        @elseif ($grupos->isNotEmpty())
            <p class="text-sm text-brand-dark/70">Cuando tengas más productos, aquí podrás copiarles estos grupos.</p>
        @endif

        <x-button variant="outline" :href="route('productos.index')">Volver a productos</x-button>
    </div>
</x-layouts.app>
