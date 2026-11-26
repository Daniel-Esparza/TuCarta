<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use App\Enums\TipoEntrega;
use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use PerteneceANegocio;

    protected $table = 'pedidos';

    protected $fillable = [
        'negocio_id', 'cliente_id', 'folio', 'cliente_nombre', 'notas',
        'tipo_entrega', 'direccion_entrega', 'referencias_entrega',
        'total', 'estado', 'ip_origen',
    ];

    protected $casts = [
        'tipo_entrega' => TipoEntrega::class,
        'estado' => EstadoPedido::class,
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(PedidoEstadoHistorial::class, 'pedido_id');
    }
}
