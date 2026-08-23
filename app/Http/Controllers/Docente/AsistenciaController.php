<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }
    public function index()
    {
        return Inertia::render('Docente/Asistencia');
    }
    public function getAsistencia()
    {
        $periodo = Periodo::actual();
        $docenteApto = Auth::guard('docente')->user();

        if (!$periodo || !$docenteApto || !$docenteApto->tieneCargaEnPeriodo($periodo->id)) {
            return response()->json(['asistencias' => []]);
        }

        $asistenciasDocente = DB::table('asistencia_docentes as ad')
            ->select(
                'ad.fecha',
                'ad.hora_inicio',
                'ad.hora_fin',
                'ad.observacion',
                'ad.estado',
                'ca.tipo',
                'c.denominacion as curso',
                'g.denominacion as grupo',
                DB::raw('DATE_FORMAT(ad.fecha,"%d-%m-%Y") as fecha_asistencia')
            )
            ->join('carga_academicas as ca', 'ca.id', 'ad.carga_academicas_id')
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->where('ad.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->orderBy('ad.fecha')
            ->orderBy('ad.hora_inicio')
            ->get();

        $asistencias = [];

        foreach ($asistenciasDocente as $k => $val) {
            $obj = new \stdClass;
            $obj->start = $val->fecha . ' ' . $val->hora_inicio;
            $obj->end = $val->fecha . ' ' . $val->hora_fin;
            $obj->title = $val->curso . " (" . $val->grupo . ")";
            $obj->class = $val->estado == '1' ? 'asis bg-success-asistencia' : ($val->estado == '2' ? 'asis bg-warning-asistencia' : 'asis bg-danger-asistencia');
            $obj->obs = $val->observacion;
            $obj->estado = $val->estado;
            $obj->tipo = $val->tipo;
            $obj->fecha_asistencia = $val->fecha_asistencia;
            $obj->hora_inicio = $val->hora_inicio;
            $obj->hora_fin = $val->hora_fin;

            $asistencias[] = $obj;
        }
        $response["asistencias"] = $asistencias;

        return response()->json($response);
    }
}
