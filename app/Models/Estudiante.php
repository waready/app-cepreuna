<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Estudiante extends Authenticatable implements Auditable
{

    use HasApiTokens, Notifiable, \OwenIt\Auditing\Auditable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guard_name = 'sanctum';
    protected $fillable = [
        'usuario',
    ];
    protected $guarded = ['id'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function model_has_role()
    {
        // if (Auth::user()->hasRole('Super Admin')) {
        return $this->hasOne(ModelHasRole::class, 'model_id')->with('role');
        // } else {
        // return $this->hasOne(ModelHasRole::class, 'model_id')->with('role')->where('roles_id', '!=', '1');
        // }
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documentos_id');
    }
    public function colegio()
    {
        return $this->belongsTo(Colegio::class, 'colegios_id')->with('tipo_colegio');
    }
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
    public function ubigeo()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeos_id');
    }
    public function ubigeo_nacimiento()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeos_nacimiento', 'id');
    }
    public function matricula()
    {
        return $this->hasOne(Matricula::class, 'estudiantes_id');
    }
}
