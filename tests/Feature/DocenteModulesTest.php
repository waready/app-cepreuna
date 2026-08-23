<?php

namespace Tests\Feature;

use App\Http\Controllers\Docente\HorarioController;
use App\Models\CargaAcademica;
use App\Models\DocenteApto;
use App\Models\Sesiones;
use Tests\TestCase;

class DocenteModulesTest extends TestCase
{
    public function test_todas_las_rutas_del_panel_docente_exigen_su_guard_y_permiso()
    {
        $routeNames = [
            'docentes.horarios',
            'docentes.get-horario',
            'docentes.recursos.cursos',
            'docentes.recursos.get-carga',
            'docentes.recursos.get-estudiantes',
            'docentes.recursos.carga-update',
            'docentes.recursos.cuadernillos',
            'docentes.recursos.get-cursos-docente',
            'docentes.recursos.temarios',
            'docentes.recursos.get-cursos-docente-temario',
            'docentes.recursos.sesiones',
            'docentes.recursos.lista-sesion',
            'docentes.recursos.get-cursos-carga',
            'docentes.recursos.store-sesion',
            'docentes.recursos.update-sesion',
            'docentes.asistencias',
            'docentes.get-asistencias',
        ];

        foreach ($routeNames as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "No existe la ruta {$routeName}");
            $this->assertContains('auth:docente', $route->gatherMiddleware(), $routeName);
            $this->assertContains('permission:panel docente', $route->gatherMiddleware(), $routeName);
        }
    }

    public function test_sanctum_reconoce_la_sesion_del_docente()
    {
        $this->assertContains('docente', config('sanctum.guard'));
    }

    public function test_el_scope_de_carga_filtra_docente_y_periodo()
    {
        $query = CargaAcademica::query()->delDocenteEnPeriodo(52, 9);

        $this->assertStringContainsString('`carga_academicas`.`docentes_id` = ?', $query->toSql());
        $this->assertStringContainsString('`carga_academicas`.`periodos_id` = ?', $query->toSql());
        $this->assertSame([52, 9], $query->getBindings());
    }

    public function test_la_cuenta_docente_historica_exige_carga_en_el_periodo_actual()
    {
        $query = DocenteApto::query()
            ->conCredenciales('docente@cepreuna.edu.pe', 'clave')
            ->conCargaEnPeriodo(10)
            ->masReciente();

        $sql = $query->toSql();

        $this->assertStringContainsString('exists', $sql);
        $this->assertStringContainsString('`carga_academicas` as `ca`', $sql);
        $this->assertStringContainsString('`ca`.`docentes_id` = `docente_aptos`.`docentes_id`', $sql);
        $this->assertStringContainsString('`ca`.`periodos_id` = ?', $sql);
        $this->assertStringContainsString('`docentes`.`usuario` = ?', $sql);
        $this->assertStringContainsString('order by `docente_aptos`.`periodos_id` desc', $sql);
        $this->assertSame([
            'docente@cepreuna.edu.pe',
            'clave',
            'docente@cepreuna.edu.pe',
            'clave',
            10,
            '1',
        ], $query->getBindings());
    }

    public function test_el_login_google_acepta_la_identidad_del_registro_maestro_docente()
    {
        $query = DocenteApto::query()->conIdentidadGoogle('google-id');

        $this->assertStringContainsString('`docente_aptos`.`idgsuite` = ?', $query->toSql());
        $this->assertStringContainsString('`docentes`.`idgsuite` = ?', $query->toSql());
        $this->assertSame(['google-id', 'google-id'], $query->getBindings());
    }

    public function test_credenciales_vacias_nunca_se_convierten_en_una_busqueda_por_nulos()
    {
        $query = DocenteApto::query()->conCredenciales('', '');

        $this->assertStringContainsString('1 = 0', $query->toSql());
        $this->assertSame([], $query->getBindings());
    }

    public function test_el_login_rechaza_una_solicitud_sin_credenciales_antes_de_consultar_la_db()
    {
        $response = $this->from('/')->post(route('login-singsuit.login'), []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_el_scope_de_sesion_hereda_el_docente_y_periodo_de_su_carga()
    {
        $query = Sesiones::query()->delDocenteEnPeriodo(52, 9);

        $this->assertStringContainsString('exists', $query->toSql());
        $this->assertStringContainsString('`sesiones`.`periodos_id` = ?', $query->toSql());
        $this->assertStringContainsString('`carga_academicas`.`docentes_id` = ?', $query->toSql());
        $this->assertStringContainsString('`carga_academicas`.`periodos_id` = ?', $query->toSql());
        $this->assertSame([9, 52, 9], $query->getBindings());
    }

    public function test_el_horario_conserva_todos_los_bloques_y_solo_asigna_el_que_corresponde()
    {
        $controller = new class extends HorarioController {
            public function construir($turno, array $dias, array $plantillas, array $horarios)
            {
                return $this->construirTurnoHorario($turno, $dias, $plantillas, $horarios);
            }
        };

        $turno = (object) ['id' => 1, 'denominacion' => 'Mañana'];
        $plantilla = (object) [
            'id' => 10,
            'horaInicio' => '08:00',
            'horaFin' => '08:45',
            'tipo' => '1',
        ];
        $horario = (object) ['id' => 99, 'grupo' => 'A'];

        $resultado = $controller->construir(
            $turno,
            [['id' => '1', 'nombre' => 'Lu'], ['id' => '2', 'nombre' => 'Ma']],
            [1 => [$plantilla]],
            ['1-10' => $horario]
        );

        $this->assertCount(2, $resultado->dias);
        $this->assertSame($horario, $resultado->dias[0]->disponibilidad[0]->horario);
        $this->assertNull($resultado->dias[1]->disponibilidad[0]->horario);
    }
}
