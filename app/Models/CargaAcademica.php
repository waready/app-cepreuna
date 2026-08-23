<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\CalificacionDocente;
use Illuminate\Support\Facades\DB;

class CargaAcademica extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function scopeDelDocenteEnPeriodo($query, $docenteId, $periodoId)
    {
        return $query
            ->where($this->qualifyColumn('docentes_id'), $docenteId)
            ->where($this->qualifyColumn('periodos_id'), $periodoId);
    }

    public function curso()
    {
        return $this->belongsTo('App\Models\Curso', 'cursos_id');
    }
    public function docente()
    {
        return $this->belongsTo('App\Models\Docente', 'docentes_id');
    }
    public function grupoAula()
    {
        return $this->belongsTo('App\Models\GrupoAula', 'grupo_aulas_id')->with(["aula", "area", "grupo"]);
    }

    public function grupo()
    {
        // una inscripcion tiene un pago a traves de
        return $this->hasOneThrough(Grupo::class, GrupoAula::class, 'id', 'id', 'grupo_aulas_id', 'grupos_id');
    }
    public function aula()
    {
        // una inscripcion tiene un pago a traves de
        return $this->hasOneThrough(Aula::class, GrupoAula::class, 'id', 'id', 'grupo_aulas_id', 'aulas_id');
    }
    public function area()
    {
        // una inscripcion tiene un pago a traves de
        return $this->hasOneThrough(Area::class, GrupoAula::class, 'id', 'id', 'grupo_aulas_id', 'areas_id');
    }

    public function getEncuestaRealizadaPorEstudiante(int $idEstudiante)
    {
        return DB::table('calificacion_docentes AS A')
            ->leftJoin('calificacion_docente_detalles AS B', 'A.id', '=', 'B.calificacion_docentes_id')
            ->where('B.estudiantes_id', $idEstudiante)
            ->where([['A.carga_academicas_id', $this->id],['A.estado', "1"]])
            ->count() > 0;
    }

    public function calificacionDocente()
    {
        return $this->hasMany(CalificacionDocente::class, 'carga_academicas_id');
    }

    public function asistenciaDocente()
    {
        return $this->hasMany(AsistenciaDocente::class, 'carga_academicas_id');
    }
}
