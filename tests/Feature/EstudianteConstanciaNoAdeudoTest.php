<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstudianteConstanciaNoAdeudoTest extends TestCase
{
    private const SIGNED = 'https://sistemas.test/api/v1/constancia-no-adeudo/71?expires=1&signature=abc';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'constancia_test', 'database.connections.constancia_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'services.constancia_no_adeudo.api_url' => 'https://sistemas.test',
            'services.constancia_no_adeudo.token' => 'token-prueba']);
        DB::purge('constancia_test');
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id(); $table->string('nro_documento');
        });
        DB::table('estudiantes')->insert([['id' => 1, 'nro_documento' => '61536694'], ['id' => 2, 'nro_documento' => '11111111']]);
        $this->withoutMiddleware();
        $estudiante = new Estudiante();
        $estudiante->id = 1;
        $this->actingAs($estudiante, 'estudiante');
    }

    public function test_route_requires_the_student_guard_and_permission()
    {
        $route = app('router')->getRoutes()->getByName('estudiantes.constancia-no-adeudo');
        $this->assertContains('auth:estudiante', $route->gatherMiddleware());
        $this->assertContains('permission:panel estudiante', $route->gatherMiddleware());
    }

    public function test_redirects_the_authenticated_student_to_their_own_signed_constancia()
    {
        Http::fake(['sistemas.test/api/v1/*' => Http::response([['nro_documento' => '61536694', 'constancia_no_adeudo' => self::SIGNED]])]);

        $this->get('/estudiantes/constancia-no-adeudo?dni=11111111')->assertRedirect(self::SIGNED);

        Http::assertSent(fn ($request) => $request->url() === 'https://sistemas.test/api/v1/61536694'
            && $request->header('Authorization') === ['token-prueba']);
    }

    public function test_a_student_without_a_synchronized_enrollment_gets_a_clear_404()
    {
        Http::fake(['sistemas.test/api/v1/*' => Http::response([])]);
        $this->get('/estudiantes/constancia-no-adeudo')->assertNotFound();
    }

    public function test_backend_errors_are_reported_as_502()
    {
        Http::fake(['sistemas.test/api/v1/*' => Http::response(['message' => 'error'], 500)]);
        $this->get('/estudiantes/constancia-no-adeudo')->assertStatus(502);
    }

    public function test_links_outside_the_central_system_are_not_followed()
    {
        Http::fake(['sistemas.test/api/v1/*' => Http::response([['constancia_no_adeudo' => 'https://evil.test/x']])]);
        $this->get('/estudiantes/constancia-no-adeudo')->assertStatus(502);
    }
}
