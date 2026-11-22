<?php

namespace App\Services;

use App\Enums\TipoEntrega;
use App\Models\Negocio;
use App\Models\Pedido;

class WhatsappPedidoService
{
    /**
     * Construye el texto del pedido listo para enviar por WhatsApp.
     * Todo proviene de la base de datos, nunca del navegador.
     */
    public function construirMensaje(Pedido $pedido, Negocio $negocio): string
    {
        $pedido->loadMissing(['items.opciones']);

        $lineas = [
            "Pedido {$pedido->folio} - {$negocio->nombre}",
            "Cliente: {$pedido->cliente_nombre}",
            '',
        ];

        foreach ($pedido->items as $item) {
            $subtotalFormateado = $this->formatearPrecio((float) $item->subtotal);
            $lineas[] = "{$item->cantidad} x {$item->nombre_item} ... {$subtotalFormateado}";

            if ($item->opciones->isNotEmpty()) {
                $nombresOpciones = $item->opciones
                    ->pluck('nombre_opcion')
                    ->implode(', ');
                $lineas[] = "   Opciones: {$nombresOpciones}";
            }
        }

        // Hueco preparado para el bloque de tipo de pedido (US-20 - Sebastián)
        $bloqueEntrega = $this->formatearBloqueEntrega($pedido);
        if ($bloqueEntrega !== '') {
            $lineas[] = '';
            $lineas[] = $bloqueEntrega;
        }

        $totalFormateado = $this->formatearPrecio((float) $pedido->total);
        $lineas[] = '';
        $lineas[] = "Total estimado: {$totalFormateado} (sin envío)";

        if (filled($pedido->notas)) {
            $lineas[] = "Nota: {$pedido->notas}";
        }

        return implode("\n", $lineas);
    }

    /**
     * Normaliza el número del negocio y arma la URL https://wa.me/NUMERO?text=...
     * Devuelve null si el negocio no tiene un número válido configurado.
     */
    public function generarUrl(Pedido $pedido, Negocio $negocio): ?string
    {
        $numero = $this->normalizarTelefono($negocio->telefono_whatsapp);

        if ($numero === null) {
            return null;
        }

        $mensaje = $this->construirMensaje($pedido, $negocio);

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($mensaje);
    }

    /**
     * Deja solo dígitos. Si son 10 dígitos (número nacional MX), antepone 52.
     */
    public function normalizarTelefono(?string $telefono): ?string
    {
        if (blank($telefono)) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $telefono);

        if (strlen($digitos) < 10) {
            return null;
        }

        if (strlen($digitos) === 10) {
            return '52'.$digitos;
        }

        return $digitos;
    }

    /**
     * Hueco preparado para US-20 (Sebastián).
     */
    protected function formatearBloqueEntrega(Pedido $pedido): string
    {
        if (! $pedido->tipo_entrega instanceof TipoEntrega) {
            return '';
        }

        return match ($pedido->tipo_entrega) {
            TipoEntrega::EnSitio => 'Tipo de entrega: En sitio',
            TipoEntrega::ParaLlevar => 'Tipo de entrega: Para llevar',
            TipoEntrega::Domicilio => $this->formatearDomicilio($pedido),
        };
    }

    protected function formatearDomicilio(Pedido $pedido): string
    {
        $partes = ['Tipo de entrega: A domicilio'];

        if (filled($pedido->direccion_entrega)) {
            $partes[] = "Dirección: {$pedido->direccion_entrega}";
        }

        if (filled($pedido->referencias_entrega)) {
            $partes[] = "Referencias: {$pedido->referencias_entrega}";
        }

        return implode("\n", $partes);
    }

    protected function formatearPrecio(float $monto): string
    {
        return fmod($monto, 1.0) === 0.0
            ? '$'.number_format($monto, 0, '.', '')
            : '$'.number_format($monto, 2, '.', '');
    }
}
