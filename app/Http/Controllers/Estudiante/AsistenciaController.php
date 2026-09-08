<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaEstudiante;
use App\Models\AsistenciaEstudianteDetalle;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    private const ESTADOS = [
        '1' => ['titulo' => 'Presente', 'clase' => 'bg-success-asistencia'],
        '2' => ['titulo' => 'Tarde', 'clase' => 'bg-warning-asistencia'],
        '3' => ['titulo' => 'Falta', 'clase' => 'bg-danger-asistencia'],
        '4' => ['titulo' => 'Permiso', 'clase' => 'bg-info-asistencia'],
    ];

    public function __construct()
    {
        $this->middleware('auth:estudiante');
    }
    public function index()
    {
        // return view("web.estudiante.asistencia");
        return Inertia::render('Estudiante/Asistencia');
    }

    public function getAsistencia()
    {

        $idEstudiante = Auth::user()->id;
        $matricula = Matricula::actualDelEstudiante($idEstudiante);

        if (!$matricula) {
            return response()->json(["asistencias" => []]);
        }

        $horasAsistencia = DB::table('carga_academicas as ca')
            ->select(
                DB::raw('MAX(ph.hora_fin) as fin'),
                DB::raw('MIN(ph.hora_inicio) as inicio')
            )
            ->join('horarios as h', 'h.carga_academicas_id', 'ca.id')
            ->join('plantilla_horarios as ph', 'ph.id', 'h.plantilla_horarios_id')
            ->where('ca.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->where('ca.periodos_id', $matricula->periodos_id)
            ->where('h.periodos_id', $matricula->periodos_id)
            ->first();

        $asistenciasEstudianteD = AsistenciaEstudianteDetalle::select('asistencia_estudiante_detalles.*')
            ->join('asistencia_estudiantes as ae', 'ae.id', 'asistencia_estudiante_detalles.asistencia_estudiantes_id')
            ->addSelect('ae.fecha')
            ->where('asistencia_estudiante_detalles.estudiantes_id', $idEstudiante)
            ->where('ae.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->get();

        $asistencias = [];

        if (!$horasAsistencia || !$horasAsistencia->inicio || !$horasAsistencia->fin) {
            return response()->json(["asistencias" => $asistencias]);
        }

        foreach ($asistenciasEstudianteD as $k => $val) {
            $estado = self::ESTADOS[(string) $val->estado] ?? [
                'titulo' => 'Asistencia',
                'clase' => 'bg-secondary-asistencia',
            ];
            $descripcion = trim((string) ($val->observacion ?? ''));

            $obj = new \stdClass;
            $obj->start = $val->fecha . ' ' . $horasAsistencia->inicio;
            $obj->end = $val->fecha . ' ' . $horasAsistencia->fin;
            $obj->title = $estado['titulo'];
            $obj->class = $estado['clase'];
            $obj->content = (string) $val->estado === '4' && $descripcion !== '' ? e($descripcion) : '';
            $obj->estado = (string) $val->estado;
            $obj->estado_texto = $estado['titulo'];
            $obj->descripcion = $descripcion !== '' ? $descripcion : null;
            $obj->fecha = $val->fecha;

            $asistencias[] = $obj;
        }
        $response["asistencias"] = $asistencias;

        return response()->json($response);
    }
    public function rangoFechas()
    {
        $idEstudiante = Auth::user()->id;
        $matricula = Matricula::actualDelEstudiante($idEstudiante);

        if (!$matricula) {
            return response()->json(null);
        }

        $horasAsistencia = DB::table('carga_academicas as ca')
            ->select(
                DB::raw('MAX(ph.hora_fin) as fin'),
                DB::raw('MIN(ph.hora_inicio) as inicio')
            )
            ->join('horarios as h', 'h.carga_academicas_id', 'ca.id')
            ->join('plantilla_horarios as ph', 'ph.id', 'h.plantilla_horarios_id')
            ->where('ca.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->where('ca.periodos_id', $matricula->periodos_id)
            ->where('h.periodos_id', $matricula->periodos_id)
            ->first();

        return response()->json($horasAsistencia);
    }
}
