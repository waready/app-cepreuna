<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\CalificacionDocente;
use App\Models\CalificacionDocenteDetalle;
use App\Models\CargaAcademica;
use App\Models\Curso;
use App\Models\Criterio;
use App\Models\Inscripciones;
use App\Models\CurriculaDetalle;
use App\Models\Matricula;
use App\Models\Periodo;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CursosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:estudiante');
    }
    public function index()
    {

        $idEstudiante = Auth::user()->id;
        // dd($idEstudiante);
        $periodo = Periodo::actual();
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual(optional($periodo)->id)
            ->where('matricula', '1')
            ->latest('id')
            ->first();

        if (!$periodo || !$matricula) {
            return Inertia::render('Estudiante/Curso', [
                "calificacion" => [],
                "inscripcion" => $inscripcion,
            ]);
        }

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();
        $calificar = array();
        $cargas = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("grupo_aulas_id", $matricula->grupo_aulas_id)
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->orderBy("cursos_id")
            ->get();

        $calificacionesPorCarga = $this->obtenerUltimasCalificacionesActivasPorCarga(
            $cargas->pluck('id')->all(),
            $periodo->id
        );
        $calificacionesRespondidas = [];

        if ($calificacionesPorCarga->isNotEmpty()) {
            $calificacionesRespondidas = array_fill_keys(
                CalificacionDocenteDetalle::where("estudiantes_id", $idEstudiante)
                    ->whereIn("calificacion_docentes_id", $calificacionesPorCarga->pluck('id')->all())
                    ->pluck("calificacion_docentes_id")
                    ->all(),
                true
            );
        }

        // $calificacionDocente = CalificacionDocente::with("asistenciaDocente")->where([["carga_academicas_id", 34], ["estado", "1"]])->orderBy("id", "desc")->first();
        // dd($calificacionDocente);
        foreach ($cargas as $carga) {
            $calificacionDocente = $calificacionesPorCarga->get($carga->id);
            // $calificacionDocente = CalificacionDocente::with("asistenciaDocente")->where([["carga_academicas_id", 34], ["estado", "0"]])->orderBy("id", "desc")->first();
            if (isset($calificacionDocente) && $calificacionDocente->asistenciaDocente) {
                $fechaHoraFin = new DateTime($calificacionDocente->asistenciaDocente->fecha . " " . $calificacionDocente->asistenciaDocente->hora_fin);
                // $fechaHoraFin = new DateTime("2021-12-02 11:03:00");
                // $fechaHoraFin->modify('+24 hour');
                $fechaHoraFin->modify('+15 minutes');
                $fechaActual = new DateTime();
                $finDiaActual = new DateTime(date($calificacionDocente->asistenciaDocente->fecha . " 23:59:59"));
                // dd($finDiaActual);
                if ($fechaActual > $finDiaActual) {
                    //$calificacionDocente->estado = "0";
                    //$calificacionDocente->save();
                } else {
                    if (!isset($calificacionesRespondidas[$calificacionDocente->id])) {
                        array_push($calificar, $calificacionDocente);
                    }
                }
            }
        }
        // $response = json_encode($calificar);
        //dd($calificar);
        // return view('web.estudiante.cursos', $response);
        return Inertia::render('Estudiante/Curso', [
            "calificacion" => $calificar,
            "inscripcion" => $inscripcion
        ]);
    }
    public function calificacionDocentePorCarga(Request $request)
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$inscripcion || !$matricula) {
            return response()->json([
                "message" => 'No se encontró una matrícula activa para el ciclo actual.',
                "status" => false,
            ], 422);
        }

        $datos = $request->validate([
            'cargaId' => 'required|integer',
            'preguntas' => 'required|array|min:1',
            'preguntas.*' => 'required|integer|between:1,5',
        ], [
            'preguntas.required' => 'Debe responder todos los criterios de evaluación.',
            'preguntas.*.between' => 'Cada calificación debe estar entre 1 y 5.',
        ]);

        $carga = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("id", $datos["cargaId"])
            ->where("grupo_aulas_id", $matricula->grupo_aulas_id)
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->first();

        if (!$carga) {
            return response()->json([
                "message" => 'El curso no pertenece a la matrícula activa del estudiante.',
                "status" => false,
            ], 422);
        }

        if (!$carga->docentes_id || (string) optional($carga->grupoAula)->estado_encuesta !== '1') {
            return response()->json([
                "message" => 'La calificación docente no está habilitada para este curso.',
                "status" => false,
            ], 422);
        }

        $criterios = $this->obtenerCriteriosEncuesta($inscripcion);
        $respuestas = collect($datos['preguntas'])->mapWithKeys(function ($puntaje, $criterioId) {
            return [(int) $criterioId => (int) $puntaje];
        });
        $criterioIds = $criterios->pluck('id')->map(function ($criterioId) {
            return (int) $criterioId;
        });

        if (
            $criterios->isEmpty()
            || $respuestas->count() !== $criterios->count()
            || $criterioIds->diff($respuestas->keys())->isNotEmpty()
            || $respuestas->keys()->diff($criterioIds)->isNotEmpty()
        ) {
            return response()->json([
                "message" => 'Debe responder todos los criterios de evaluación disponibles.',
                "status" => false,
            ], 422);
        }

        try {
            $resultado = DB::transaction(function () use (
                $carga,
                $criterios,
                $idEstudiante,
                $inscripcion,
                $matricula,
                $periodo,
                $respuestas
            ) {
                // Serializa los envíos de una misma carga para evitar duplicados por doble clic.
                DB::table('carga_academicas')
                    ->where('id', $carga->id)
                    ->lockForUpdate()
                    ->first();

                $calificacionRespondida = DB::table('calificacion_docentes as cd')
                    ->join('calificacion_docente_detalles as cdd', 'cdd.calificacion_docentes_id', 'cd.id')
                    ->where('cd.carga_academicas_id', $carga->id)
                    ->where('cd.estado', '1')
                    ->where('cdd.estudiantes_id', $idEstudiante)
                    ->where(function ($query) use ($periodo) {
                        $query->where('cd.periodos_id', $periodo->id)
                            ->orWhereNull('cd.periodos_id');
                    })
                    ->select('cd.id')
                    ->first();

                if ($calificacionRespondida) {
                    return [
                        'message' => 'La calificación de este docente ya fue registrada.',
                        'status' => true,
                        'calificacion' => $calificacionRespondida->id,
                        'already_registered' => true,
                    ];
                }

                $calificacionDocente = CalificacionDocente::where('carga_academicas_id', $carga->id)
                    ->where('estado', '1')
                    ->where(function ($query) use ($periodo) {
                        $query->where('periodos_id', $periodo->id)
                            ->orWhereNull('periodos_id');
                    })
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$calificacionDocente) {
                    $calificacionDocente = new CalificacionDocente;
                    $calificacionDocente->participantes = 0;
                    $calificacionDocente->carga_academicas_id = $carga->id;
                    $calificacionDocente->asistencia_docentes_id = $carga->asistenciaDocente()->max('id');
                    $calificacionDocente->estado = '1';
                }

                $calificacionDocente->periodos_id = $periodo->id;
                $calificacionDocente->grupo_aulas_id = $matricula->grupo_aulas_id;
                $calificacionDocente->docentes_id = $carga->docentes_id;
                $calificacionDocente->modalidad = $this->normalizarModalidadEncuesta($inscripcion->modalidad);
                $calificacionDocente->save();

                foreach ($criterios as $criterio) {
                    $calificacionDetalle = new CalificacionDocenteDetalle;
                    $calificacionDetalle->periodos_id = $periodo->id;
                    $calificacionDetalle->puntaje = $respuestas->get((int) $criterio->id);
                    $calificacionDetalle->criterios_id = $criterio->id;
                    $calificacionDetalle->estudiantes_id = $idEstudiante;
                    $calificacionDetalle->calificacion_docentes_id = $calificacionDocente->id;
                    $calificacionDetalle->save();
                }

                $detalles = CalificacionDocenteDetalle::where(
                    'calificacion_docentes_id',
                    $calificacionDocente->id
                );
                $totalPuntaje = (clone $detalles)->sum('puntaje');
                $totalRespuestas = (clone $detalles)->count();
                $participantes = (clone $detalles)
                    ->distinct('estudiantes_id')
                    ->count('estudiantes_id');

                $calificacionDocente->participantes = $participantes;
                $calificacionDocente->promedio = $totalRespuestas > 0
                    ? $totalPuntaje / $totalRespuestas
                    : 0;
                $calificacionDocente->puntaje_total = $participantes > 0
                    ? $totalPuntaje / $participantes
                    : 0;
                $calificacionDocente->observacion = 0;
                $calificacionDocente->save();

                return [
                    'message' => 'Calificación registrada correctamente.',
                    'status' => true,
                    'calificacion' => $calificacionDocente->id,
                ];
            });

            return response()->json($resultado);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                "message" => 'No se pudo registrar la calificación. Inténtelo nuevamente.',
                "status" => false,
            ], 500);
        }
    }

    public function getCarga()
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$matricula) {
            return response()->json(['carga' => []]);
        }

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();

        $cargas = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("grupo_aulas_id", $matricula->grupo_aulas_id)
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->orderBy("cursos_id")
            ->get();

        $cargasConEncuestaHabilitada = $cargas
            ->filter(function ($carga) {
                return optional($carga->grupoAula)->estado_encuesta == 1;
            })
            ->pluck('id')
            ->all();

        $encuestasRealizadas = [];

        if (!empty($cargasConEncuestaHabilitada)) {
            $encuestasRealizadas = array_fill_keys(
                DB::table('calificacion_docentes as cd')
                    ->join('calificacion_docente_detalles as cdd', 'cdd.calificacion_docentes_id', 'cd.id')
                    ->where('cdd.estudiantes_id', $idEstudiante)
                    ->where('cd.estado', '1')
                    ->whereIn('cd.carga_academicas_id', $cargasConEncuestaHabilitada)
                    ->where(function ($query) use ($periodo) {
                        $query->where('cd.periodos_id', $periodo->id)
                            ->orWhereNull('cd.periodos_id');
                    })
                    ->distinct()
                    ->pluck('cd.carga_academicas_id')
                    ->all(),
                true
            );
        }

        foreach ($cargas as $carga) {
            $carga->encuesta_realizada = isset($encuestasRealizadas[$carga->id]);
        }
        // esto muestra los cursos que el estudiante califico
        // $response["carga"] = CalificacionDocenteDetalle::with("CalificacionDocente")->where('estudiantes_id', $idEstudiante)->get()
        //     ->groupBy('calificacion_docentes_id')
        //     ->map(function ($group) {
        //         return $group->first();
        //     });
        $response = [];
        $response['carga'] = $cargas;

        return response()->json($response);
    }
    public function indexCuadernillo()
    {
        $matricula = Matricula::query()
            ->with("grupoAula")
            ->delEstudiante(Auth::user()->id)
            ->delPeriodoActual()
            ->latest('id')
            ->first();
        $response["area"] = 0;
        if ($matricula) {
            $response["area"] = $matricula->grupoAula->area->id;
        }

        // return view('web.estudiante.cuadernillos');
        return Inertia::render('Estudiante/Cuadernillo', ["data" => $response]);
    }
    public function getCursosEstudiante()
    {
        $idEstudiante = Auth::user()->id;
        $cuadernillos = [];
        $periodo = Periodo::actual();
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$matricula) {
            return response()->json(["cuadernillos" => $cuadernillos]);
        }

        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion',
                'c.color as color',
                'c.id as id'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->where('ca.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->where('ca.periodos_id', $periodo->id)
            ->orderBy('c.id')
            ->get();

        $curriculaDetalles = $this->obtenerCurriculaDetallesPorCurso(
            $matricula->curriculas_id,
            $cargaAcademica->pluck('id')->unique()->values()->all()
        );

        $cuadernillosPorDetalle = collect();

        if ($curriculaDetalles->isNotEmpty()) {
            $cuadernillosPorDetalle = DB::table('cuadernillos')
                ->select('semana', 'path', 'id', 'curricula_detalles_id')
                ->where('tipo', '2')
                ->where('periodos_id', $periodo->id)
                ->whereIn('curricula_detalles_id', $curriculaDetalles->pluck('id')->all())
                ->orderBy('semana')
                ->get()
                ->groupBy('curricula_detalles_id');
        }

        foreach ($cargaAcademica as $k => $val) {
            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->denominacion = $val->denominacion;
            $obj->color = $val->color;
            $obj->base_path = config('app.external_image_url');
            $curriculaDetalle = $curriculaDetalles->get($val->id);
            $obj->cuadernillos = $curriculaDetalle
                ? $cuadernillosPorDetalle->get($curriculaDetalle->id, collect())->values()
                : collect();
            $cuadernillos[] = $obj;
        }
        $response["cuadernillos"] = $cuadernillos;

        return response()->json($response);
    }
    public function getUrlCuadernillo(Request $request)
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$matricula) {
            return "";
        }

        $cuadernillo = DB::table('cuadernillos as cu')
            ->select('cu.path')
            ->join('curricula_detalles as cd', 'cd.id', 'cu.curricula_detalles_id')
            ->where('cu.semana', $request->semana)
            ->where('cu.tipo', '2')
            ->where('cu.periodos_id', $periodo->id)
            ->where('cd.cursos_id', $request->curso)
            ->where('cd.curriculas_id', $matricula->curriculas_id)
            ->first();

        if (empty($cuadernillo)) {
            return "";
        }

        return response()->json($cuadernillo);
    }
    public function getCriteriosDocente()
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$inscripcion) {
            return response()->json([]);
        }

        return response()->json($this->obtenerCriteriosEncuesta($inscripcion));
    }
    public function CalificarDocente(Request $request, $id)
    {
        $idEstudiante = Auth::user()->id;
        $rules = $request->validate([
            'preguntasValidar.*' => 'required|integer|max:10|min:1',

        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
            'preguntasValidar.*.min' => '* El valor minimo de calificación es de 1.',
            'preguntasValidar.*.max' => '* El valor maximo de calificación es de 10.',
        ]);

        $totalCriterios = count($request->preguntasValidar);
        DB::beginTransaction();
        try {
            foreach ($request->preguntas as $key => $value) {
                if (!empty($value)) {

                    $calificacionDetalle = new CalificacionDocenteDetalle;
                    $calificacionDetalle->puntaje = $value;
                    $calificacionDetalle->criterios_id = $key;
                    $calificacionDetalle->estudiantes_id = $idEstudiante;
                    $calificacionDetalle->calificacion_docentes_id = $id;
                    $calificacionDetalle->save();
                }
            }

            $totalPuntaje = CalificacionDocenteDetalle::where("calificacion_docentes_id", $id)->sum("puntaje");
            $calificacionDocente = CalificacionDocente::find($id);
            $calificacionDocente->participantes = $calificacionDocente->participantes + 1;
            $calificacionDocente->promedio = $totalPuntaje / ($calificacionDocente->participantes * $totalCriterios);
            $calificacionDocente->save();

            DB::commit();
            $response["message"] = 'Registro guardado correctamente';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al guardar registro, intentelo nuevamante.';
            $response["status"] =  false;
        }

        return $response;
        // dd($request->all(), $id);
    }

    public function indexTemario()
    {
        $idEstudiante = Auth::user()->id;
        $matricula = Matricula::query()
            ->with("grupoAula")
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();
        $response["area"] = 0;
        if ($matricula) {
            $response["area"] = $matricula->grupoAula->area->id;
        }

        return Inertia::render('Estudiante/Temario', ["data" => $response]);
    }
    
    public function getCursosEstudianteTemario()
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$matricula) {
            return response()->json(["temarios" => []]);
        }

        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion as curso',
                'c.color as color',
                'c.id as id',
                'a.id as idArea',
                'a.denominacion as area'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('areas as a', 'a.id', 'ga.areas_id')
            ->where('ca.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->distinct()
            ->orderBy('a.id', 'asc')
            ->orderBy('c.id', 'asc')
            ->get();

        $curriculaDetalles = $this->obtenerCurriculaDetallesPorCurso(
            $matricula->curriculas_id,
            $cargaAcademica->pluck('id')->all()
        );

        $temariosPorDetalle = collect();

        if ($curriculaDetalles->isNotEmpty()) {
            $temariosPorDetalle = DB::table('temarios')
                ->select('path', 'id', 'curricula_detalles_id')
                ->where('periodos_id', $periodo->id)
                ->whereIn('curricula_detalles_id', $curriculaDetalles->pluck('id')->all())
                ->get()
                ->keyBy('curricula_detalles_id');
        }

        $temarios = [];
        foreach ($cargaAcademica as $k => $val) {
            $curriculaDetalle = $curriculaDetalles->get($val->id);

            if ($curriculaDetalle) {
                $obj = new \stdClass;
                $obj->id = $val->id;
                $obj->area = $val->area;
                $obj->curso = $val->curso;
                $obj->color = $val->color;
                $obj->base_path = config('app.external_image_url');
                $obj->temarios = $temariosPorDetalle->get($curriculaDetalle->id);
                $temarios[] = $obj;
            }
        }
        $response["temarios"] = $temarios;

        return response()->json($response);
    }

    private function obtenerCurriculaDetallesPorCurso($curriculaId, array $cursosIds)
    {
        if (empty($curriculaId) || empty($cursosIds)) {
            return collect();
        }

        return CurriculaDetalle::where('curriculas_id', $curriculaId)
            ->whereIn('cursos_id', $cursosIds)
            ->get()
            ->keyBy('cursos_id');
    }

    private function obtenerUltimasCalificacionesActivasPorCarga(array $cargaIds, $periodoId = null)
    {
        if (empty($cargaIds)) {
            return collect();
        }

        $ultimasCalificacionesIds = CalificacionDocente::where('estado', '1')
            ->whereIn('carga_academicas_id', $cargaIds)
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where(function ($periodoQuery) use ($periodoId) {
                    $periodoQuery->where('periodos_id', $periodoId)
                        ->orWhereNull('periodos_id');
                });
            })
            ->selectRaw('MAX(id) as id')
            ->groupBy('carga_academicas_id')
            ->pluck('id')
            ->all();

        if (empty($ultimasCalificacionesIds)) {
            return collect();
        }

        return CalificacionDocente::with("asistenciaDocente", "docente")
            ->whereIn('id', $ultimasCalificacionesIds)
            ->get()
            ->keyBy('carga_academicas_id');
    }

    private function obtenerCriteriosEncuesta($inscripcion)
    {
        if (!$inscripcion) {
            return collect();
        }

        $consultaBase = Criterio::where('estado', '1')
            ->where('tipo', '1')
            ->orderBy('id');
        $criterios = (clone $consultaBase)
            ->where('modalidad', $this->normalizarModalidadEncuesta($inscripcion->modalidad))
            ->get();

        // Conserva compatibilidad con criterios antiguos que no separaban modalidad.
        if ($criterios->isEmpty()) {
            $criterios = (clone $consultaBase)
                ->where(function ($query) {
                    $query->whereNull('modalidad')
                        ->orWhere('modalidad', '');
                })
                ->get();
        }

        if ($criterios->isEmpty()) {
            $criterios = $consultaBase->get();
        }

        return $criterios;
    }

    private function normalizarModalidadEncuesta($modalidad)
    {
        return (string) $modalidad === '1' ? '1' : '0';
    }
}
