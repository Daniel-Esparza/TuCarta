<?php

namespace Tests\Feature;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutoRegistroNegocioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El layout carga CSS y JS con @vite; en pruebas no hace falta compilarlos.
        $this->withoutVite();

        // Por si el cache persiste entre pruebas.
        RateLimiter::clear('auto-registro|127.0.0.1');
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Tacos El Guero',
            'direccion_web' => 'tacos-guero',
            'whatsapp' => '+524441234567',
            'correo_contacto' => 'contacto@tacosguero.mx',
        ], $cambios);
    }

    public function test_el_formulario_abre_sin_sesion(): void
    {
        $this->get('/registrar-negocio')
            ->assertOk()
            ->assertSee('Registra tu negocio')
            ->assertSee('Enviar solicitud');
    }

    public function test_si_falta_cualquiera_de_los_cuatro_datos_hay_error_por_campo(): void
    {
        $this->from('/registrar-negocio')->post('/registrar-negocio', [])
            ->assertRedirect('/registrar-negocio')
            ->assertSessionHasErrors(['nombre', 'direccion_web', 'whatsapp', 'correo_contacto']);

        $this->assertDatabaseCount('negocios', 0);
    }

    #[DataProvider('direccionesInvalidas')]
    public function test_direccion_web_con_formato_malo_o_reservada_da_error(string $direccion): void
    {
        $this->from('/registrar-negocio')
            ->post('/registrar-negocio', $this->datosValidos(['direccion_web' => $direccion]))
            ->assertSessionHasErrors('direccion_web');

        $this->assertDatabaseCount('negocios', 0);
    }

    public static function direccionesInvalidas(): array
    {
        return [
            'con mayusculas' => ['MiNegocio'],
            'muy corta' => ['ab'],
            'muy larga' => [str_repeat('a', 31)],
            'con caracteres raros' => ['mi negocio!'],
            'reservada admin' => ['admin'],
            'reservada auth' => ['auth'],
        ];
    }

    public function test_direccion_web_repetida_da_error(): void
    {
        Negocio::factory()->create(['subdominio' => 'repetido']);

        $this->from('/registrar-negocio')
            ->post('/registrar-negocio', $this->datosValidos(['direccion_web' => 'repetido']))
            ->assertSessionHasErrors(['direccion_web' => 'Esa dirección web ya está en uso.']);

        $this->assertDatabaseCount('negocios', 1);
    }

    public function test_whatsapp_invalido_da_error(): void
    {
        $this->from('/registrar-negocio')
            ->post('/registrar-negocio', $this->datosValidos(['whatsapp' => '12345']))
            ->assertSessionHasErrors('whatsapp');

        $this->assertDatabaseCount('negocios', 0);
    }

    public function test_un_envio_valido_deja_el_negocio_pendiente_sin_crear_cuenta(): void
    {
        $this->post('/registrar-negocio', $this->datosValidos())
            ->assertRedirect('/registrar-negocio')
            ->assertSessionHas('enviado');

        $this->assertDatabaseHas('negocios', [
            'nombre' => 'Tacos El Guero',
            'subdominio' => 'tacos-guero',
            'telefono_whatsapp' => '+524441234567',
            'correo_contacto' => 'contacto@tacosguero.mx',
            'estado' => 'pendiente',
        ]);

        // Decision D1: todavia no se crea cuenta de dueno.
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_el_whatsapp_con_espacios_y_guiones_se_acepta_y_se_normaliza(): void
    {
        $this->post('/registrar-negocio', $this->datosValidos(['whatsapp' => '+52 444-123-4567']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('negocios', ['telefono_whatsapp' => '+524441234567']);
    }

    public function test_la_direccion_de_un_negocio_pendiente_no_abre_su_menu(): void
    {
        $this->post('/registrar-negocio', $this->datosValidos());

        $this->get('http://tacos-guero.'.config('tucarta.dominio'))->assertNotFound();
    }

    public function test_despues_de_varios_envios_seguidos_aparece_el_aviso_del_limite(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/registrar-negocio', []);
        }

        $this->post('/registrar-negocio', $this->datosValidos())
            ->assertSessionHas('limite');

        $this->assertDatabaseCount('negocios', 0);
    }
}
