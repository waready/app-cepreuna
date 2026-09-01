<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BancoPreguntaLote extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public const ESTADO_EN_REVISION = 'en_revision';
    public const ESTADO_APROBADO = 'aprobado';
    public const ESTADO_OBSERVADO = 'observado';
    public const ESTADO_RECHAZADO = 'rechazado';

    public const NIVEL_BASICO = 'basico';
    public const NIVEL_INTERMEDIO = 'intermedio';
    public const NIVEL_AVANZADO = 'avanzado';

    protected $fillable = [
        'periodos_id',
        'cursos_id',
        'docentes_id',
        'semana',
        'nivel',
        'version',
        'archivo_path',
        'archivo_nombre',
        'estado',
    ];

    protected $casts = [
        'semana' => 'integer',
        'version' => 'integer',
    ];

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodos_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'cursos_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docentes_id');
    }

    public function revision()
    {
        return $this->hasOne(BancoPreguntaRevision::class, 'banco_pregunta_lote_id');
    }
}
