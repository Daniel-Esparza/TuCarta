<?php

namespace Database\Factories;

use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->unique()->company();

        return [
            'nombre' => $nombre,
            'subdominio' => Str::limit(Str::slug($nombre), 25, '').'-'.fake()->unique()->numberBetween(10, 9999),
            'telefono_whatsapp' => '+52'.fake()->numerify('##########'),
            'correo_contacto' => fake()->unique()->safeEmail(),
            'descripcion' => fake()->sentence(),
            'direccion' => fake()->address(),
            'categoria' => fake()->randomElement(['Antojitos', 'Mariscos', 'Cafetería', 'Tacos']),
            'estado' => 'activo',
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn () => ['estado' => 'pendiente']);
    }
}
