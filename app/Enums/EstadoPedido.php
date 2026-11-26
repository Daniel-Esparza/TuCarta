<?php

namespace App\Enums;

// Estados de un pedido. El inicial es "recibido".
enum EstadoPedido: string
{
    case Recibido = 'recibido';
    case Confirmado = 'confirmado';
    case EnPreparacion = 'en_preparacion';
    case Listo = 'listo';
    case EnCamino = 'en_camino';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';

    /**
     * US-10: nombre legible para el panel de métricas y el historial (US-21).
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Recibido => 'Recibido',
            self::Confirmado => 'Confirmado',
            self::EnPreparacion => 'En preparación',
            self::Listo => 'Listo',
            self::EnCamino => 'En camino',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
        };
    }
}
