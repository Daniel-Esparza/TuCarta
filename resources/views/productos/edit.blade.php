<x-layouts.app :title="'Editar producto - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Editar producto</h1>
            <p class="text-sm text-brand-dark/70">{{ $producto->nombre }}</p>
        </header>

        <form method="POST" action="{{ route('productos.update', $producto) }}" enctype="multipart/form-data"
            class="flex flex-col gap-5 rounded-2xl border border-brand-beigeBorder p-6">
            @csrf
            @method('PUT')

            <x-campo nombre="nombre" etiqueta="Nombre" :valor="$producto->nombre" />

            <x-campo nombre="descripcion" etiqueta="Descripción (opcional)" :valor="$producto->descripcion" />

            <x-campo nombre="precio" etiqueta="Precio" :valor="$producto->precio" ayuda="En pesos, por ejemplo 25.50." />

            <x-select nombre="categoria_id" etiqueta="Categoría">
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </x-select>

            @if ($producto->imagen)
                <div class="space-y-1">
                    <span class="block text-sm font-semibold text-brand-dark">Imagen actual</span>
                    <img src="{{ Storage::url($producto->imagen) }}" alt="Imagen actual de {{ $producto->nombre }}"
                        class="h-24 w-24 rounded-xl border border-brand-beigeBorder object-cover" />
                </div>
            @endif

            <x-campo nombre="imagen" etiqueta="Cambiar imagen (opcional)" tipo="file"
                ayuda="JPG, PNG o WebP de 5 MB máximo. Se borra la anterior al guardar." />

            <div class="space-y-1">
                <span class="block text-sm font-semibold text-brand-dark">Disponibilidad</span>
                <label for="disponible_hoy"
                    class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-lg border border-brand-beigeBorder bg-brand-beigeInput px-3.5 py-2.5 text-sm">
                    <input type="hidden" name="disponible_hoy" value="0" />
                    <input id="disponible_hoy" name="disponible_hoy" type="checkbox" value="1"
                        @checked(old('disponible_hoy', $producto->disponible_hoy))
                        class="size-6 accent-brand-terracotta" />
                    <span class="font-semibold text-brand-dark">Disponible hoy</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit">Guardar cambios</x-button>
                <x-button variant="outline" :href="route('panel.negocio.categoria', $producto->categoria_id)">
                    Cancelar
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>