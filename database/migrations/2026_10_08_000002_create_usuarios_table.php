<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->nullable()->constrained('negocios');
            $table->string('rol');
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('email')->unique();
            $table->string('correo_contacto')->nullable();
            $table->string('password')->nullable();
            $table->string('estado')->default('pendiente_activacion');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('telefono_whatsapp');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
