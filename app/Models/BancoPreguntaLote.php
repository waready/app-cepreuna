<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BancoPreguntaLote extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public const ESTADO_BORRADOR = 'borrador';
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
        'cantidad_preguntas',
        'version',
        'archivo_path',
        'archivo_nombre',
        'archivo_mime',
        'archivo_size',
        'estado',
        'observacion',
        'enviado_at',
        'revisado_at',
        'revisado_por',
    ];

    protected $casts = [
        'semana' => 'integer',
        'cantidad_preguntas' => 'integer',
        'version' => 'integer',
        'archivo_size' => 'integer',
        'enviado_at' => 'datetime',
        'revisado_at' => 'datetime',
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

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function revisiones()
    {
        return $this->hasMany(BancoPreguntaRevision::class, 'banco_pregunta_lote_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
