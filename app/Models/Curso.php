<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    public function lotesBancoPreguntas()
    {
        return $this->hasMany(BancoPreguntaLote::class, 'cursos_id');
    }
}
