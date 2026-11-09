<x-layouts.app title="Grupos de opciones">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Grupos de opciones</h1>
            <p class="text-sm text-brand-dark/70">
                Producto: {{ $producto->nombre }}. Crea grupos como "Tamaño" o "Extras".
                Las opciones con precio se agregan en el siguiente paso.
            </p>
        </header>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-2xl border border-brand-terracotta/40 bg-brand-beigeCard px-4 py-3 text-sm font-medium text-brand-dark">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Grupos del producto</h2>
            @forelse ($grupos as $grupo)
                <article class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3">
                    <p class="text-sm font-bold">{{ $grupo->nombre }}</p>
                    <p class="text-xs text-brand-dark/70">
                        {{ $grupo->es_multiple ? 'Varias opciones' : 'Una sola opción' }} ·
                        {{ $grupo->es_obligatorio ? 'Obligatorio' : 'Opcional' }} ·
                        Elegir de {{ $grupo->min_selecciones }} a {{ $grupo->max_selecciones }}
                    </p>

                    <details class="mt-3">
                        <summary class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg border border-brand-beigeBorder px-4 text-sm font-medium">
                            Editar grupo
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

                    <div class="mt-3">
                        <x-confirmar :accion="route('grupos.destroy', [$producto, $grupo])" metodo="DELETE"
                                     etiqueta="Eliminar"
                                     :titulo="'Eliminar ' . $grupo->nombre"
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

        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Nuevo grupo</h2>
            @include('productos._form-grupo', [
                'accion' => route('grupos.store', $producto),
                'metodo' => 'POST',
                'grupo' => null,
                'texto' => 'Crear grupo',
            ])
        </section>

        <a href="{{ route('categorias.index') }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-brand-beigeBorder px-4 py-2.5 font-medium text-brand-dark transition-colors duration-200 hover:bg-brand-beigeCard/50 focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2">
            Volver
        </a>
    </div>
</x-layouts.app>
