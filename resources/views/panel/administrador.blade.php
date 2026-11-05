<x-layouts.app title="Panel central - TuCarta">
    <div class="flex flex-col gap-6">
        <header class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Panel central</p>
            <h1 class="text-xl font-extrabold">Hola Administrador</h1>
            <p class="text-sm text-brand-dark/70">Sesión de {{ auth()->user()->email }}.</p>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
            <x-tarjeta-enlace :href="route('negocios.index')" titulo="Lista y estado de negocios">
                Busca, filtra, suspende o reactiva los negocios que tienes a tu cargo.
            </x-tarjeta-enlace>

            @can('revisarPendientes', App\Models\Negocio::class)
                <x-tarjeta-enlace :href="route('negocios.pendientes')" titulo="Negocios pendientes">
                    Aprueba o rechaza las solicitudes del auto-registro.
                </x-tarjeta-enlace>
            @endcan

            @can('create', App\Models\Negocio::class)
                <x-tarjeta-enlace :href="route('negocios.create')" titulo="Alta de negocio">
                    Da de alta un negocio con la cuenta de su dueño.
                </x-tarjeta-enlace>
            @endcan

            @can('create', App\Models\Usuario::class)
                <x-tarjeta-enlace :href="route('moderadores.index')" titulo="Moderadores y permisos">
                    Crea moderadores y asígnales permisos.
                </x-tarjeta-enlace>
            @endcan
        </div>
    </div>
</x-layouts.app>
