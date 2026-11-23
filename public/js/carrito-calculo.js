/*
 * US-13: cálculo puro del carrito (sin tocar el DOM).
 * Se usa igual desde el navegador (window.TuCartaCalculo) y desde las
 * pruebas (require de node), por eso el envoltorio UMD.
 */
(function (raiz, fabrica) {
    if (typeof module === 'object' && module.exports) { module.exports = fabrica(); }
    else { raiz.TuCartaCalculo = fabrica(); }
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function opcionesPorId(item) {
        var mapa = {};
        (item.grupos || []).forEach(function (g) {
            (g.opciones || []).forEach(function (o) { mapa[o.id] = o; });
        });
        return mapa;
    }

    // Acepta incremento (nombre del módulo) e incremento_precio (columna real).
    function incrementoDe(opcion) {
        var valor = opcion.incremento;
        if (valor === undefined || valor === null) { valor = opcion.incremento_precio; }
        return Number(valor) || 0;
    }

    function ordenar(ids) {
        return (ids || []).slice().sort(function (a, b) { return a - b; });
    }

    // Suma de los incrementos de las opciones elegidas (US-18).
    function incrementoDeOpciones(item, opcionIds) {
        var mapa = opcionesPorId(item), suma = 0;
        ordenar(opcionIds).forEach(function (id) {
            if (mapa[id]) { suma += incrementoDe(mapa[id]); }
        });
        return suma;
    }

    // Precio unitario con opciones incluidas.
    function precioUnitario(item, opcionIds) {
        return (Number(item.precio) || 0) + incrementoDeOpciones(item, opcionIds);
    }

    function subtotalLinea(linea, item) {
        return precioUnitario(item, linea.opciones) * linea.cantidad;
    }

    // Total estimado en pantalla (US-13); el servidor recalcula al pedir (US-14a/US-24).
    function total(lineas, items) {
        var porId = {};
        items.forEach(function (i) { porId[i.id] = i; });
        return lineas.reduce(function (acum, linea) {
            var item = porId[linea.producto_id];
            return item ? acum + subtotalLinea(linea, item) : acum;
        }, 0);
    }

    // Identidad de línea: producto + opciones elegidas (ordenadas).
    // El mismo producto con opciones distintas ocupa líneas distintas.
    function claveLinea(linea) {
        return linea.producto_id + '|' + ordenar(linea.opciones).join(',');
    }

    // Estructura que se manda al servidor al pedir (acuerdo US-13/US-14a):
    // producto, cantidad y opciones elegidas. NUNCA precios ni totales.
    function estructuraPedido(negocioId, lineas) {
        return {
            negocio_id: negocioId,
            lineas: lineas.map(function (l) {
                return { producto_id: l.producto_id, cantidad: l.cantidad, opciones: ordenar(l.opciones) };
            })
        };
    }

    return {
        precioUnitario: precioUnitario,
        subtotalLinea: subtotalLinea,
        total: total,
        claveLinea: claveLinea,
        estructuraPedido: estructuraPedido,
        incrementoDeOpciones: incrementoDeOpciones
    };
});
