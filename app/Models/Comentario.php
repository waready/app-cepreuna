<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use HasFactory;
    protected $table = "comentarios";

    public function rol()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }
    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class);
    }
}
