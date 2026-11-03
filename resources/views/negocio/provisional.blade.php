<x-layouts.app :title="$negocio->nombre">
    <section class="mx-auto mt-6 flex w-full max-w-md flex-col items-center gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-6 py-10 text-center">
        @if ($negocio->logo_path)
            <img src="{{ Storage::url($negocio->logo_path) }}" alt="Logo de {{ $negocio->nombre }}"
                 class="h-20 w-20 rounded-full border border-brand-beigeBorder object-cover">
        @endif

        <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Próximamente</p>
        <h1 class="text-2xl font-extrabold">{{ $negocio->nombre }}</h1>

        @if ($negocio->descripcion)
            <p class="text-sm text-brand-dark/70">{{ $negocio->descripcion }}</p>
        @endif

        <p class="text-sm text-brand-dark/70">Estamos preparando la carta de este negocio. Vuelve pronto.</p>

        <div class="mt-2 space-y-1 text-sm text-brand-dark/80">
            @if ($negocio->direccion)
                <p>{{ $negocio->direccion }}</p>
            @endif
            @if ($negocio->telefono_whatsapp)
                <p>WhatsApp: {{ $negocio->telefono_whatsapp }}</p>
            @endif
        </div>
    </section>
</x-layouts.app>