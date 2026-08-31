<?php

namespace Tests\Feature;

use App\Http\Controllers\Docente\HorarioController;
use App\Http\Kernel;
use App\Http\Middleware\EnsureCurrentTeacherPeriod;
use App\Models\CargaAcademica;
use App\Models\DocenteApto;
use App\Models\Sesiones;
use App\Services\GrupoAulaContactService;
use App\Support\DocentePreguntasDemoAccess;
use Illuminate\Support\Facades\DB;
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

    public function test_la_cuenta_docente_exige_inscripcion_apta_sin_exigir_carga_del_periodo_activo()
    {
        $query = DocenteApto::query()->habilitadoEnPeriodo(10);

        $sql = $query->toSql();

        $this->assertStringContainsString('`docente_aptos`.`estado` = ?', $sql);
        $this->assertStringContainsString('`inscripcion_docentes` as `inscripcion_actual`', $sql);
        $this->assertStringContainsString('`inscripcion_actual`.`periodos_id` = ?', $sql);
        $this->assertStringContainsString('`inscripcion_actual`.`apto` = ?', $sql);
        $this->assertStringNotContainsString('`carga_academicas` as `carga_actual`', $sql);
        $this->assertSame(['1', '1', '1', 10, '1', '1'], $query->getBindings());
    }

    public function test_solo_la_cuenta_mas_reciente_del_docente_puede_autenticarse()
    {
        $query = DocenteApto::query()
            ->habilitadoEnPeriodo(10)
            ->conCredenciales('docente@cepreuna.edu.pe', 'clave');

        $sql = $query->toSql();

        $this->assertStringContainsString('MAX(cuenta_periodo.periodos_id)', $sql);
        $this->assertStringContainsString('MAX(cuenta_vigente.id)', $sql);
        $this->assertStringContainsString('`docentes`.`usuario` = ?', $sql);
        $this->assertSame('docente@cepreuna.edu.pe', $query->getBindings()[6]);
        $this->assertSame('clave', $query->getBindings()[7]);
    }

    public function test_el_control_de_periodo_docente_se_aplica_a_todas_las_rutas_web()
    {
        $this->assertContains(
            EnsureCurrentTeacherPeriod::class,
            app(Kernel::class)->getMiddlewareGroups()['web']
        );
    }

    public function test_los_contactos_de_grupo_se_resuelven_en_una_sola_consulta_del_periodo()
    {
        $consultas = DB::connection()->pretend(function () {
            app(GrupoAulaContactService::class)->obtener([501, 502], 10);
        });

        $this->assertCount(1, $consultas);
        $this->assertStringContainsString('`auxiliar_grupos`', $consultas[0]['query']);
        $this->assertStringContainsString('`auxiliar_coordinadores`', $consultas[0]['query']);
        $this->assertStringContainsString('group by `grupo_aulas_id`', $consultas[0]['query']);
        $this->assertStringContainsString('group by `auxiliares_id`', $consultas[0]['query']);
        $this->assertStringContainsString('`telefono` is not null', $consultas[0]['query']);
        $this->assertSame([10, 501, 502, 10], $consultas[0]['bindings']);
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

    public function test_el_demo_de_preguntas_esta_apagado_por_defecto()
    {
        config()->set('features.docente_preguntas_demo.enabled', false);
        config()->set('features.docente_preguntas_demo.docentes_ids', ['52']);

        $cuenta = new DocenteApto();
        $cuenta->docentes_id = 52;

        $this->assertFalse(app(DocentePreguntasDemoAccess::class)->permite($cuenta));
    }

    public function test_el_demo_de_preguntas_solo_admite_docentes_seleccionados()
    {
        config()->set('features.docente_preguntas_demo.enabled', true);
        config()->set('features.docente_preguntas_demo.docentes_ids', ['52', '64']);

        $seleccionado = new DocenteApto();
        $seleccionado->docentes_id = 52;

        $noSeleccionado = new DocenteApto();
        $noSeleccionado->docentes_id = 80;

        $acceso = app(DocentePreguntasDemoAccess::class);

        $this->assertTrue($acceso->permite($seleccionado));
        $this->assertFalse($acceso->permite($noSeleccionado));
    }

    public function test_la_ruta_del_demo_es_solo_lectura_y_exige_la_lista_blanca()
    {
        $route = app('router')->getRoutes()->getByName('docentes.recursos.preguntas-demo');

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth:docente', $route->gatherMiddleware());
        $this->assertContains('permission:panel docente', $route->gatherMiddleware());
        $this->assertContains('docente.preguntas.demo', $route->gatherMiddleware());
    }
}
