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

    public function scopeConCargaEnPeriodo($query, $periodoId)
    {
        $docenteColumn = $query->getModel()->qualifyColumn('docentes_id');

        return $query->whereExists(function ($carga) use ($docenteColumn, $periodoId) {
            $carga->selectRaw('1')
                ->from('carga_academicas as ca')
                ->whereColumn('ca.docentes_id', $docenteColumn)
                ->where('ca.periodos_id', $periodoId)
                ->where('ca.estado', '1');
        });
    }

    public function scopeConCredenciales($query, $usuario, $password)
    {
        if (!is_string($usuario) || trim($usuario) === '' || !is_string($password) || $password === '') {
            return $query->whereRaw('1 = 0');
        }

        $usuarioColumn = $query->getModel()->qualifyColumn('usuario');
        $passwordColumn = $query->getModel()->qualifyColumn('password');

        return $query->where(function ($identidad) use ($usuario, $password, $usuarioColumn, $passwordColumn) {
            $identidad
                ->where(function ($cuenta) use ($usuario, $password, $usuarioColumn, $passwordColumn) {
                    $cuenta->where($usuarioColumn, $usuario)
                        ->where($passwordColumn, $password);
                })
                ->orWhereHas('docente', function ($docente) use ($usuario, $password) {
                    $docenteModel = $docente->getModel();

                    $docente->where($docenteModel->qualifyColumn('usuario'), $usuario)
                        ->where($docenteModel->qualifyColumn('password'), $password);
                });
        });
    }

    public function scopeConIdentidadGoogle($query, $idGoogle)
    {
        $idGoogleColumn = $query->getModel()->qualifyColumn('idgsuite');

        return $query->where(function ($identidad) use ($idGoogle, $idGoogleColumn) {
            $identidad->where($idGoogleColumn, $idGoogle)
                ->orWhereHas('docente', function ($docente) use ($idGoogle) {
                    $docente->where(
                        $docente->getModel()->qualifyColumn('idgsuite'),
                        $idGoogle
                    );
                });
        });
    }

    public function scopeMasReciente($query)
    {
        return $query
            ->orderByDesc($query->getModel()->qualifyColumn('periodos_id'))
            ->orderByDesc($query->getModel()->qualifyColumn('id'));
    }

    public function tieneCargaEnPeriodo($periodoId): bool
    {
        if (!$periodoId || !$this->docentes_id) {
            return false;
        }

        return CargaAcademica::query()
            ->delDocenteEnPeriodo($this->docentes_id, $periodoId)
            ->where('estado', '1')
            ->exists();
    }

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
