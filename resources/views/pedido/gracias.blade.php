<x-layouts.app title="Pedido recibido">
    <section class="mx-auto mt-6 flex w-full max-w-md flex-col items-center gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-6 py-10 text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Pedido recibido</p>
        <h1 class="text-2xl font-extrabold">Folio {{ $folio }}</h1>
        <p class="text-sm text-brand-dark/70">Guarda este folio para consultar tu pedido.</p>

        <a id="btn-seguir-pedido"
           href="{{ route('seguimiento.mostrar', ['folio' => $folio]) }}"
           class="inline-flex w-full items-center justify-center rounded-xl border border-brand-terracotta px-5 py-3 text-sm font-bold text-brand-terracotta transition hover:bg-brand-terracotta/5">
            Seguir mi pedido
        </a>

        @if (! empty($whatsappUrl))
            <a id="btn-abrir-whatsapp"
               href="{{ $whatsappUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-brand-terracotta px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:opacity-95">
                Abrir WhatsApp
            </a>
            <p class="text-xs text-brand-dark/60">
                Si WhatsApp no se abrió automáticamente, usa el botón de arriba para enviar tu pedido.
            </p>
        @else
            <div class="mt-2 w-full rounded-xl border border-brand-beigeBorder bg-white/70 p-4 text-sm text-brand-dark/80">
                Este negocio no tiene un número de WhatsApp disponible en este momento. Conserva tu folio <strong>{{ $folio }}</strong> para confirmar tu pedido.
            </div>
        @endif

        <a href="{{ route('negocio.publico') }}" class="mt-2 text-xs font-semibold text-brand-terracotta underline">
            Volver al menú
        </a>
    </section>

    <script>
        (function () {
            var llaveCarrito = @json('tucarta.carrito.' . $negocio->id);
            var llaveMarca = @json('tucarta.wa_abierto.' . $folio);
            var whatsappUrl = @json($whatsappUrl ?? null);

            try {
                localStorage.removeItem(llaveCarrito);
            } catch (e) {}

            if (whatsappUrl) {
                try {
                    if (!sessionStorage.getItem(llaveMarca)) {
                        sessionStorage.setItem(llaveMarca, '1');
                        window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
                    }
                } catch (e) {}
            }
        })();
    </script>
</x-layouts.app>
