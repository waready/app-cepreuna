<?php

namespace Tests\Feature;

use App\Models\BancoPregunta;
use App\Models\BancoPreguntaAlternativa;
use App\Models\BancoPreguntaLote;
use Tests\TestCase;

class BancoPreguntasSchemaTest extends TestCase
{
    public function test_los_modelos_apuntan_a_las_tres_tablas_propuestas()
    {
        $this->assertSame('banco_pregunta_lotes', (new BancoPreguntaLote())->getTable());
        $this->assertSame('banco_preguntas', (new BancoPregunta())->getTable());
        $this->assertSame(
            'banco_pregunta_alternativas',
            (new BancoPreguntaAlternativa())->getTable()
        );
    }

    public function test_las_relaciones_utilizan_las_claves_del_esquema()
    {
        $lote = new BancoPreguntaLote();
        $pregunta = new BancoPregunta();
        $alternativa = new BancoPreguntaAlternativa();

        $this->assertSame('periodos_id', $lote->periodo()->getForeignKeyName());
        $this->assertSame('cursos_id', $lote->curso()->getForeignKeyName());
        $this->assertSame('docentes_id', $lote->docente()->getForeignKeyName());
        $this->assertSame(
            'banco_pregunta_lote_id',
            $pregunta->lote()->getForeignKeyName()
        );
        $this->assertSame(
            'banco_pregunta_id',
            $alternativa->pregunta()->getForeignKeyName()
        );
    }

    public function test_el_flujo_declara_estados_y_dificultades_controlados()
    {
        $this->assertSame('borrador', BancoPreguntaLote::ESTADO_BORRADOR);
        $this->assertSame('en_revision', BancoPreguntaLote::ESTADO_EN_REVISION);
        $this->assertSame('aprobado', BancoPreguntaLote::ESTADO_APROBADO);
        $this->assertSame('observado', BancoPreguntaLote::ESTADO_OBSERVADO);

        $this->assertSame('opcion_multiple', BancoPregunta::TIPO_OPCION_MULTIPLE);
        $this->assertSame('basica', BancoPregunta::DIFICULTAD_BASICA);
        $this->assertSame('intermedia', BancoPregunta::DIFICULTAD_INTERMEDIA);
        $this->assertSame('avanzada', BancoPregunta::DIFICULTAD_AVANZADA);
    }

    public function test_existen_migraciones_separadas_y_reversibles()
    {
        $archivos = glob(database_path('migrations/2026_08_31_*_banco_pregunta*.php'));

        $this->assertCount(3, $archivos);

        foreach ($archivos as $archivo) {
            $contenido = file_get_contents($archivo);

            $this->assertStringContainsString('Schema::create', $contenido);
            $this->assertStringContainsString('Schema::dropIfExists', $contenido);
        }
    }
}
