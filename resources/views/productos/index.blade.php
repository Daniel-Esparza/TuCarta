<x-layouts.app :title="'Productos - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-extrabold">Productos</h1>
                <x-button :href="route('productos.create')">Agregar producto</x-button>
            </div>
        </header>

        @if (session('exito'))
            <x-alerta>{{ session('exito') }}</x-alerta>
        @endif

        @error('productos')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        <form method="GET" action="{{ route('productos.index') }}" class="grid gap-3 md:grid-cols-[1fr_14rem_14rem_auto] md:items-end">
            @if ($destino)
                <input type="hidden" name="destino" value="{{ $destino }}">
            @endif

            <x-campo nombre="q" etiqueta="Buscar por nombre" tipo="search" :valor="request('q')" />

            <x-select nombre="categoria" etiqueta="Categoría">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((int) request('categoria') === $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </x-select>

            <x-select nombre="disponibilidad" etiqueta="Disponibilidad">
                <option value="">Todos</option>
                <option value="disponible" @selected(request('disponibilidad') === 'disponible')>Disponibles</option>
                <option value="agotado" @selected(request('disponibilidad') === 'agotado')>Agotados</option>
            </x-select>

            <x-button type="submit" class="w-full md:w-auto">Buscar</x-button>
        </form>

        @if ($productos->isEmpty())
            <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                No se encontraron productos.
            </p>
        @else
            <form id="form-mover" method="POST" action="{{ route('productos.mover') }}"
                  class="flex flex-col gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput p-4">
                @csrf
                @foreach (['q', 'categoria', 'disponibilidad'] as $filtro)
                    @if (filled(request($filtro)))
                        <input type="hidden" name="{{ $filtro }}" value="{{ request($filtro) }}">
                    @endif
                @endforeach

                <p class="text-sm font-bold">Cambiar de categoría varios productos a la vez</p>

                <div class="grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-end">
                    <x-select nombre="categoria_id" etiqueta="Mover a la categoría">
                        <option value="">Elige una categoría</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(old('categoria_id', $destino) == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </x-select>

                    <x-button variant="outline" type="button"
                              onclick="document.querySelectorAll('input[form=form-mover]').forEach(casilla => casilla.checked = true)">
                        Marcar esta página
                    </x-button>

                    <x-button type="submit">Mover</x-button>
                </div>

                <label class="flex min-h-[44px] items-center gap-3 text-sm font-medium">
                    <input type="checkbox" name="todos" value="1" class="size-6 accent-brand-terracotta">
                    Mover los {{ $productos->total() }} productos de esta búsqueda, sin marcarlos uno por uno
                </label>
            </form>

            <x-tabla :encabezados="['Nombre', 'Categoría', 'Precio', 'Disponibilidad', 'Acciones']">
                @foreach ($productos as $producto)
                    <x-tabla.fila>
                        <x-tabla.celda etiqueta="Nombre">
                            <label class="inline-flex min-h-[44px] items-center gap-3">
                                <input form="form-mover" type="checkbox" name="productos[]" value="{{ $producto->id }}"
                                       class="size-6 shrink-0 accent-brand-terracotta"
                                       aria-label="Seleccionar {{ $producto->nombre }}">
                                {{ $producto->nombre }}
                            </label>
                        </x-tabla.celda>
                        <x-tabla.celda etiqueta="Categoría">{{ $producto->categoria->nombre }}</x-tabla.celda>
                        <x-tabla.celda etiqueta="Precio">${{ number_format($producto->precio, 2) }}</x-tabla.celda>
                        <x-tabla.celda etiqueta="Disponibilidad"><x-producto-disponibilidad :producto="$producto" /></x-tabla.celda>
                        <x-tabla.celda etiqueta="Acciones">
                            <span class="flex flex-wrap justify-end gap-2 md:justify-start">
                                <x-button variant="outline" :href="route('productos.edit', $producto)">Editar</x-button>
                                <x-button variant="outline" :href="route('grupos.index', $producto)">Opciones</x-button>
                                <x-confirmar :accion="route('productos.destroy', $producto)" metodo="DELETE"
                                             etiqueta="Eliminar"
                                             :titulo="'Eliminar '.$producto->nombre"
                                             mensaje="Se borra del menú junto con su foto y sus opciones. Los pedidos ya hechos no cambian."
                                             confirmar="Eliminar" />
                            </span>
                        </x-tabla.celda>
                    </x-tabla.fila>
                @endforeach
            </x-tabla>

            {{ $productos->links() }}
        @endif

        <x-button variant="outline" :href="route('panel.negocio')">Volver al panel</x-button>
    </div>
</x-layouts.app>
