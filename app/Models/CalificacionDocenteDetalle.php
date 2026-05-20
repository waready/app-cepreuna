<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionDocenteDetalle extends Model
{
    public function criterio(){
        return $this->belongsTo('App\Models\Criterio','criterios_id');
    }
    public function CalificacionDocente(){
        return $this->belongsTo('App\Models\CalificacionDocente','calificacion_docentes_id');
    }
}
