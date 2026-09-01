<?php

namespace Tests\Unit;

use App\Models\DocenteApto;
use App\Support\DocentePreguntasDemoAccess;
use Tests\TestCase;

class DocentePreguntasDemoAccessTest extends TestCase
{
    public function test_todos_los_docentes_pueden_probar_cuando_no_hay_una_lista(): void
    {
        config([
            'features.docente_preguntas_demo.enabled' => true,
            'features.docente_preguntas_demo.docentes_ids' => [],
        ]);

        $cuenta = new DocenteApto();
        $cuenta->docentes_id = 52;

        $this->assertTrue(app(DocentePreguntasDemoAccess::class)->permite($cuenta));
    }

    public function test_una_lista_configurada_restringe_el_acceso(): void
    {
        config([
            'features.docente_preguntas_demo.enabled' => true,
            'features.docente_preguntas_demo.docentes_ids' => ['80'],
        ]);

        $cuenta = new DocenteApto();
        $cuenta->docentes_id = 52;

        $this->assertFalse(app(DocentePreguntasDemoAccess::class)->permite($cuenta));
    }
}
