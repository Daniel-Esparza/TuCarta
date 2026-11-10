<x-layouts.app :title="'Panel - '.$negocio->nombre">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel del negocio</p>
            <h1 class="text-xl font-extrabold">Hola Negocio</h1>
            <p class="text-sm text-brand-dark/70">Panel de {{ $negocio->nombre }}. Sesión de {{ auth()->user()->email }}.</p>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
            <x-tarjeta-enlace :href="route('negocio.publico')" titulo="Ver mi página">
                Así ven tu negocio tus clientes.
            </x-tarjeta-enlace>

            @if (auth()->user()->tienePermiso(App\Enums\ClavePermiso::EditarPerfil))
                <x-tarjeta-enlace :href="route('perfil.edit')" titulo="Perfil del negocio">
                    Nombre, descripción, dirección, WhatsApp y logo.
                </x-tarjeta-enlace>
                <x-tarjeta-enlace :href="route('categorias.index')" titulo="Categorías del menú">
                    Crea, edita, elimina y ordena tus categorías.
                </x-tarjeta-enlace>
                <x-tarjeta-enlace :href="route('productos.index')" titulo="Productos del menú">
                    Busca, filtra, edita y elimina tus productos.
                </x-tarjeta-enlace>
                <x-tarjeta-enlace :href="route('horarios.index')" titulo="Horarios de atención">
                    Define qué días y a qué horas atiendes.
                </x-tarjeta-enlace>
                <x-tarjeta-enlace :href="route('cierre-temporal.index')" titulo="Cierre temporal">
                    Deja de recibir pedidos cuando lo necesites.
                </x-tarjeta-enlace>
            @endif

            @if (auth()->user()->tienePermiso(App\Enums\ClavePermiso::GestionarEquipo))
                <x-tarjeta-enlace :href="route('equipo.index')" titulo="Equipo de trabajo">
                    Da de alta y desactiva a tus empleados.
                </x-tarjeta-enlace>
            @endif
        </div>

        <section class="flex flex-col gap-3">
            <h2 class="text-xs font-bold uppercase tracking-widest">Categorías</h2>
            @forelse ($categorias as $categoria)
                <a href="{{ route('panel.negocio.categoria', $categoria) }}"
                   class="flex min-h-[44px] items-center justify-between gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeInput px-4 py-3 text-sm transition-colors duration-200 hover:border-brand-terracotta focus:outline-none focus:ring-2 focus:ring-brand-terracotta">
                    <span class="font-bold">{{ $categoria->nombre }}</span>
                    <span class="text-xs text-brand-dark/70">{{ $categoria->items_count }} productos</span>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-brand-beigeBorder px-4 py-6 text-center text-sm text-brand-dark/60">
                    Este negocio todavía no tiene categorías.
                </p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
