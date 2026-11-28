<x-layouts.app title="Seguimiento de tu pedido">
    <div class="mx-auto mt-6 w-full max-w-md px-4">
        <div class="flex flex-col gap-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard p-6">
            <h1 class="text-xl font-extrabold text-brand-dark">Pedido {{ $pedido->folio }}</h1>
            <p class="text-sm text-brand-dark">
                Estado actual:
                <strong>{{ $pasos->last()?->estado_nuevo?->value ?? $pasos->last()?->estado_nuevo }}</strong>
            </p>

            <h2 class="mt-2 text-base font-bold text-brand-dark">Pasos del pedido</h2>
            <ol class="flex flex-col gap-2 text-sm text-brand-dark">
                @foreach ($pasos as $paso)
                    <li class="flex items-center justify-between border-b border-brand-beigeBorder pb-2">
                        <span>{{ $paso->estado_nuevo?->value ?? $paso->estado_nuevo }}</span>
                        <span class="text-xs text-brand-dark/70">{{ \Illuminate\Support\Carbon::parse($paso->created_at)->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="pt-2">
                <a href="{{ route('seguimiento.formulario') }}" class="text-sm font-bold text-brand-terracotta underline">
                    Consultar otro folio
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
