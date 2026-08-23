<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Matricula extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function scopeDelEstudiante($query, $estudianteId)
    {
        return $query->where(
            $query->getModel()->qualifyColumn('estudiantes_id'),
            $estudianteId
        );
    }

    public function scopeDelPeriodoActual($query, $periodoId = null)
    {
        $periodoId = $periodoId ?: optional(Periodo::actual())->id;

        if (!$periodoId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->qualifyColumn('periodos_id'),
            $periodoId
        );
    }

    public static function actualDelEstudiante($estudianteId, $periodoId = null)
    {
        return static::query()
            ->delEstudiante($estudianteId)
            ->delPeriodoActual($periodoId)
            ->orderByDesc('id')
            ->first();
    }

    public function estudiante()
    {
        return $this->belongsTo('App\Models\Estudiante', 'estudiantes_id');
    }
    public function turno()
    {
        return $this->belongsTo('App\Models\Turno', 'turnos_id');
    }
    public function grupoAula()
    {
        return $this->belongsTo('App\Models\GrupoAula', 'grupo_aulas_id')->with(["grupo", "turno", "aula", "area"]);
    }
    public function inscripciones()
    {
        return $this->belongsTo('App\Models\Inscripciones', 'estudiantes_id');
    }
}
