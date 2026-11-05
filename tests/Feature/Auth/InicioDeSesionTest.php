<?php

namespace Tests\Feature\Auth;

use App\Models\Negocio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InicioDeSesionTest extends TestCase
{
    use RefreshDatabase;

    private function login(): string
    {
        return 'http://auth.'.config('tucarta.dominio');
    }

    private function entrar(string $email, string $password = 'password')
    {
        return $this->from($this->login())->post($this->login(), ['email' => $email, 'password' => $password]);
    }

    public function test_se_muestra_el_formulario_en_auth(): void
    {
        $this->get($this->login())
            ->assertOk()
            ->assertSee('Iniciar Sesión')
            ->assertSee('name="_token"', false);
    }

    public function test_el_administrador_entra_y_llega_al_panel_central(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->entrar($admin->email)->assertRedirect($this->login().'/panel');

        $this->assertAuthenticatedAs($admin);
        $this->get($this->login().'/panel')->assertOk()->assertSee('Hola Administrador');
    }

    public function test_el_dueno_entra_y_llega_al_panel_de_su_negocio(): void
    {
        $negocio = Negocio::factory()->create(['subdominio' => 'tacos-juan']);
        $dueno = Usuario::factory()->deNegocio($negocio)->create();

        $this->entrar($dueno->email)
            ->assertRedirect('http://tacos-juan.'.config('tucarta.dominio').'/panel');

        $this->get('http://tacos-juan.'.config('tucarta.dominio').'/panel')->assertOk()->assertSee('Hola Negocio');
    }

    public function test_el_cliente_entra_y_vuelve_al_dominio_principal(): void
    {
        $cliente = Usuario::factory()->create();

        $this->entrar($cliente->email)->assertRedirect('http://'.config('tucarta.dominio').'/');
    }

    public function test_el_correo_no_distingue_mayusculas(): void
    {
        $admin = Usuario::factory()->administrador()->create(['email' => 'admin@tucarta.local']);

        $this->entrar('ADMIN@TuCarta.local');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_contrasena_incorrecta_y_correo_inexistente_dan_el_mismo_error(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->entrar($admin->email, 'otra-cosa')
            ->assertRedirect($this->login())
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña son incorrectos.']);

        $this->entrar('nadie@tucarta.local')
            ->assertRedirect($this->login())
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña son incorrectos.']);

        $this->assertGuest();
    }

    public function test_los_campos_son_obligatorios(): void
    {
        $this->post($this->login(), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'email' => 'Escribe tu correo.',
                'password' => 'Escribe tu contraseña.',
            ]);

        $this->assertGuest();
    }

    public function test_una_cuenta_inactiva_no_puede_entrar(): void
    {
        $empleado = Usuario::factory()->deNegocio()->inactivo()->create();

        $this->entrar($empleado->email)
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña son incorrectos.']);

        $this->assertGuest();
    }

    public function test_despues_de_cinco_intentos_fallidos_se_bloquea_cinco_minutos(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->entrar($admin->email, 'mala');
        }

        // Ni con la contraseña correcta entra mientras dure el bloqueo.
        $this->entrar($admin->email)
            ->assertSessionHasErrors(['email' => 'Demasiados intentos fallidos. Intenta de nuevo en 5 minutos.']);
        $this->assertGuest();

        $this->travel(5)->minutes();
        $this->travel(1)->seconds();

        $this->entrar($admin->email)->assertRedirect($this->login().'/panel');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_la_contrasena_se_guarda_cifrada(): void
    {
        $usuario = Usuario::factory()->create(['password' => 'secreta123']);

        $guardada = $usuario->fresh()->getAuthPassword();

        $this->assertNotSame('secreta123', $guardada);
        $this->assertTrue(Hash::check('secreta123', $guardada));
    }

    public function test_se_registra_la_fecha_del_ultimo_inicio_de_sesion(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->assertNull($admin->last_login);

        $this->entrar($admin->email);

        $this->assertNotNull($admin->fresh()->last_login);
    }

    public function test_el_usuario_puede_cerrar_sesion(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->post($this->login().'/salir')->assertRedirect($this->login());

        $this->assertGuest();
        $this->get($this->login().'/panel')->assertRedirect($this->login());
    }

    public function test_con_sesion_iniciada_el_login_manda_a_su_panel(): void
    {
        $negocio = Negocio::factory()->create(['subdominio' => 'tacos-juan']);
        $dueno = Usuario::factory()->deNegocio($negocio)->create();

        $this->actingAs($dueno)->get($this->login())
            ->assertRedirect('http://tacos-juan.'.config('tucarta.dominio').'/panel');
    }

    public function test_la_sesion_vale_en_todos_los_subdominios(): void
    {
        $this->get($this->login());

        $this->assertSame('.'.config('tucarta.dominio'), config('session.domain'));
    }

    public function test_por_localhost_la_sesion_queda_solo_en_esa_direccion(): void
    {
        $this->get('http://localhost/');

        $this->assertNull(config('session.domain'));
    }
}
