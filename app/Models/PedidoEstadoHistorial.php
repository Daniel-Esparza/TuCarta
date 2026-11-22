<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class PedidoEstadoHistorial extends Model
{
    use PerteneceANegocio;

    protected $table = 'pedido_estado_historial';

    public $timestamps = false;

    protected $fillable = [
        'pedido_id', 'negocio_id', 'usuario_id',
        'estado_anterior', 'estado_nuevo', 'motivo',
    ];

    protected $casts = [
        'estado_anterior' => EstadoPedido::class,
        'estado_nuevo' => EstadoPedido::class,
    ];
}
