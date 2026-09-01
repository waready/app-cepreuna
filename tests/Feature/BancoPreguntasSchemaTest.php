<?php

namespace Tests\Feature;

use App\Http\Requests\StoreBancoPreguntaLoteRequest;
use App\Models\BancoPreguntaLote;
use App\Models\BancoPreguntaRevision;
use App\Support\DocumentoWord;
use Tests\TestCase;

class BancoPreguntasSchemaTest extends TestCase
{
    public function test_los_modelos_apuntan_a_las_dos_tablas_del_tramite()
    {
        $this->assertSame('banco_pregunta_lotes', (new BancoPreguntaLote())->getTable());
        $this->assertSame(
            'banco_pregunta_revisiones',
            (new BancoPreguntaRevision())->getTable()
        );
    }

    public function test_las_relaciones_utilizan_las_claves_del_esquema()
    {
        $lote = new BancoPreguntaLote();
        $revision = new BancoPreguntaRevision();

        $this->assertSame('periodos_id', $lote->periodo()->getForeignKeyName());
        $this->assertSame('cursos_id', $lote->curso()->getForeignKeyName());
        $this->assertSame('docentes_id', $lote->docente()->getForeignKeyName());
        $this->assertSame(
            'banco_pregunta_lote_id',
            $lote->revision()->getForeignKeyName()
        );
        $this->assertSame(
            'banco_pregunta_lote_id',
            $revision->lote()->getForeignKeyName()
        );
        $this->assertSame('users_id', $revision->usuario()->getForeignKeyName());
    }

    public function test_el_flujo_declara_estados_niveles_y_decisiones_controlados()
    {
        $this->assertSame('en_revision', BancoPreguntaLote::ESTADO_EN_REVISION);
        $this->assertSame('aprobado', BancoPreguntaLote::ESTADO_APROBADO);
        $this->assertSame('observado', BancoPreguntaLote::ESTADO_OBSERVADO);
        $this->assertSame('rechazado', BancoPreguntaLote::ESTADO_RECHAZADO);
        $this->assertSame('basico', BancoPreguntaLote::NIVEL_BASICO);
        $this->assertSame('intermedio', BancoPreguntaLote::NIVEL_INTERMEDIO);
        $this->assertSame('avanzado', BancoPreguntaLote::NIVEL_AVANZADO);
        $this->assertSame('aprobar', BancoPreguntaRevision::ACCION_APROBAR);
        $this->assertSame('observar', BancoPreguntaRevision::ACCION_OBSERVAR);
        $this->assertSame('rechazar', BancoPreguntaRevision::ACCION_RECHAZAR);
    }

    public function test_la_solicitud_exige_dos_preguntas_y_confirmacion_explicita()
    {
        $reglas = (new StoreBancoPreguntaLoteRequest())->rules();

        $this->assertArrayNotHasKey('cantidad_preguntas', $reglas);
        $this->assertContains('accepted', $reglas['confirmacion_dos_preguntas']);
        $this->assertContains('file', $reglas['archivo']);
    }

    public function test_los_modelos_solo_permiten_los_campos_minimos()
    {
        $this->assertSame([
            'periodos_id',
            'cursos_id',
            'docentes_id',
            'semana',
            'nivel',
            'version',
            'archivo_path',
            'archivo_nombre',
            'estado',
        ], (new BancoPreguntaLote())->getFillable());

        $this->assertSame([
            'banco_pregunta_lote_id',
            'users_id',
            'accion',
            'comentario',
            'archivo_path',
            'archivo_nombre',
        ], (new BancoPreguntaRevision())->getFillable());

        $this->assertNull(BancoPreguntaRevision::UPDATED_AT);
    }

    public function test_las_migraciones_no_reintroducen_campos_redundantes()
    {
        $lotes = file_get_contents(database_path(
            'migrations/2026_08_31_000001_create_banco_pregunta_lotes_table.php'
        ));
        $revisiones = file_get_contents(database_path(
            'migrations/2026_08_31_000002_create_banco_pregunta_revisiones_table.php'
        ));

        foreach ([
            'cantidad_preguntas',
            'archivo_mime',
            'archivo_size',
            'observacion',
            'enviado_at',
            'revisado_at',
            'revisado_por',
        ] as $campo) {
            $this->assertStringNotContainsString($campo, $lotes);
        }

        foreach (['archivo_mime', 'archivo_size', 'updated_at'] as $campo) {
            $this->assertStringNotContainsString($campo, $revisiones);
        }
    }

    public function test_existen_dos_migraciones_separadas_y_reversibles()
    {
        $archivos = glob(database_path('migrations/2026_08_31_*_banco_pregunta*.php'));

        $this->assertCount(2, $archivos);

        foreach ($archivos as $archivo) {
            $contenido = file_get_contents($archivo);

            $this->assertStringContainsString('Schema::create', $contenido);
            $this->assertStringContainsString('Schema::dropIfExists', $contenido);
        }
    }

    public function test_la_plantilla_proporcionada_es_un_docx_valido()
    {
        $path = resource_path('templates/modelo-preguntas.docx');

        $this->assertFileExists($path);
        $this->assertTrue(DocumentoWord::esDocxValido($path));
    }
}
