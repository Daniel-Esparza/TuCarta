<x-layouts.app :title="'Tu carrito — ' . $negocio->nombre">
    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Regresar al menú --}}
        <a href="{{ url('/') }}"
           class="inline-flex items-center gap-1 text-sm text-brand-dark/60 hover:text-brand-terracotta">
            ← Regresar al menú
        </a>

        {{-- Encabezado --}}
        <div class="space-y-1">
            <h1 class="text-2xl font-extrabold">Tu carrito</h1>
            <p class="text-sm text-brand-dark/60">
                Tu selección de <strong>{{ $negocio->nombre }}</strong> se guarda en este
                navegador. Revisa tus líneas, ajusta cantidades y verifica el total
                estimado en el panel que se abre abajo.
            </p>
        </div>

        {{-- Estado vacío / ayuda --}}
        <div class="rounded-2xl bg-brand-beigeBorder p-4 text-sm text-brand-dark/60">
            ¿Todavía no agregas nada? Vuelve al menú, elige un producto disponible y toca
            <strong>Agregar</strong>; el carrito aparecerá aquí y en la barra inferior de
            cualquier página del menú.
        </div>
    </div>

    {{-- US-13: monta barra fija, panel y modal de opciones --}}
    <x-carrito-scripts :negocio="$negocio" :items="$itemsMenu" />

    {{-- Al llegar con líneas, abrir el panel directamente --}}
    <script>
        window.addEventListener('load', function () {
            var api = window.TuCartaCarrito;
            if (api && typeof api.abrirPanel === 'function' && api.lineas().length > 0) {
                api.abrirPanel();
            }
        });
    </script>
</x-layouts.app>
