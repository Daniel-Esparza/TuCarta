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

        {{-- Resumen del carrito guardado en este navegador --}}
        <div id="resumen-carrito" class="space-y-2 rounded-xl border border-brand-beigeBorder bg-white/70 p-4 text-sm"></div>

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

            <button type="submit" id="boton-confirmar" class="w-full rounded-lg bg-brand-terracotta px-4 py-3 font-bold text-white">
                Confirmar pedido
            </button>
        </form>
    </section>

    <script>
        (function () {
            var llave = @json('tucarta.carrito.' . $negocio->id);
            var campo = document.getElementById('carrito-input');
            var resumen = document.getElementById('resumen-carrito');
            var boton = document.getElementById('boton-confirmar');
            var lineas = [];

            try { lineas = JSON.parse(localStorage.getItem(llave) || '[]'); } catch (e) {}
            if (!Array.isArray(lineas)) { lineas = []; }

            function parrafo(texto, clase) {
                var p = document.createElement('p');
                p.textContent = texto;
                if (clase) { p.className = clase; }
                return p;
            }

            if (lineas.length === 0) {
                resumen.appendChild(parrafo('Tu carrito está vacío. Vuelve al menú y agrega tus productos.'));
                boton.disabled = true;
                boton.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            // El servidor espera item_id, cantidad y opciones; los precios los calcula él (US-14a).
            campo.value = JSON.stringify(lineas.map(function (l) {
                return { item_id: l.producto_id, cantidad: l.cantidad, opciones: l.opciones || [] };
            }));

            var total = 0;
            resumen.appendChild(parrafo('Tu pedido', 'font-bold'));
            var lista = document.createElement('ul');
            lista.className = 'space-y-1';
            lineas.forEach(function (l) {
                var li = document.createElement('li');
                li.textContent = l.cantidad + ' × ' + l.nombre + (l.opciones_texto ? ' (' + l.opciones_texto + ')' : '');
                lista.appendChild(li);
                total += (l.precio_unitario || 0) * l.cantidad;
            });
            resumen.appendChild(lista);
            resumen.appendChild(parrafo('Total estimado: $' + total.toFixed(2) + ' (el negocio confirma el precio final)', 'font-semibold'));
        })();
    </script>

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
