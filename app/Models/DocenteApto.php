<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
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

    public function scopeHabilitadoEnPeriodo($query, $periodoId = null)
    {
        $periodoId = $periodoId ?: optional(Periodo::actual())->id;

        if (!$periodoId) {
            return $query->whereRaw('1 = 0');
        }

        $docenteColumn = $query->getModel()->qualifyColumn('docentes_id');

        return $query
            ->where($query->getModel()->qualifyColumn('estado'), '1')
            ->cuentaVigente()
            ->whereExists(function ($inscripcion) use ($docenteColumn, $periodoId) {
                $inscripcion->selectRaw('1')
                    ->from('inscripcion_docentes as inscripcion_actual')
                    ->whereColumn('inscripcion_actual.docentes_id', $docenteColumn)
                    ->where('inscripcion_actual.periodos_id', $periodoId)
                    ->where('inscripcion_actual.apto', '1')
                    ->where('inscripcion_actual.estado', '1');
            });
    }

    public function scopeCuentaVigente($query)
    {
        $model = $query->getModel();

        $ultimosPeriodos = DB::table('docente_aptos as cuenta_periodo')
            ->selectRaw('cuenta_periodo.docentes_id, MAX(cuenta_periodo.periodos_id) as periodos_id')
            ->where('cuenta_periodo.estado', '1')
            ->groupBy('cuenta_periodo.docentes_id');

        $cuentasVigentes = DB::query()
            ->fromSub($ultimosPeriodos, 'ultimo_periodo')
            ->join('docente_aptos as cuenta_vigente', function ($join) {
                $join->on('cuenta_vigente.docentes_id', '=', 'ultimo_periodo.docentes_id')
                    ->on('cuenta_vigente.periodos_id', '=', 'ultimo_periodo.periodos_id');
            })
            ->where('cuenta_vigente.estado', '1')
            ->selectRaw('MAX(cuenta_vigente.id)')
            ->groupBy('cuenta_vigente.docentes_id');

        return $query->whereIn($model->qualifyColumn('id'), $cuentasVigentes);
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

    public function estaHabilitadoEnPeriodo($periodoId): bool
    {
        if (!$periodoId || !$this->getKey()) {
            return false;
        }

        return static::query()
            ->whereKey($this->getKey())
            ->habilitadoEnPeriodo($periodoId)
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
