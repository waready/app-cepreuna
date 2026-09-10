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

        if (!$periodo || !$docenteApto || !$docenteApto->estaHabilitadoEnPeriodo($periodo->id)) {
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
            ->where('ad.periodos_id', $periodo->id)
            ->where('ca.periodos_id', $periodo->id)
            // NO se filtra por `ca.estado`. Antes se exigia que la carga
            // siguiera vigente, y eso escondia las suplencias: cuando el
            // suplente devuelve el curso al titular su carga pasa a estado '0'
            // -solo uno dicta a la vez- y con ella desaparecia del panel una
            // clase que si dicto y que si se le paga. En el ciclo 2026-II eran
            // 214 asistencias de 112 docentes, 537 horas de pago que no podian
            // ver.
            //
            // El estado de la carga dice quien dicta HOY; la asistencia dice lo
            // que paso ESE dia, y eso no cambia despues. La fila ya esta acotada
            // al docente por `ad.docentes_id`, asi que no hace falta ese filtro
            // para nada.
            ->orderBy('ad.fecha')
            ->orderBy('ad.hora_inicio')
            ->get();

        $asistencias = [];

        foreach ($asistenciasDocente as $k => $val) {
            $obj = new \stdClass;
            $obj->start = $val->fecha . ' ' . $val->hora_inicio;
            $obj->end = $val->fecha . ' ' . $val->hora_fin;
            // `ca.tipo` = '2' es suplencia. Ya se consultaba pero no se usaba:
            // sin decirlo, al docente le aparecia una clase que no es de su
            // horario habitual y no tenia como saber por que.
            $esSuplencia = (string) $val->tipo === '2';
            $obj->title = $val->curso . " (" . $val->grupo . ")" . ($esSuplencia ? ' — Suplencia' : '');
            $obj->es_suplencia = $esSuplencia;
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
