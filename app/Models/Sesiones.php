<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Sesiones extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function scopeDelDocenteEnPeriodo($query, $docenteId, $periodoId)
    {
        return $query
            ->where($this->qualifyColumn('periodos_id'), $periodoId)
            ->whereHas('carga', function ($query) use ($docenteId, $periodoId) {
                $query->delDocenteEnPeriodo($docenteId, $periodoId);
            });
    }

    public function carga(){
        return $this->belongsTo('App\Models\CargaAcademica','carga_academicas_id');
    }
}
