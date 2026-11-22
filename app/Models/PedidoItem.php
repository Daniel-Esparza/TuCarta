<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoItem extends Model
{
    use PerteneceANegocio;

    protected $table = 'pedido_items';

    // Solo tiene created_at, y lo pone la base de datos.
    public $timestamps = false;

    protected $fillable = [
        'pedido_id', 'negocio_id', 'item_id', 'nombre_item',
        'precio_unitario', 'cantidad', 'subtotal',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function opciones(): HasMany
    {
        return $this->hasMany(PedidoItemOpcion::class, 'pedido_item_id');
    }
}
