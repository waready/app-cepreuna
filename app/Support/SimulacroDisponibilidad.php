<?php

namespace App\Support;

use App\Models\Periodo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimulacroDisponibilidad
{
    public function habilitado(): bool
    {
        $periodo = Periodo::actual();
        if (! $periodo || ! Schema::hasTable('simulacro_configuraciones')) {
            return false;
        }

        // La habilitacion controla el menu; el plazo de inscripcion no debe
        // impedir descargar la ficha cuando las inscripciones ya cerraron.
        return DB::table('simulacro_configuraciones')
            ->where('periodos_id', $periodo->id)
            ->where('estado', 1)
            ->exists();
    }
}
