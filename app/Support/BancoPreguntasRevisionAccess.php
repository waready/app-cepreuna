<?php

namespace App\Support;

use App\Models\User;

class BancoPreguntasRevisionAccess
{
    public function permite($usuario): bool
    {
        if (!config('features.banco_preguntas_revision.enabled', false)
            || !$usuario instanceof User) {
            return false;
        }

        $usuariosIds = array_map(
            'strval',
            config('features.banco_preguntas_revision.usuarios_ids', [])
        );

        return in_array((string) $usuario->getKey(), $usuariosIds, true);
    }
}
