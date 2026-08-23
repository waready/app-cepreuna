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

        $calificacionesPorCarga = $this->obtenerUltimasCalificacionesActivasPorCarga($cargas->pluck('id')->all());
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

        //return $request;
        $idEstudiante = Auth::user()->id;
        // dd($idEstudiante);

        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);
        $matricula = Matricula::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (!$periodo || !$matricula) {
            return [
                "message" => 'No se encontró una matrícula activa para el ciclo actual.',
                "status" => false,
            ];
        }

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();
        $calificar = array();
        $carga = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("id", $request->input("cargaId"))
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->first();

        if (!$carga) {
            return [
                "message" => 'La carga académica no pertenece al ciclo activo.',
                "status" => false,
            ];
        }

        //return $carga;
        $calificacionDocente = CalificacionDocente::where([["carga_academicas_id", $request->cargaId],["estado",'1']])
            ->orderby("asistencia_docentes_id", "desc")
            ->first();
        // dd($calificacionDocente);
        //return $request->preguntas;

        $totalCriterios = count($request->preguntasValidar);
        DB::beginTransaction();
        try {

            if (!isset($calificacionDocente)) {
                // return "entro";
                $calificacionDocente = new CalificacionDocente;
                $calificacionDocente->participantes = 0;
                $calificacionDocente->docentes_id = $carga->docentes_id;
                $calificacionDocente->carga_academicas_id = $carga->id;
                $calificacionDocente->asistencia_docentes_id = $carga->asistenciaDocente()->max('id');
                if($inscripcion){
                    if($inscripcion->modalidad == '1'){ //virtual
                        $calificacionDocente->modalidad = '1';  //virtual
                    }elseif($inscripcion->modalidad == '2'){ //preasensial
                        $calificacionDocente->modalidad = '0';  //presensial
                    }
                }
                $calificacionDocente->save();
            }else {
                $calificacionDocente->docentes_id = $carga->docentes_id;
            
                if($inscripcion){
                    if($inscripcion->modalidad == '1'){ //virtual
                        $calificacionDocente->modalidad = '1'; //virtual
                    }elseif($inscripcion->modalidad == '2'){ //preasensial
                        $calificacionDocente->modalidad = '0'; //presensial
                    }
                }
                $calificacionDocente->save();
            }
    
            foreach ($request->preguntas as $key => $value) {
                if (!empty($value)) {

                    $calificacionDetalle = new CalificacionDocenteDetalle;
                    $calificacionDetalle->puntaje = $value;
                    $calificacionDetalle->criterios_id = $key;
                    $calificacionDetalle->estudiantes_id = $idEstudiante;
                    $calificacionDetalle->calificacion_docentes_id = $calificacionDocente->id;
                    $calificacionDetalle->save();
                }
            }

            $totalPuntaje = CalificacionDocenteDetalle::where("calificacion_docentes_id", $calificacionDocente->id)->sum("puntaje");
            $calificacionDocente->participantes = $calificacionDocente->participantes + 1;
            $calificacionDocente->promedio = $totalPuntaje / ($calificacionDocente->participantes * $totalCriterios);
            $calificacionDocente->puntaje_total = $totalPuntaje / $calificacionDocente->participantes;
            $calificacionDocente->observacion = 0;
            $calificacionDocente->save();

            DB::commit();
            $response["message"] = 'Registro guardado correctamente';
            $response["status"] = true;
            $response["calificacion"] = $calificacionDocente->id;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al guardar registro, intentelo nuevamante.';
            $response["error"]  = $e; 
            $response["status"] =  false;
        }

        return $response;
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
    public function getCriteriosDocente(Request $request)
    {
        //return $request->modalidad;
        if ($request->modalidad == "1") {
            $modalidad = "1";
        } else {
            $modalidad = "0";
        }

        $response  = Criterio::where([["estado", "1"], ["tipo", "1"], ["modalidad", $modalidad]])->get();

        return $response;
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

    private function obtenerUltimasCalificacionesActivasPorCarga(array $cargaIds)
    {
        if (empty($cargaIds)) {
            return collect();
        }

        $ultimasCalificacionesIds = CalificacionDocente::where('estado', '1')
            ->whereIn('carga_academicas_id', $cargaIds)
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
}
