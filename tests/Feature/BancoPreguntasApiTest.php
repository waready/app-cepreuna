<?php

namespace Tests\Feature;

use App\Exceptions\BancoPreguntasApiException;
use App\Http\Controllers\Docente\PreguntasDemoController;
use App\Services\BancoPreguntasApi;
use App\Support\DocumentoWord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BancoPreguntasApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.banco_preguntas', [
            'url' => 'https://central.test/api',
            'token' => 'token-interno-pruebas',
            'timeout' => 10,
        ]);
    }

    public function test_cepre_app_envia_el_word_al_backend_central()
    {
        Http::fake([
            'https://central.test/api/integraciones/cepre-app/banco-preguntas' => Http::response([
                'status' => true,
                'message' => 'Recibido',
                'data' => ['id' => 50],
            ], 201),
        ]);

        $archivo = new UploadedFile(
            resource_path('templates/modelo-preguntas.docx'),
            'preguntas.docx',
            DocumentoWord::MIME,
            null,
            true
        );

        $response = app(BancoPreguntasApi::class)->crearEntrega([
            'periodos_id' => 10,
            'cursos_id' => 4,
            'docentes_id' => 52,
            'semana' => 3,
            'nivel' => 'intermedio',
        ], $archivo);

        $this->assertSame(50, $response['data']['id']);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://central.test/api/integraciones/cepre-app/banco-preguntas'
                && $request->hasHeader('X-Cepre-App-Token', 'token-interno-pruebas')
                && $request->isMultipart()
                && $request->hasFile('archivo');
        });
    }

    public function test_las_descargas_del_docente_vienen_del_backend_central()
    {
        Http::fake([
            'https://central.test/api/integraciones/cepre-app/banco-preguntas/50/archivo*' => Http::response(
                'contenido-word',
                200,
                ['Content-Type' => DocumentoWord::MIME]
            ),
        ]);

        $contenido = app(BancoPreguntasApi::class)->descargarEntrega(50, 52, 10);

        $this->assertSame('contenido-word', $contenido);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/banco-preguntas/50/archivo')
                && str_contains($request->url(), 'docentes_id=52')
                && str_contains($request->url(), 'periodos_id=10');
        });
    }

    public function test_los_errores_de_validacion_del_backend_se_conservan()
    {
        Http::fake([
            'https://central.test/api/integraciones/cepre-app/banco-preguntas' => Http::response([
                'message' => 'Los datos proporcionados no son validos.',
                'errors' => ['semana' => ['Ya existe una entrega en revision.']],
            ], 422),
        ]);

        $archivo = new UploadedFile(
            resource_path('templates/modelo-preguntas.docx'),
            'preguntas.docx',
            DocumentoWord::MIME,
            null,
            true
        );

        try {
            app(BancoPreguntasApi::class)->crearEntrega([
                'periodos_id' => 10,
                'cursos_id' => 4,
                'docentes_id' => 52,
                'semana' => 3,
                'nivel' => 'intermedio',
            ], $archivo);
            $this->fail('Se esperaba una excepcion del backend central.');
        } catch (BancoPreguntasApiException $exception) {
            $this->assertSame(422, $exception->status());
            $this->assertSame(
                ['semana' => ['Ya existe una entrega en revision.']],
                $exception->errors()
            );
        }
    }

    public function test_cepre_app_no_declara_un_disco_para_banco_de_preguntas()
    {
        $this->assertArrayNotHasKey('banco_preguntas', config('filesystems.disks'));
    }

    public function test_el_controlador_docente_no_guarda_archivos_ni_entregas()
    {
        $source = file_get_contents((new \ReflectionClass(
            PreguntasDemoController::class
        ))->getFileName());

        $this->assertStringContainsString('bancoPreguntasApi->crearEntrega', $source);
        $this->assertStringNotContainsString("Storage::disk('banco_preguntas')", $source);
        $this->assertStringNotContainsString('BancoPreguntaLote::create', $source);
        $this->assertStringNotContainsString('DB::transaction', $source);
    }
}
