<?php

use App\Support\SeguridadPorFilas;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // negocios, usuarios, permisos y usuario_permisos quedan fuera de RLS:
    // se consultan antes de saber de qué negocio se trata.
    private array $tablas = [
        'categorias',
        'horarios',
        'items',
        'grupos_opciones',
        'opciones',
        'pedidos',
        'pedido_items',
        'pedido_item_opciones',
        'pedido_estado_historial',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            SeguridadPorFilas::activar($tabla);
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            SeguridadPorFilas::desactivar($tabla);
        }
    }
};
