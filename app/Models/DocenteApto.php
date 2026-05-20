<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class DocenteApto extends Authenticatable implements Auditable
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
    public function docente()
    {
        return $this->belongsTo('App\Models\Docente', 'docentes_id');
    }

}
