<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class PedidoItemOpcion extends Model
{
    use PerteneceANegocio;

    protected $table = 'pedido_item_opciones';

    public $timestamps = false;

    protected $fillable = [
        'pedido_item_id', 'negocio_id', 'opcion_id',
        'nombre_grupo', 'nombre_opcion', 'incremento_precio',
    ];

    protected $casts = [
        'incremento_precio' => 'decimal:2',
    ];
}
