<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    use HasFactory;
    protected $table = "publicaciones";

    public function rol()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }
}
