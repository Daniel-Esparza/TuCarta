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
}
