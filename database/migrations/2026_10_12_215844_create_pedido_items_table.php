<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos');
            $table->foreignId('negocio_id')->constrained('negocios');
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('nombre_item', 120);
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('cantidad');
            $table->decimal('subtotal', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('pedido_id', 'idx_pedido_items_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
