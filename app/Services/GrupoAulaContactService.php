<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GrupoAulaContactService
{
    public function obtener(array $grupoAulaIds, int $periodoId): Collection
    {
        $grupoAulaIds = collect($grupoAulaIds)
            ->filter(function ($id) {
                return is_numeric($id) && (int) $id > 0;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if ($grupoAulaIds->isEmpty()) {
            return collect();
        }

        $auxiliaresActuales = DB::table('auxiliar_grupos')
            ->selectRaw('MAX(id) as id, grupo_aulas_id')
            ->where('periodos_id', $periodoId)
            ->whereIn('grupo_aulas_id', $grupoAulaIds->all())
            ->groupBy('grupo_aulas_id');

        $coordinadoresActuales = DB::table('auxiliar_coordinadores')
            ->selectRaw('MAX(id) as id, auxiliares_id')
            ->where('periodos_id', $periodoId)
            ->groupBy('auxiliares_id');

        $telefonosCoordinadores = DB::table('auxiliares')
            ->selectRaw('MAX(id) as id, users_id')
            ->whereNotNull('telefono')
            ->whereRaw("TRIM(telefono) <> ''")
            ->groupBy('users_id');

        return DB::query()
            ->fromSub($auxiliaresActuales, 'ag_actual')
            ->join('auxiliar_grupos as ag', 'ag.id', '=', 'ag_actual.id')
            ->leftJoin('auxiliares as aux', 'aux.id', '=', 'ag.auxiliares_id')
            ->leftJoin('users as aux_user', 'aux_user.id', '=', 'aux.users_id')
            ->leftJoinSub($coordinadoresActuales, 'ac_actual', function ($join) {
                $join->on('ac_actual.auxiliares_id', '=', 'ag.auxiliares_id');
            })
            ->leftJoin('auxiliar_coordinadores as ac', 'ac.id', '=', 'ac_actual.id')
            ->leftJoin('users as coord_user', 'coord_user.id', '=', 'ac.users_id')
            ->leftJoinSub($telefonosCoordinadores, 'coord_aux_actual', function ($join) {
                $join->on('coord_aux_actual.users_id', '=', 'ac.users_id');
            })
            ->leftJoin('auxiliares as coord_aux', 'coord_aux.id', '=', 'coord_aux_actual.id')
            ->select([
                'ag.grupo_aulas_id',
                'ag.id as auxiliar_asignacion_id',
                'aux.telefono as auxiliar_telefono',
                'aux_user.name as auxiliar_nombre',
                'aux_user.paterno as auxiliar_paterno',
                'aux_user.materno as auxiliar_materno',
                'ac.id as coordinador_asignacion_id',
                'coord_aux.telefono as coordinador_telefono',
                'coord_user.name as coordinador_nombre',
                'coord_user.paterno as coordinador_paterno',
                'coord_user.materno as coordinador_materno',
            ])
            ->get()
            ->mapWithKeys(function ($fila) {
                return [
                    (int) $fila->grupo_aulas_id => [
                        'auxiliar' => $this->contacto(
                            $fila->auxiliar_asignacion_id,
                            $fila->auxiliar_nombre,
                            $fila->auxiliar_paterno,
                            $fila->auxiliar_materno,
                            $fila->auxiliar_telefono
                        ),
                        'coordinador' => $this->contacto(
                            $fila->coordinador_asignacion_id,
                            $fila->coordinador_nombre,
                            $fila->coordinador_paterno,
                            $fila->coordinador_materno,
                            $fila->coordinador_telefono
                        ),
                    ],
                ];
            });
    }

    private function contacto($asignacionId, $nombre, $paterno, $materno, $telefono): ?array
    {
        if (!$asignacionId) {
            return null;
        }

        $nombreCompleto = collect([$paterno, $materno, $nombre])
            ->filter(function ($parte) {
                return is_string($parte) && trim($parte) !== '';
            })
            ->map(function ($parte) {
                return trim($parte);
            })
            ->implode(' ');

        $telefono = is_string($telefono) ? trim($telefono) : $telefono;

        return [
            'nombre' => $nombreCompleto ?: 'Nombre no registrado',
            'telefono' => $telefono ?: null,
        ];
    }
}
