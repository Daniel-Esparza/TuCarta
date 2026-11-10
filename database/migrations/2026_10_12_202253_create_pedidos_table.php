<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS estado_pedido');
            DB::statement('DROP TYPE IF EXISTS tipo_entrega');
            DB::statement("CREATE TYPE tipo_entrega AS ENUM ('en_sitio', 'para_llevar', 'domicilio')");
            DB::statement("CREATE TYPE estado_pedido AS ENUM ('recibido', 'confirmado', 'en_preparacion', 'listo', 'en_camino', 'entregado', 'cancelado')");
        }

        Schema::create('pedidos', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios');
            $table->foreignId('cliente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('folio', 20);
            $table->string('cliente_nombre', 120);
            $table->string('notas', 500)->nullable();
            $table->string('direccion_entrega', 255)->nullable();
            $table->string('referencias_entrega', 255)->nullable();
            $table->decimal('total', 10, 2);
            $table->ipAddress('ip_origen')->nullable();

            if ($driver !== 'pgsql') {
                $table->enum('tipo_entrega', ['en_sitio', 'para_llevar', 'domicilio']);
                $table->enum('estado', ['recibido', 'confirmado', 'en_preparacion', 'listo', 'en_camino', 'entregado', 'cancelado'])->default('recibido');
            }

            $table->timestamps();

            $table->unique(['negocio_id', 'folio'], 'uq_pedidos_negocio_folio');
            $table->index('cliente_id', 'idx_pedidos_cliente');
        });

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pedidos ADD COLUMN tipo_entrega tipo_entrega NOT NULL');
            DB::statement("ALTER TABLE pedidos ADD COLUMN estado estado_pedido NOT NULL DEFAULT 'recibido'");
        }

        Schema::table('pedidos', function (Blueprint $table) {
            $table->index(['negocio_id', 'estado', 'created_at'], 'idx_pedidos_bandeja');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TYPE IF EXISTS estado_pedido');
            DB::statement('DROP TYPE IF EXISTS tipo_entrega');
        }
    }
};