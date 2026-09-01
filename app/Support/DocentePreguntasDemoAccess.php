<?php

namespace App\Support;

use App\Models\DocenteApto;

class DocentePreguntasDemoAccess
{
    public function permite(?DocenteApto $cuenta): bool
    {
        if (!config('features.docente_preguntas_demo.enabled', false) || !$cuenta) {
            return false;
        }

        $docentesIds = array_map(
            'strval',
            config('features.docente_preguntas_demo.docentes_ids', [])
        );

        if ($docentesIds === []) {
            return true;
        }

        return in_array((string) $cuenta->docentes_id, $docentesIds, true);
    }
}
