<x-layouts.app :title="'Nuevo producto - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Nuevo producto</h1>
        </header>

        @if ($categorias->isEmpty())
            <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                Aún no tienes categorías. Crea una primero en la pantalla de Categorías del panel.
            </p>
        @endif

        <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data"
            class="flex flex-col gap-5 rounded-2xl border border-brand-beigeBorder p-6">
            @csrf

            <x-campo nombre="nombre" etiqueta="Nombre" ayuda="Como lo verán tus clientes en la carta." />

            <x-campo nombre="descripcion" etiqueta="Descripción (opcional)" />

            <x-campo nombre="precio" etiqueta="Precio" ayuda="En pesos, por ejemplo 25.50." />

            <x-select nombre="categoria_id" etiqueta="Categoría">
                <option value="" disabled @selected(blank(old('categoria_id', $categoriaInicial)))>Elige una categoría</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $categoriaInicial) == $categoria->id)>
                        {{ $categoria->nombre }}
                    </option>
                @endforeach
            </x-select>

            <x-campo nombre="imagen" etiqueta="Imagen (opcional)" tipo="file"
                ayuda="JPG, PNG o WebP de 5 MB máximo." />

            <div class="space-y-1">
                <span class="block text-sm font-semibold text-brand-dark">Disponibilidad</span>
                <label for="disponible_hoy"
                    class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-lg border border-brand-beigeBorder bg-brand-beigeInput px-3.5 py-2.5 text-sm">
                    <input type="hidden" name="disponible_hoy" value="0" />
                    <input id="disponible_hoy" name="disponible_hoy" type="checkbox" value="1"
                        @checked(old('disponible_hoy', true))
                        class="size-6 accent-brand-terracotta" />
                    <span class="font-semibold text-brand-dark">Disponible hoy</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit">Guardar producto</x-button>
                <x-button variant="outline"
                    :href="$categoriaInicial ? route('panel.negocio.categoria', $categoriaInicial) : route('panel.negocio')">
                    Cancelar
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>