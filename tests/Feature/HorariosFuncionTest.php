<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Negocio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorariosFuncionTest extends TestCase
{
    use RefreshDatabase;

    private const ZONA = 'America/Mexico_City';

    private function negocio(array $filas, array $atributos = []): Negocio
    {
        $negocio = Negocio::factory()->create($atributos + ['zona_horaria' => self::ZONA]);
        foreach ($filas as [$dia, $apertura, $cierre]) {
            $negocio->horarios()->create([
                'dia_semana' => $dia, 'hora_apertura' => $apertura, 'hora_cierre' => $cierre,
            ]);
        }

        return $negocio;
    }

    // 2026-11-02 es lunes.
    private function lunes(string $hora): Carbon
    {
        return Carbon::parse("2026-11-02 $hora", self::ZONA);
    }

    public function test_un_horario_normal_abre_dentro_y_cierra_fuera(): void
    {
        $negocio = $this->negocio([[1, '09:00:00', '18:00:00']]);

        $this->assertTrue($negocio->estaAbierto($this->lunes('10:00')));
        $this->assertFalse($negocio->estaAbierto($this->lunes('19:00')));
    }

    public function test_un_horario_que_cruza_la_medianoche_esta_abierto_a_las_0030(): void
    {
        // 17:00 a 01:00 del lunes, guardado en dos filas.
        $negocio = $this->negocio([
            [1, '17:00:00', Horario::FIN_DE_DIA],
            [2, '00:00:00', '01:00:00'],
        ]);

        $this->assertTrue($negocio->estaAbierto($this->lunes('23:30')));
        $this->assertTrue($negocio->estaAbierto($this->lunes('00:30')->addDay())); // martes 00:30
        $this->assertFalse($negocio->estaAbierto($this->lunes('01:30')->addDay()));
    }

    public function test_fuera_de_las_horas_configuradas_responde_cerrado(): void
    {
        $negocio = $this->negocio([[1, '09:00:00', '18:00:00']]);

        $this->assertFalse($negocio->estaAbierto($this->lunes('08:59')));
        $this->assertFalse($negocio->estaAbierto($this->lunes('12:00')->addDay())); // martes sin horario
    }

    public function test_el_cierre_temporal_manda_sobre_el_horario(): void
    {
        $negocio = $this->negocio([[1, '09:00:00', '18:00:00']], ['cerrado_temporal' => true]);

        $this->assertTrue($negocio->dentroDeHorario($this->lunes('10:00')));
        $this->assertFalse($negocio->estaAbierto($this->lunes('10:00')));
    }

    public function test_los_horarios_que_se_traslapan_se_detectan(): void
    {
        $negocio = $this->negocio([[1, '09:00:00', '14:00:00']]);

        $this->assertTrue(Horario::seTraslapa($negocio->id, 1, '13:00:00', '16:00:00'));
        $this->assertFalse(Horario::seTraslapa($negocio->id, 1, '14:00:00', '18:00:00')); // solo se tocan
        $this->assertFalse(Horario::seTraslapa($negocio->id, 2, '09:00:00', '14:00:00')); // otro día
    }
}