<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Inscripciones extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $filleable = ['areas_id','cantidad_inscrito','estado','tipo_estudiante','turnos_id'];
    protected $guarded = [];

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

    public function estudiante(){
    	return $this->belongsTo('App\Models\Estudiante','estudiantes_id');
    }
    public function area(){
    	return $this->belongsTo('App\Models\Area','areas_id');
    }
    public function sede(){
        return $this->belongsTo('App\Models\Sede','sedes_id');
    }
    public function periodo(){
        return $this->belongsTo('App\Models\Periodo','periodos_id');
    }
    public function turno(){
        return $this->belongsTo('App\Models\Turno','turnos_id');
    }
    public function pago()
    {
        // una inscripcion tiene un pago a traves de
        return $this->hasManyThrough(Pago::class,InscripcionPago::class,'inscripciones_id','id','id','pagos_id')->distinct();
    }
    public function inscripcionPago()
    {
        return $this->hasMany(InscripcionPago::class);
    }
}
