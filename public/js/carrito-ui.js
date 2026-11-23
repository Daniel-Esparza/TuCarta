/*
 * US-13: interfaz del carrito del menú público.
 * - localStorage separado por negocio: tucarta.carrito.{negocio_id}
 * - Barra fija inferior siempre visible; panel (drawer) y modal de opciones.
 * - API pública: window.TuCartaCarrito (la usa el detalle y US-14a).
 */
(function () {
    'use strict';

    var CALC = window.TuCartaCalculo;
    var MENU = window.__TUCARTA_MENU__;
    if (!CALC || !MENU || !document.getElementById('carrito-raiz')) { return; }

    var LLAVE = 'tucarta.carrito.' + MENU.negocio_id;
    var raiz = document.getElementById('carrito-raiz');
    var itemPorId = {};
    MENU.items.forEach(function (i) { itemPorId[i.id] = i; });

    var carrito = cargar();
    var modal = null;

    /* ---------- persistencia ---------- */

    function cargar() {
        try {
            var lineas = JSON.parse(localStorage.getItem(LLAVE) || '[]');
            return Array.isArray(lineas) ? lineas : [];
        } catch (e) { return []; }
    }

    function persistir() {
        try { localStorage.setItem(LLAVE, JSON.stringify(carrito)); }
        catch (e) { /* almacenamiento bloqueado: el menú sigue funcionando */ }
    }

    /* ---------- helpers ---------- */

    function dinero(n) { return '$' + (Math.round(n * 100) / 100).toFixed(2); }

    function escapeHtml(texto) {
        return String(texto).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function nombreOpciones(item, opcionIds) {
        var nombres = [];
        (item && item.grupos || []).forEach(function (g) {
            (g.opciones || []).forEach(function (o) {
                if (opcionIds.indexOf(o.id) !== -1) { nombres.push(o.nombre); }
            });
        });
        return nombres.join(', ');
    }

    /* ---------- operaciones ---------- */

    function lineasDe(itemId) {
        return carrito.filter(function (l) { return l.producto_id === itemId; });
    }

    function buscarLinea(itemId, opcionIds) {
        var clave = CALC.claveLinea({ producto_id: itemId, opciones: opcionIds });
        for (var i = 0; i < carrito.length; i++) {
            if (CALC.claveLinea(carrito[i]) === clave) { return i; }
        }
        return -1;
    }

    function agregar(itemId, opcionIds, cantidad) {
        var item = itemPorId[itemId];
        if (!item || !item.disponible_hoy) { return; } // agotado no se agrega (US-08)
        opcionIds = (opcionIds || []).slice().sort(function (a, b) { return a - b; });
        cantidad = Math.max(1, parseInt(cantidad, 10) || 1);

        var i = buscarLinea(itemId, opcionIds);
        if (i === -1) {
            carrito.push({
                producto_id: itemId,
                cantidad: cantidad,
                opciones: opcionIds,
                nombre: item.nombre,
                precio_unitario: CALC.precioUnitario(item, opcionIds),
                opciones_texto: nombreOpciones(item, opcionIds)
            });
        } else {
            carrito[i].cantidad += cantidad;
            carrito[i].precio_unitario = CALC.precioUnitario(item, opcionIds); // precio fresco
        }
        persistir();
        pintar();
    }

    function cambiarCantidad(indice, delta) {
        if (!carrito[indice]) { return; }
        carrito[indice].cantidad += delta;
        if (carrito[indice].cantidad < 1) { carrito.splice(indice, 1); }
        persistir();
        pintar();
    }

    function cambiarItemSimple(itemId, delta) {
        var lineas = lineasDe(itemId);
        if (lineas.length === 0) { return; }
        cambiarCantidad(carrito.indexOf(lineas[0]), delta);
    }

    function quitar(indice) {
        carrito.splice(indice, 1);
        persistir();
        pintar();
    }

    function cantidadTotal() {
        return carrito.reduce(function (a, l) { return a + l.cantidad; }, 0);
    }

    function totalEstimado() {
        var total = 0;
        carrito.forEach(function (l) {
            var item = itemPorId[l.producto_id];
            total += item ? CALC.subtotalLinea(l, item) : (l.precio_unitario || 0) * l.cantidad;
        });
        return total;
    }

    /* ---------- pintado ---------- */

    function pintar() {
        pintarZonas();
        pintarRaiz();
        if (raiz.querySelector('.carrito-panel')) { pintarPanel(); }
    }

    function pintarZonas() {
        var zonas = document.querySelectorAll('[data-zona-carrito]');
        for (var z = 0; z < zonas.length; z++) {
            var zona = zonas[z];
            var id = parseInt(zona.getAttribute('data-zona-carrito'), 10);
            var item = itemPorId[id];
            if (!item) { continue; }

            var cantidad = 0;
            lineasDe(id).forEach(function (l) { cantidad += l.cantidad; });
            var tieneGrupos = (item.grupos || []).length > 0;

            if (cantidad === 0) {
                zona.innerHTML = '<button type="button" class="carrito-boton-agregar" data-carrito-agregar="' + id + '">Agregar</button>';
            } else if (tieneGrupos) {
                zona.innerHTML = '<button type="button" class="carrito-boton-agregar" data-carrito-ver="' + id + '">' + cantidad + ' en el carrito</button>';
            } else {
                zona.innerHTML =
                    '<span class="carrito-stepper">' +
                    '<button type="button" aria-label="Quitar uno" data-carrito-menos="' + id + '">&minus;</button>' +
                    '<span class="carrito-cantidad">' + cantidad + '</span>' +
                    '<button type="button" aria-label="Agregar uno" data-carrito-mas="' + id + '">&plus;</button>' +
                    '</span>';
            }
        }
    }

    function pintarRaiz() {
        if (carrito.length === 0) {
            document.body.classList.remove('tucarta-con-barra');
            raiz.innerHTML = '';
            return;
        }
        document.body.classList.add('tucarta-con-barra');
        raiz.innerHTML =
            '<div class="carrito-barra">' +
            '<span class="carrito-barra-info">' + cantidadTotal() + ' en el carrito &middot; ' +
            '<strong>Total estimado: ' + dinero(totalEstimado()) + '</strong> <span class="carrito-nota">(sin env&iacute;o)</span></span>' +
            '<button type="button" class="carrito-boton-agregar" data-carrito-abrir="1">Ver carrito</button>' +
            '</div>';
    }

    function cerrarCapas() {
        var capas = raiz.querySelectorAll('.carrito-fondo, .carrito-panel');
        for (var i = 0; i < capas.length; i++) { capas[i].parentNode.removeChild(capas[i]); }
    }

    function pintarPanel() {
        cerrarCapas();
        if (carrito.length === 0) { return; }

        var fondo = document.createElement('div');
        fondo.className = 'carrito-fondo';
        fondo.setAttribute('data-carrito-cerrar', '1');

        var panel = document.createElement('aside');
        panel.className = 'carrito-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Carrito de compra');

        var html = '<header class="carrito-panel-encabezado"><h3>Tu carrito</h3>' +
            '<button type="button" class="carrito-cerrar" data-carrito-cerrar="1" aria-label="Cerrar">&times;</button></header>' +
            '<div class="carrito-panel-lineas">';

        carrito.forEach(function (l, i) {
            var item = itemPorId[l.producto_id];
            var unitario = item ? CALC.precioUnitario(item, l.opciones) : (l.precio_unitario || 0);
            html += '<article class="carrito-linea">' +
                '<div class="carrito-linea-datos">' +
                '<p class="carrito-linea-nombre">' + escapeHtml(l.nombre) + '</p>' +
                (l.opciones_texto ? '<p class="carrito-linea-opciones">' + escapeHtml(l.opciones_texto) + '</p>' : '') +
                '<p class="carrito-linea-precio">' + dinero(unitario) + ' c/u</p>' +
                '</div><div class="carrito-linea-controles">' +
                '<span class="carrito-stepper">' +
                '<button type="button" aria-label="Disminuir" data-carrito-linea-menos="' + i + '">&minus;</button>' +
                '<span class="carrito-cantidad">' + l.cantidad + '</span>' +
                '<button type="button" aria-label="Aumentar" data-carrito-linea-mas="' + i + '">&plus;</button>' +
                '</span>' +
                '<span class="carrito-linea-subtotal">' + dinero(unitario * l.cantidad) + '</span>' +
                '<button type="button" class="carrito-quitar" data-carrito-quitar="' + i + '">Quitar</button>' +
                '</div></article>';
        });

        html += '</div><footer class="carrito-panel-pie">' +
            '<p class="carrito-total">Total estimado: <strong>' + dinero(totalEstimado()) + '</strong></p>' +
            '<p class="carrito-nota">Estimado con precios actuales; no incluye env&iacute;o. Se confirma al pedir.</p>' +
            '<button type="button" class="carrito-boton-agregar carrito-boton-bloqueado" disabled>Realizar pedido</button>' +
            '<p class="carrito-nota">La confirmaci&oacute;n del pedido se habilita con US-14a.</p>' +
            '</footer>';

        panel.innerHTML = html;
        raiz.appendChild(fondo);
        raiz.appendChild(panel);
    }

    /* ---------- modal de opciones (US-18) ---------- */

    function abrirModal(item) {
        cerrarModal();
        modal = document.createElement('div');
        modal.className = 'carrito-modal-fondo';
        modal.setAttribute('data-carrito-cerrar-modal', '1');

        var caja = document.createElement('form');
        caja.className = 'carrito-modal-caja';
        caja.setAttribute('role', 'dialog');
        caja.setAttribute('aria-label', 'Elegir opciones');

        var html = '<h3 class="carrito-modal-titulo">' + escapeHtml(item.nombre) + '</h3>';
        (item.grupos || []).forEach(function (g) {
            html += '<fieldset class="carrito-modal-grupo"><legend>' + escapeHtml(g.nombre) +
                (g.es_obligatorio ? ' (obligatorio)' : ' (opcional)') + '</legend>';
            (g.opciones || []).forEach(function (o) {
                var extra = o.incremento ? ' (+$' + o.incremento.toFixed(2) + ')' : '';
                html += '<label class="carrito-modal-opcion">' +
                    '<input type="' + (g.es_multiple ? 'checkbox' : 'radio') + '" name="grupo-' + g.id + '" value="' + o.id + '"> ' +
                    escapeHtml(o.nombre) + extra + '</label>';
            });
            html += '</fieldset>';
        });
        html += '<p class="carrito-modal-error" data-carrito-modal-error hidden></p>' +
            '<div class="carrito-modal-acciones">' +
            '<button type="button" class="carrito-boton-agregar" data-carrito-cerrar-modal="1">Cancelar</button>' +
            '<button type="submit" class="carrito-boton-agregar">Agregar al carrito</button>' +
            '</div>';

        caja.innerHTML = html;
        caja.addEventListener('submit', function (evento) {
            evento.preventDefault();
            confirmarModal(item, caja);
        });
        modal.appendChild(caja);
        document.body.appendChild(modal);
    }

    function confirmarModal(item, caja) {
        var elegidas = [];
        var error = caja.querySelector('[data-carrito-modal-error]');
        var mensaje = '';

        (item.grupos || []).forEach(function (g) {
            var marcadas = caja.querySelectorAll('input[name="grupo-' + g.id + '"]:checked');
            var minimo = g.es_obligatorio ? Math.max(1, g.min) : g.min;
            if (marcadas.length < minimo) {
                mensaje = 'Elige ' + (minimo === 1 ? 'una opci\u00f3n' : minimo + ' opciones') + ' en "' + g.nombre + '".';
            }
            if (g.es_multiple && g.max && marcadas.length > g.max) {
                mensaje = 'M\u00e1ximo ' + g.max + ' opciones en "' + g.nombre + '".';
            }
            marcadas.forEach(function (m) { elegidas.push(parseInt(m.value, 10)); });
        });

        if (mensaje) {
            error.hidden = false;
            error.textContent = mensaje;
            return;
        }
        cerrarModal();
        agregar(item.id, elegidas, 1);
    }

    function cerrarModal() {
        if (modal && modal.parentNode) { modal.parentNode.removeChild(modal); }
        modal = null;
    }

    /* ---------- eventos (delegados) ---------- */

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest(
            '[data-carrito-agregar],[data-carrito-ver],[data-carrito-mas],[data-carrito-menos],' +
            '[data-carrito-linea-mas],[data-carrito-linea-menos],[data-carrito-quitar],' +
            '[data-carrito-abrir],[data-carrito-cerrar],[data-carrito-cerrar-modal]'
        );
        if (!boton) { return; }

        if (boton.hasAttribute('data-carrito-agregar')) {
            var id = parseInt(boton.getAttribute('data-carrito-agregar'), 10);
            var item = itemPorId[id];
            if (item && (item.grupos || []).length > 0) { abrirModal(item); }
            else { agregar(id, [], 1); }
        } else if (boton.hasAttribute('data-carrito-ver')) {
            pintarPanel();
        } else if (boton.hasAttribute('data-carrito-mas')) {
            cambiarItemSimple(parseInt(boton.getAttribute('data-carrito-mas'), 10), 1);
        } else if (boton.hasAttribute('data-carrito-menos')) {
            cambiarItemSimple(parseInt(boton.getAttribute('data-carrito-menos'), 10), -1);
        } else if (boton.hasAttribute('data-carrito-linea-mas')) {
            cambiarCantidad(parseInt(boton.getAttribute('data-carrito-linea-mas'), 10), 1);
        } else if (boton.hasAttribute('data-carrito-linea-menos')) {
            cambiarCantidad(parseInt(boton.getAttribute('data-carrito-linea-menos'), 10), -1);
        } else if (boton.hasAttribute('data-carrito-quitar')) {
            quitar(parseInt(boton.getAttribute('data-carrito-quitar'), 10));
        } else if (boton.hasAttribute('data-carrito-abrir')) {
            pintarPanel();
        } else if (boton.hasAttribute('data-carrito-cerrar')) {
            cerrarCapas();
        } else if (boton.hasAttribute('data-carrito-cerrar-modal')) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarModal();
            cerrarCapas();
        }
    });

    /* ---------- arranque ---------- */

    // Refresca nombres y precios estimados con los datos frescos del servidor.
    carrito.forEach(function (l) {
        var item = itemPorId[l.producto_id];
        if (!item) { return; }
        l.nombre = item.nombre;
        l.precio_unitario = CALC.precioUnitario(item, l.opciones);
        l.opciones_texto = nombreOpciones(item, l.opciones);
    });
    persistir();
    pintar();

    // API pública: el detalle del producto y US-14a la usan.
    window.TuCartaCarrito = {
        abrirPanel: pintarPanel,
        agregar: agregar,
        lineas: function () { return carrito.slice(); },
        estructuraPedido: function () { return CALC.estructuraPedido(MENU.negocio_id, carrito); }
    };
})();
