<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaEstudianteDetalle;
use App\Models\Matricula;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    private const HORA_INICIO_PREDETERMINADA = '08:00:00';

    private const HORA_FIN_PREDETERMINADA = '09:00:00';

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

        $asistenciasEstudianteD = AsistenciaEstudianteDetalle::from('asistencia_estudiante_detalles as aed')
            ->select('aed.*', 'ae.fecha', 'ae.grupo_aulas_id')
            ->join('asistencia_estudiantes as ae', 'ae.id', 'aed.asistencia_estudiantes_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ae.grupo_aulas_id')
            ->where('aed.estudiantes_id', $idEstudiante)
            ->where('ga.periodos_id', $matricula->periodos_id)
            ->orderBy('ae.fecha')
            ->orderBy('aed.id')
            ->get();

        $grupoAulaIds = $asistenciasEstudianteD
            ->pluck('grupo_aulas_id')
            ->push($matricula->grupo_aulas_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $horariosPorGrupo = $this->horariosPorGrupo($grupoAulaIds, $matricula->periodos_id);
        $rangoGeneral = $this->rangoGeneral($horariosPorGrupo);
        $horarioActual = $horariosPorGrupo->get($matricula->grupo_aulas_id) ?? $rangoGeneral;
        $asistencias = [];

        foreach ($asistenciasEstudianteD as $k => $val) {
            $horario = $horariosPorGrupo->get($val->grupo_aulas_id) ?? $horarioActual;
            $estado = self::ESTADOS[(string) $val->estado] ?? [
                'titulo' => 'Asistencia',
                'clase' => 'bg-secondary-asistencia',
            ];
            $descripcion = trim((string) ($val->observacion ?? ''));

            $obj = new \stdClass;
            $obj->start = $val->fecha . ' ' . $horario->inicio;
            $obj->end = $val->fecha . ' ' . $horario->fin;
            $obj->title = $estado['titulo'];
            $obj->class = $estado['clase'];
            $obj->content = (string) $val->estado === '4' && $descripcion !== '' ? e($descripcion) : '';
            $obj->estado = (string) $val->estado;
            $obj->estado_texto = $estado['titulo'];
            $obj->descripcion = $descripcion !== '' ? $descripcion : null;
            $obj->fecha = $val->fecha;

            $asistencias[] = $obj;
        }

        return response()->json(['asistencias' => $asistencias]);
    }

    public function rangoFechas()
    {
        $idEstudiante = Auth::user()->id;
        $matricula = Matricula::actualDelEstudiante($idEstudiante);

        if (!$matricula) {
            return response()->json($this->rangoGeneral(collect()));
        }

        $grupoAulaIds = DB::table('asistencia_estudiante_detalles as aed')
            ->join('asistencia_estudiantes as ae', 'ae.id', 'aed.asistencia_estudiantes_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ae.grupo_aulas_id')
            ->where('aed.estudiantes_id', $idEstudiante)
            ->where('ga.periodos_id', $matricula->periodos_id)
            ->distinct()
            ->pluck('ae.grupo_aulas_id')
            ->push($matricula->grupo_aulas_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $horariosPorGrupo = $this->horariosPorGrupo($grupoAulaIds, $matricula->periodos_id);

        return response()->json($this->rangoGeneral($horariosPorGrupo));
    }

    private function horariosPorGrupo(array $grupoAulaIds, int $periodoId)
    {
        if ($grupoAulaIds === []) {
            return collect();
        }

        return DB::table('carga_academicas as ca')
            ->select(
                'ca.grupo_aulas_id',
                DB::raw('MIN(ph.hora_inicio) as inicio'),
                DB::raw('MAX(ph.hora_fin) as fin')
            )
            ->join('horarios as h', 'h.carga_academicas_id', 'ca.id')
            ->join('plantilla_horarios as ph', 'ph.id', 'h.plantilla_horarios_id')
            ->whereIn('ca.grupo_aulas_id', $grupoAulaIds)
            ->where('ca.periodos_id', $periodoId)
            ->where('h.periodos_id', $periodoId)
            ->groupBy('ca.grupo_aulas_id')
            ->get()
            ->keyBy('grupo_aulas_id');
    }

    private function rangoGeneral($horarios): object
    {
        return (object) [
            'inicio' => $horarios->pluck('inicio')->filter()->min() ?: self::HORA_INICIO_PREDETERMINADA,
            'fin' => $horarios->pluck('fin')->filter()->max() ?: self::HORA_FIN_PREDETERMINADA,
        ];
    }
}
