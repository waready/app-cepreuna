<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstudianteSimulacroTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'simulacro_test', 'database.connections.simulacro_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'services.simulacro.formulario_url' => 'http://localhost:3002/inscripciones/simulacro',
            'services.simulacro.api_url' => 'http://backend.test/api']);
        DB::purge('simulacro_test');
        Schema::create('periodos', function (Blueprint $table) {
            $table->id(); $table->string('codigo'); $table->string('nombre'); $table->string('estado');
        });
        Schema::create('inscripcion_simulacros', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('periodos_id'); $table->unsignedBigInteger('estudiantes_id');
            $table->string('codigoqr'); $table->decimal('monto', 10, 2); $table->timestamps();
        });
        Schema::create('simulacro_configuraciones', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('periodos_id'); $table->string('titulo');
            $table->dateTime('fecha_examen')->nullable(); $table->time('hora_ingreso_inicio')->nullable();
            $table->time('hora_ingreso_fin')->nullable(); $table->string('lugar')->nullable();
        });
        DB::table('periodos')->insert([
            ['id' => 9, 'codigo' => '2025-II', 'nombre' => 'Anterior', 'estado' => '0'],
            ['id' => 10, 'codigo' => '2026-II', 'nombre' => 'Actual', 'estado' => '1'],
        ]);
        $this->withoutMiddleware();
        $estudiante = new Estudiante();
        $estudiante->id = 1;
        $this->actingAs($estudiante, 'estudiante');
    }

    public function test_sidebar_routes_require_the_student_guard_and_permission()
    {
        foreach (['estudiantes.simulacro', 'estudiantes.simulacro.ficha'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertContains('auth:estudiante', $route->gatherMiddleware());
            $this->assertContains('permission:panel estudiante', $route->gatherMiddleware());
        }
    }

    public function test_an_old_registration_or_another_student_does_not_prevent_redirecting_to_the_form()
    {
        $this->registrar(1, 9, 'ANTERIOR');
        $this->registrar(2, 10, 'OTRO');
        $response = $this->get('/estudiantes/simulacro', ['X-Inertia' => 'true']);
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', 'http://localhost:3002/inscripciones/simulacro?periodo=10');
        Http::fake();
        $this->get('/estudiantes/simulacro/ficha?estudiantes_id=2')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_a_registered_student_sees_the_current_registration_and_own_pdf()
    {
        $this->registrar(1, 9, 'ANTERIOR');
        $this->registrar(1, 10, 'MI-FICHA');
        $this->registrar(2, 10, 'OTRO');
        $this->get('/estudiantes/simulacro', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('component', 'Estudiante/Simulacro')
            ->assertJsonPath('props.data.ciclo', '2026-II')->assertJsonPath('props.data.monto', '16.00');
        Http::fake(['backend.test/*' => Http::response('%PDF-1.4 mi ficha', 200, ['Content-Type' => 'application/pdf'])]);
        $this->get('/estudiantes/simulacro/ficha?estudiantes_id=2&codigoqr=OTRO')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="ficha-simulacro-2026-II.pdf"');
        $this->get('/estudiantes/simulacro/ficha?descargar=1')->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="ficha-simulacro-2026-II.pdf"');
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'http://backend.test/api/web/inscripciones/simulacro/MI-FICHA/pdf');
    }

    public function test_backend_errors_are_not_served_as_a_pdf()
    {
        $this->registrar(1, 10, 'MI-FICHA');
        Http::fake(['backend.test/*' => Http::response('<html>Error</html>', 200)]);
        $this->get('/estudiantes/simulacro/ficha')->assertStatus(502);
    }

    private function registrar(int $estudiante, int $periodo, string $codigo): void
    {
        DB::table('inscripcion_simulacros')->insert([
            'estudiantes_id' => $estudiante, 'periodos_id' => $periodo, 'codigoqr' => $codigo,
            'monto' => 16, 'created_at' => '2026-11-06 12:00:00', 'updated_at' => '2026-11-06 12:00:00',
        ]);
    }
}
