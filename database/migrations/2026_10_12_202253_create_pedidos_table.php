<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE tipo_entrega AS ENUM ('en_sitio', 'para_llevar', 'domicilio')");
        DB::statement("CREATE TYPE estado_pedido AS ENUM ('recibido', 'confirmado', 'en_preparacion', 'listo', 'en_camino', 'entregado', 'cancelado')");

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios');
            $table->foreignId('cliente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('folio', 20);
            $table->string('cliente_nombre', 120);
            $table->string('notas', 500)->nullable();
            $table->string('direccion_entrega', 255)->nullable();
            $table->string('referencias_entrega', 255)->nullable();
            $table->decimal('total', 10, 2);
            $table->ipAddress('ip_origen')->nullable(); // en PostgreSQL es inet
            $table->timestamps();

            $table->unique(['negocio_id', 'folio'], 'uq_pedidos_negocio_folio');
            $table->index('cliente_id', 'idx_pedidos_cliente');
        });

        DB::statement('ALTER TABLE pedidos ADD COLUMN tipo_entrega tipo_entrega NOT NULL');
        DB::statement("ALTER TABLE pedidos ADD COLUMN estado estado_pedido NOT NULL DEFAULT 'recibido'");
        DB::statement('CREATE INDEX idx_pedidos_bandeja ON pedidos (negocio_id, estado, created_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
        DB::statement('DROP TYPE IF EXISTS estado_pedido');
        DB::statement('DROP TYPE IF EXISTS tipo_entrega');
    }
};