@props(['negocio', 'items'])

{{--
    US-13: datos y scripts del carrito (localStorage por negocio).
    Los precios viajan al navegador SOLO para pintar el total estimado;
    lo que se envía al servidor al pedir (US-14a) lo arma
    TuCartaCalculo.estructuraPedido() y nunca incluye precios.
--}}
@php
    $payload = [
        'negocio_id' => $negocio->id,
        'url_pedido' => route('pedido.confirmar'),
        'items' => $items->map(fn ($item) => [
            'id'             => $item->id,
            'nombre'         => $item->nombre,
            'precio'         => (float) $item->precio,
            'disponible_hoy' => (bool) $item->disponible_hoy,
            'grupos'         => $item->gruposOpciones->map(fn ($g) => [
                'id'             => $g->id,
                'nombre'         => $g->nombre,
                'es_multiple'    => (bool) $g->es_multiple,
                'es_obligatorio' => (bool) $g->es_obligatorio,
                'min'            => (int) $g->min_selecciones,
                'max'            => (int) $g->max_selecciones,
                'opciones'       => $g->opciones->map(fn ($o) => [
                    'id'         => $o->id,
                    'nombre'     => $o->nombre,
                    'incremento' => (float) $o->incremento_precio,
                ])->values(),
            ])->values(),
        ])->values(),
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/carrito.css') }}">
<div id="carrito-raiz" class="carrito-raiz"></div>
<script>window.__TUCARTA_MENU__ = @json($payload);</script>
<script src="{{ asset('js/carrito-calculo.js') }}" defer></script>
<script src="{{ asset('js/carrito-ui.js') }}" defer></script>
