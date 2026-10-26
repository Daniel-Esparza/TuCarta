<x-layouts.app :title="$negocio->nombre">
    <section class="mx-auto mt-6 flex w-full max-w-md flex-col items-center gap-3 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-6 py-10 text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-brand-terracotta">Próximamente</p>
        <h1 class="text-2xl font-extrabold">{{ $negocio->nombre }}</h1>
        <p class="text-sm text-brand-dark/70">Estamos preparando la carta de este negocio. Vuelve pronto.</p>
    </section>
</x-layouts.app>