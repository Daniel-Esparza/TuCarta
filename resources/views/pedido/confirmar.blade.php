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

            {{-- US-20: tipo de pedido --}}
            <fieldset class="space-y-2">
                <legend class="text-sm font-bold">¿Cómo quieres tu pedido?</legend>
                @foreach ([
                    'en_sitio' => 'Consumo en el lugar',
                    'para_llevar' => 'Para llevar',
                    'domicilio' => 'Envío a domicilio',
                ] as $valor => $texto)
                    <label class="flex items-center gap-3 rounded-lg border bg-white px-3 py-2 text-sm font-semibold">
                        <input type="radio" name="tipo_entrega" value="{{ $valor }}" required
                               @checked(old('tipo_entrega') === $valor)>
                        {{ $texto }}
                    </label>
                @endforeach
            </fieldset>

            <div id="campos-domicilio" class="space-y-4" @if (old('tipo_entrega') !== 'domicilio') hidden @endif>
                <label class="block text-sm font-bold">
                    Dirección
                    <input name="direccion_entrega" value="{{ old('direccion_entrega') }}" maxlength="255"
                           class="mt-1 w-full rounded-lg border px-3 py-2">
                </label>

                <label class="block text-sm font-bold">
                    Referencias (opcional)
                    <input name="referencias_entrega" value="{{ old('referencias_entrega') }}" maxlength="255"
                           class="mt-1 w-full rounded-lg border px-3 py-2">
                </label>

                <p class="text-xs text-gray-600">
                    El costo y la forma del envío se acuerdan directamente con el negocio por WhatsApp.
                </p>
            </div>

            <label class="block text-sm font-bold">
                Nota (opcional)
                <textarea name="notas" maxlength="500" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('notas') }}</textarea>
            </label>

            <button type="submit" class="w-full rounded-lg bg-brand-terracotta px-4 py-3 font-bold text-white">
                Confirmar pedido
            </button>
        </form>
    </section>

    <script>
        (function () {
            var radios = document.querySelectorAll('input[name="tipo_entrega"]');
            var bloque = document.getElementById('campos-domicilio');
            var campos = bloque.querySelectorAll('input');

            function actualizar() {
                var elegido = document.querySelector('input[name="tipo_entrega"]:checked');
                var esDomicilio = elegido && elegido.value === 'domicilio';
                bloque.hidden = !esDomicilio;
                campos.forEach(function (c) { c.disabled = !esDomicilio; });
            }

            radios.forEach(function (r) { r.addEventListener('change', actualizar); });
            actualizar();
        })();
    </script>
</x-layouts.app>
