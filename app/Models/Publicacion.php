<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    use HasFactory;
    protected $table = "publicaciones";

    protected $appends = [
        'archivo_url',
        'imagen_pub_url',
        'imagen_tumb_url',
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

    public function getArchivoUrlAttribute(): string
    {
        return MediaUrl::publication($this->attributes['archivo'] ?? null);
    }

    public function getImagenPubUrlAttribute(): string
    {
        return MediaUrl::publication($this->attributes['imagen_pub'] ?? null);
    }

    public function getImagenTumbUrlAttribute(): string
    {
        return MediaUrl::publication($this->attributes['imagen_tumb'] ?? null);
    }

    public function rol()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }
}
