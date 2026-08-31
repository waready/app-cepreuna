<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class BancoPregunta extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    public const TIPO_OPCION_MULTIPLE = 'opcion_multiple';
    public const DIFICULTAD_BASICA = 'basica';
    public const DIFICULTAD_INTERMEDIA = 'intermedia';
    public const DIFICULTAD_AVANZADA = 'avanzada';
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    protected $fillable = [
        'banco_pregunta_lote_id',
        'tipo',
        'tema',
        'enunciado',
        'dificultad',
        'explicacion',
        'imagen_path',
        'orden',
        'estado',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function lote()
    {
        return $this->belongsTo(BancoPreguntaLote::class, 'banco_pregunta_lote_id');
    }

    public function alternativas()
    {
        return $this->hasMany(BancoPreguntaAlternativa::class, 'banco_pregunta_id')
            ->orderBy('orden');
    }
}
