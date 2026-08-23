<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    public function scopeActivo($query)
    {
        return $query->where(function ($query) {
            $estadoColumn = $query->getModel()->qualifyColumn('estado');

            $query->where($estadoColumn, '1')
                ->orWhere($estadoColumn, 1)
                ->orWhere($estadoColumn, 'activo');
        });
    }

    public static function actual()
    {
        return static::query()
            ->activo()
            ->orderByDesc((new static())->qualifyColumn('id'))
            ->first();
    }
}
