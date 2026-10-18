<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'negocio_id' => null,
            'rol' => Rol::Cliente,
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'estado' => 'activo',
            'email_verified_at' => now(),
            'telefono_whatsapp' => '+52'.fake()->numerify('##########'),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => Rol::Administrador]);
    }

    public function deNegocio(?Negocio $negocio = null): static
    {
        return $this->state(fn () => [
            'rol' => Rol::Negocio,
            'negocio_id' => $negocio ?? Negocio::factory(),
            'correo_contacto' => fake()->unique()->safeEmail(),
        ]);
    }

    public function pendienteDeActivacion(): static
    {
        return $this->state(fn () => [
            'password' => null,
            'estado' => 'pendiente_activacion',
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado' => 'inactivo']);
    }
}
