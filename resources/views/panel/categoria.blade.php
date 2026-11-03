<x-layouts.app :title="$categoria->nombre.' - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">{{ $categoria->nombre }}</h1>
        </header>

        <section class="flex flex-col gap-3">
            @forelse ($categoria->items as $item)
                <article class="flex items-center justify-between gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3 text-sm">
                    <span class="font-bold">{{ $item->nombre }}{{ $item->disponible_hoy ? '' : ' (agotado)' }}</span>
                    <span>${{ number_format($item->precio, 2) }}</span>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Esta categoría no tiene productos.
                </p>
            @endforelse
        </section>

        <x-button variant="outline" :href="route('panel.negocio')">Volver al panel</x-button>
    </div>
</x-layouts.app>
