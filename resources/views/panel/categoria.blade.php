@php($puedeEditar = auth()->user()->tienePermiso(App\Enums\ClavePermiso::EditarPerfil))

<x-layouts.app :title="$categoria->nombre.' - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-extrabold">{{ $categoria->nombre }}</h1>
                @if ($puedeEditar)
                    <span class="flex flex-wrap gap-3">
                        <x-button variant="outline" :href="route('productos.index', ['destino' => $categoria->id])">Mover productos aquí</x-button>
                        <x-button :href="route('productos.create', ['categoria' => $categoria->id])">Agregar producto</x-button>
                    </span>
                @endif
            </div>
        </header>

        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if (session('error'))
            <x-alerta tipo="error">{{ session('error') }}</x-alerta>
        @endif

        <section class="flex flex-col gap-3">
            @forelse ($categoria->items as $item)
                <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3 text-sm">
                    <span class="font-bold">{{ $item->nombre }}</span>
                    <span class="flex flex-wrap items-center gap-3">
                        <span>${{ number_format($item->precio, 2) }}</span>
                        <x-producto-disponibilidad :producto="$item" />
                        @if ($puedeEditar)
                            <a href="{{ route('productos.edit', $item) }}"
                               class="font-semibold text-brand-terracotta underline underline-offset-2">Editar</a>
                        @endif
                    </span>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Esta categoría todavía no tiene productos.
                </p>
            @endforelse
        </section>

        <x-button variant="outline" :href="$puedeEditar ? route('categorias.index') : route('panel.negocio')">Volver</x-button>
    </div>
</x-layouts.app>
