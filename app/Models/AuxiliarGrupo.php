<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuxiliarGrupo extends Model
{
    //
    public function auxiliar()
    {
        return $this->belongsTo('App\Models\Auxiliar', 'auxiliares_id')->with('user');
    }
}
