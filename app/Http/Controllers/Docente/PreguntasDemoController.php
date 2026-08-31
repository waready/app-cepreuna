<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PreguntasDemoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }

    public function index()
    {
        $cuenta = Auth::guard('docente')->user();
        $periodo = Periodo::actual();

        abort_unless($cuenta && $periodo, 404);

        $cargas = DB::table('carga_academicas as ca')
            ->select(
                'ca.id as carga_id',
                'ca.cursos_id as curso_id',
                'c.denominacion as curso',
                'g.denominacion as grupo',
                's.id as sede_id',
                's.denominacion as sede'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->join('aulas as au', 'au.id', 'ga.aulas_id')
            ->join('locales as l', 'l.id', 'au.locales_id')
            ->join('sedes as s', 's.id', 'l.sedes_id')
            ->where('ca.docentes_id', $cuenta->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->orderBy('c.denominacion')
            ->orderBy('g.denominacion')
            ->get();

        $cursos = $cargas
            ->groupBy('curso_id')
            ->map(function ($cargasCurso) {
                $primeraCarga = $cargasCurso->first();
                $grupos = $cargasCurso->pluck('grupo')->filter()->unique()->values();
                $modalidades = $cargasCurso
                    ->map(function ($carga) {
                        $esVirtual = (int) $carga->sede_id === 1
                            || Str::contains(Str::lower((string) $carga->sede), 'virtual');

                        return $esVirtual ? 'Virtual' : 'Presencial';
                    })
                    ->unique()
                    ->values();

                return [
                    'id' => (int) $primeraCarga->curso_id,
                    'curso' => $primeraCarga->curso,
                    'grupos' => $grupos->all(),
                    'modalidades' => $modalidades->all(),
                    'cargas_ids' => $cargasCurso->pluck('carga_id')->map(function ($id) {
                        return (int) $id;
                    })->values()->all(),
                    'label' => sprintf(
                        '%s - %d %s',
                        $primeraCarga->curso,
                        $grupos->count(),
                        $grupos->count() === 1 ? 'grupo' : 'grupos'
                    ),
                ];
            })
            ->values();

        return Inertia::render('Docente/Recurso/PreguntasDemo', [
            'cursos' => $cursos,
            'periodo' => [
                'id' => (int) $periodo->id,
                'nombre' => $periodo->nombre ?? $periodo->codigo ?? "Periodo {$periodo->id}",
            ],
        ]);
    }
}
