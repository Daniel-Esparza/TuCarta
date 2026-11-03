<?php

namespace Tests\Feature;

use App\Enums\EstadoNegocio;
use App\Models\Negocio;
use App\Rules\DireccionWeb;
use App\Rules\Whatsapp;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BaseEstadoNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function errorDeDireccion(string $direccion, ?Negocio $ignorar = null): ?string
    {
        $validador = Validator::make(['direccion_web' => $direccion], ['direccion_web' => [new DireccionWeb($ignorar)]]);

        return $validador->errors()->first('direccion_web') ?: null;
    }

    public function test_los_estados_del_negocio_son_los_cuatro_del_sprint_6(): void
    {
        $this->assertSame(
            ['activo', 'suspendido', 'pendiente', 'rechazado'],
            array_map(fn (EstadoNegocio $estado) => $estado->value, EstadoNegocio::cases()),
        );
    }

    public function test_el_estado_se_lee_como_enum_y_se_guarda_como_texto(): void
    {
        $negocio = Negocio::factory()->create(['estado' => 'suspendido']);

        $this->assertSame(EstadoNegocio::Suspendido, $negocio->fresh()->estado);
        $this->assertDatabaseHas('negocios', ['id' => $negocio->id, 'estado' => 'suspendido']);
    }

    public function test_un_negocio_rechazado_guarda_su_motivo(): void
    {
        $negocio = Negocio::factory()->rechazado('La dirección del local no existe')->create();

        $this->assertSame(EstadoNegocio::Rechazado, $negocio->fresh()->estado);
        $this->assertSame('La dirección del local no existe', $negocio->fresh()->motivo_rechazo);
    }

    public function test_ubicacion_y_categoria_son_opcionales(): void
    {
        $negocio = Negocio::factory()->pendiente()->create(['direccion' => null, 'categoria' => null]);

        $this->assertNull($negocio->fresh()->direccion);
        $this->assertNull($negocio->fresh()->categoria);
    }

    public function test_una_direccion_web_valida_pasa(): void
    {
        $this->assertNull($this->errorDeDireccion('tacos-el-guero-2'));
        $this->assertNull($this->errorDeDireccion('abc'));
        $this->assertNull($this->errorDeDireccion(str_repeat('a', 30)));
    }

    #[DataProvider('direccionesInvalidas')]
    public function test_cada_direccion_invalida_tiene_su_error(string $direccion, string $error): void
    {
        $this->assertSame($error, $this->errorDeDireccion($direccion));
    }

    public static function direccionesInvalidas(): array
    {
        return [
            'mayusculas' => ['MiNegocio', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'muy corta' => ['ab', 'La dirección web debe tener al menos 3 caracteres.'],
            'muy larga' => [str_repeat('a', 31), 'La dirección web no puede pasar de 30 caracteres.'],
            'espacios y signos' => ['mi negocio!', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'acentos' => ['cafeteria-lucía', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'guion al inicio' => ['-tacos', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'guion al final' => ['tacos-', 'Usa solo minúsculas, números y guion, sin espacios.'],
            'reservada www' => ['www', 'Esa dirección web no está disponible.'],
            'reservada auth' => ['auth', 'Esa dirección web no está disponible.'],
            'reservada del servidor' => ['traefik', 'Esa dirección web no está disponible.'],
        ];
    }

    public function test_una_direccion_repetida_da_error_aunque_el_negocio_este_pendiente(): void
    {
        Negocio::factory()->pendiente()->create(['subdominio' => 'tacos-juan']);

        $this->assertSame('Esa dirección web ya está en uso.', $this->errorDeDireccion('tacos-juan'));
    }

    public function test_al_editar_un_negocio_su_propia_direccion_no_cuenta_como_repetida(): void
    {
        $negocio = Negocio::factory()->create(['subdominio' => 'tacos-juan']);

        $this->assertNull($this->errorDeDireccion('tacos-juan', $negocio));
    }

    public function test_las_palabras_reservadas_salen_de_la_configuracion(): void
    {
        config(['tucarta.reservados' => ['demo']]);

        $this->assertSame('Esa dirección web no está disponible.', $this->errorDeDireccion('demo'));
        $this->assertNull($this->errorDeDireccion('www'));
    }

    public function test_los_negocios_de_prueba_cumplen_la_regla(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Negocio::pluck('subdominio') as $subdominio) {
            // Se ignora el propio negocio para no contarlo como repetido.
            $negocio = Negocio::where('subdominio', $subdominio)->first();
            $this->assertNull($this->errorDeDireccion($subdominio, $negocio), $subdominio);
        }
    }

    public function test_el_whatsapp_se_normaliza_y_exige_lada_y_10_digitos(): void
    {
        $valida = fn (string $numero) => Validator::make(['w' => Whatsapp::normalizar($numero)], ['w' => [new Whatsapp]])->passes();

        $this->assertTrue($valida('+52 444-123-4567'));
        $this->assertTrue($valida('+1 (555) 123 4567'));
        $this->assertFalse($valida('4441234567'));
        $this->assertFalse($valida('+52 444 123'));
    }
}
