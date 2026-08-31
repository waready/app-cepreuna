<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class BancoPreguntaAlternativa extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'banco_pregunta_id',
        'clave',
        'texto',
        'es_correcta',
        'orden',
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
        'orden' => 'integer',
    ];

    public function pregunta()
    {
        return $this->belongsTo(BancoPregunta::class, 'banco_pregunta_id');
    }
}
