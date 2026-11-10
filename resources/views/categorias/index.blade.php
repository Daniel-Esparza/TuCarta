<x-layouts.app title="Categorías del menú">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Categorías del menú</h1>
            <p class="text-sm text-brand-dark/70">
                Organiza tu menú en secciones como Entradas, Bebidas o Postres. El orden de esta lista es el que verá tu cliente.
            </p>
        </header>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-2xl border border-brand-terracotta/40 bg-brand-beigeCard px-4 py-3 text-sm font-medium text-brand-dark">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->editar->any())
            <div role="alert" class="rounded-2xl border border-brand-terracotta/40 bg-brand-beigeCard px-4 py-3 text-sm font-medium text-brand-dark">
                {{ $errors->editar->first('nombre') }}
            </div>
        @endif

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Tus categorías</h2>
            @forelse ($categorias as $categoria)
                <article class="rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ $categoria->nombre }}</p>
                            <p class="text-xs text-brand-dark/70">{{ $categoria->items->count() }} producto(s)</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @foreach ([['subir', 'Subir', $loop->first], ['bajar', 'Bajar', $loop->last]] as [$ruta, $texto, $deshabilitado])
                                <form method="POST" action="{{ route('categorias.'.$ruta, $categoria) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" @disabled($deshabilitado)
                                            aria-label="{{ $texto }} {{ $categoria->nombre }}"
                                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg border border-brand-beigeBorder px-3 text-sm font-medium transition-colors duration-200 hover:bg-brand-beigeCard/50 focus:outline-none focus:ring-2 focus:ring-brand-terracotta disabled:cursor-not-allowed disabled:opacity-40">
                                        {{ $texto }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-3">
                        <x-button :href="route('panel.negocio.categoria', $categoria)">Ver productos</x-button>
                        <x-button variant="outline" :href="route('productos.create', ['categoria' => $categoria->id])">Agregar producto</x-button>
                        <x-button variant="outline" :href="route('productos.index', ['destino' => $categoria->id])">Mover productos aquí</x-button>
                    </div>

                    <details class="mt-3">
                        <summary class="inline-flex min-h-[44px] cursor-pointer items-center rounded-lg border border-brand-beigeBorder px-4 text-sm font-medium">
                            Editar nombre
                        </summary>
                        <form method="POST" action="{{ route('categorias.update', $categoria) }}" class="mt-3 flex flex-col gap-3">
                            @csrf
                            @method('PUT')
                            <label for="nombre-{{ $categoria->id }}" class="text-sm font-bold">Nombre</label>
                            <input id="nombre-{{ $categoria->id }}" name="nombre" value="{{ $categoria->nombre }}"
                                   maxlength="80" required
                                   class="min-h-[44px] rounded-lg border border-brand-beigeBorder bg-brand-beigeCard px-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-terracotta">
                            <x-button type="submit" variant="terracotta">Guardar cambios</x-button>
                        </form>
                    </details>

                    <div class="mt-3">
                        <x-confirmar :accion="route('categorias.destroy', $categoria)" metodo="DELETE"
                                     etiqueta="Eliminar"
                                     :titulo="'Eliminar ' . $categoria->nombre"
                                     mensaje="Solo se puede eliminar si no tiene productos."
                                     confirmar="Eliminar" />
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Todavía no tienes categorías. Crea la primera abajo.
                </p>
            @endforelse
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-widest">Nueva categoría</h2>
            <form method="POST" action="{{ route('categorias.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-campo nombre="nombre" etiqueta="Nombre de la categoría" placeholder="Entradas, Bebidas, Postres" required />
                <x-button type="submit" variant="terracotta">Crear categoría</x-button>
            </form>
        </section>

        <a href="{{ route('panel.negocio') }}"
           class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-brand-beigeBorder px-4 py-2.5 font-medium text-brand-dark transition-colors duration-200 hover:bg-brand-beigeCard/50 focus:outline-none focus:ring-2 focus:ring-brand-terracotta focus:ring-offset-2">
            Volver
        </a>
    </div>
</x-layouts.app>