<?php

namespace Tests\Feature;

use App\Enums\ClavePermiso;
use App\Models\Horario;
use App\Models\Negocio;
use App\Models\Usuario;
use Database\Seeders\PermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorariosPantallaTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private Usuario $dueno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);

        $this->negocio = Negocio::factory()->create(['subdominio' => 'tacos-juan']);
        $this->dueno = Usuario::factory()->deNegocio($this->negocio)->create();
        $this->dueno->otorgarPermiso(ClavePermiso::EditarPerfil, $this->negocio);
    }

    private function url(string $ruta = ''): string
    {
        return 'http://tacos-juan.'.config('tucarta.dominio').'/panel/horarios'.$ruta;
    }

    private function guardar(int $dia, string $abre, string $cierra)
    {
        return $this->actingAs($this->dueno)->from($this->url())->post($this->url(), [
            'dia_semana' => $dia,
            'hora_apertura' => $abre,
            'hora_cierre' => $cierra,
        ]);
    }

    public function test_un_horario_normal_se_guarda(): void
    {
        $this->guardar(1, '09:00', '18:00')->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('horarios', [
            'negocio_id' => $this->negocio->id,
            'dia_semana' => 1,
            'hora_apertura' => '09:00:00',
            'hora_cierre' => '18:00:00',
        ]);
    }

    public function test_un_horario_que_cruza_la_medianoche_se_guarda_en_dos_dias(): void
    {
        $this->guardar(7, '17:00', '01:00')->assertSessionHasNoErrors();

        // Del domingo pasa al lunes.
        $this->assertDatabaseHas('horarios', ['dia_semana' => 7, 'hora_apertura' => '17:00:00', 'hora_cierre' => Horario::FIN_DE_DIA]);
        $this->assertDatabaseHas('horarios', ['dia_semana' => 1, 'hora_apertura' => '00:00:00', 'hora_cierre' => '01:00:00']);
    }

    public function test_un_horario_que_se_traslapa_da_error(): void
    {
        $this->guardar(2, '09:00', '18:00');

        $this->guardar(2, '17:00', '20:00')->assertSessionHasErrors('hora_apertura');

        $this->assertDatabaseCount('horarios', 1);
    }

    public function test_al_eliminar_un_horario_que_cruza_la_medianoche_se_borran_sus_dos_partes(): void
    {
        $this->guardar(3, '17:00', '01:00');
        $primera = Horario::where('dia_semana', 3)->firstOrFail();

        $this->actingAs($this->dueno)->delete($this->url('/'.$primera->id))->assertRedirect();

        $this->assertDatabaseCount('horarios', 0);
    }

    public function test_un_dueno_no_ve_ni_borra_horarios_de_otro_negocio(): void
    {
        $otro = Negocio::factory()->create();
        $ajeno = Horario::create(['negocio_id' => $otro->id, 'dia_semana' => 1, 'hora_apertura' => '09:00:00', 'hora_cierre' => '18:00:00']);

        $this->actingAs($this->dueno)->delete($this->url('/'.$ajeno->id))->assertNotFound();

        $this->assertDatabaseHas('horarios', ['id' => $ajeno->id]);
    }

    public function test_un_empleado_sin_permiso_recibe_403_y_un_invitado_va_al_login(): void
    {
        $empleado = Usuario::factory()->deNegocio($this->negocio)->create();

        $this->actingAs($empleado)->get($this->url())->assertForbidden();

        auth()->logout();
        $this->get($this->url())->assertRedirect('http://auth.'.config('tucarta.dominio'));
    }

    public function test_la_ruta_de_entrada_sin_contrasena_no_existe(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->get('http://'.config('tucarta.dominio').'/entrar-prueba/'.$admin->email)->assertNotFound();
        $this->get('http://'.config('tucarta.dominio').'/login-local')->assertNotFound();
        $this->assertGuest();
    }
}
