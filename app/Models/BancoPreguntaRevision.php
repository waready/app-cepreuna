<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BancoPreguntaRevision extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'banco_pregunta_revisiones';

    public const UPDATED_AT = null;

    public const ACCION_APROBAR = 'aprobar';
    public const ACCION_OBSERVAR = 'observar';
    public const ACCION_RECHAZAR = 'rechazar';

    protected $fillable = [
        'banco_pregunta_lote_id',
        'users_id',
        'accion',
        'comentario',
        'archivo_path',
        'archivo_nombre',
    ];

    public function lote()
    {
        return $this->belongsTo(BancoPreguntaLote::class, 'banco_pregunta_lote_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
