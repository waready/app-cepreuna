<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = "notificaciones";

    public function comentario()
    {
        return $this->belongsTo(Comentario::class)->with(["rol","publicacion"]);
    }
    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class)->with("rol");
    }
    public function rol()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }
}
