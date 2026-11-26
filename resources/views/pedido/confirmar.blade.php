<x-layouts.app title="Confirmar pedido">
    <section class="mx-auto mt-6 w-full max-w-md space-y-4 rounded-2xl border border-brand-beigeBorder bg-brand-beigeCard px-6 py-8">
        <h1 class="text-2xl font-extrabold">Confirmar pedido</h1>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('pedido.store') }}" class="space-y-4">
            @csrf
            {{-- US-13 llena este campo con el carrito guardado en el navegador --}}
            <input type="hidden" name="carrito" id="carrito-input" value="{{ old('carrito') }}">

            <label class="block text-sm font-bold">
                Tu nombre
                <input name="cliente_nombre" value="{{ old('cliente_nombre') }}" maxlength="120" required
                       class="mt-1 w-full rounded-lg border px-3 py-2">
            </label>

            <label class="block text-sm font-bold">
                Nota (opcional)
                <textarea name="notas" maxlength="500" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('notas') }}</textarea>
            </label>

            <button type="submit" class="w-full rounded-lg bg-brand-terracotta px-4 py-3 font-bold text-white">
                Confirmar pedido
            </button>
        </form>
    </section>
</x-layouts.app>
