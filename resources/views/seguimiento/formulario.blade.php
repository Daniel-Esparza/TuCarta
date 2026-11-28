@php
    $subdominioActual = $subdominio
        ?? request()->route('subdominio')
        ?? app(\App\Support\NegocioActual::class)->negocio()?->subdominio;
@endphp

<x-layouts.app title="Consulta tu pedido">
    <main class="mx-auto mt-6 w-full max-w-md px-4">
        @if (! empty($limitado))
            <p role="alert" class="mb-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-4 py-3 text-sm font-medium text-brand-dark">
                Hiciste demasiadas consultas seguidas. Espera un minuto e inténtalo de nuevo.
            </p>
        @endif

        @if (! empty($noEncontrado))
            <p role="alert" class="mb-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-4 py-3 text-sm font-medium text-brand-dark">
                No encontramos un pedido con el folio {{ $folio }}. Revisa que esté bien escrito.
            </p>
        @endif

        <form method="POST" action="{{ route('seguimiento.buscar', ['subdominio' => $subdominioActual]) }}" class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-6">
            @csrf
            <h1 class="text-xl font-extrabold text-brand-dark">Consulta tu pedido</h1>
            <p class="text-sm text-brand-dark/70">Escribe el folio que te dimos al enviar tu pedido.</p>

            <x-campo nombre="folio" etiqueta="Folio del pedido" required autofocus />

            <button type="submit" class="rounded-xl bg-brand-terracotta px-4 py-2.5 text-sm font-bold text-white">
                Consultar
            </button>
        </form>
    </main>
</x-layouts.app>
