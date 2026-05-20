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
        $matricula = Matricula::where('estudiantes_id', $idEstudiante)->first();
        //return $matricula;
        $periodo = Periodo::where('estado', '1')->first();

        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::where([['matricula', '1'], ['estudiantes_id', $idEstudiante]])->first();

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();
        $calificar = array();
        $cargas = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("grupo_aulas_id", $matricula->grupo_aulas_id)
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->orderBy("cursos_id")
            ->get();

        // $calificacionDocente = CalificacionDocente::with("asistenciaDocente")->where([["carga_academicas_id", 34], ["estado", "1"]])->orderBy("id", "desc")->first();
        // dd($calificacionDocente);
        foreach ($cargas as $carga) {
            // echo $carga->id . " -";

            $calificacionDocente = CalificacionDocente::with("asistenciaDocente", "docente")->where([["carga_academicas_id", $carga->id], ["estado", "1"]])->orderBy("id", "desc")->first();
            // $calificacionDocente = CalificacionDocente::with("asistenciaDocente")->where([["carga_academicas_id", 34], ["estado", "0"]])->orderBy("id", "desc")->first();
            if (isset($calificacionDocente)) {
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
                    $calificacionEstudiante = CalificacionDocenteDetalle::where([["estudiantes_id", $idEstudiante], ["calificacion_docentes_id", $calificacionDocente->id]])->get();

                    if (count($calificacionEstudiante) == 0) {
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

        $inscripcion = Inscripciones::where('estudiantes_id', $idEstudiante)->first();
        $matricula = Matricula::where('estudiantes_id', $idEstudiante)->first();

        $periodo = Periodo::where('estado', '1')->first();

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();
        $calificar = array();
        $carga = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("id", $request->input("cargaId"))
            ->where("estado", "1")
            ->first();

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
        $matricula = Matricula::where('estudiantes_id', $idEstudiante)->first();

        $periodo = Periodo::where('estado', '1')->first();

        // $docenteApto = DocenteApto::find($idDocenteApto);
        // // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();

        $cargas = CargaAcademica::with(["curso", "docente", "grupoAula"])
            ->where("grupo_aulas_id", $matricula->grupo_aulas_id)
            ->where("periodos_id", $periodo->id)
            ->where("estado", "1")
            ->orderBy("cursos_id")
            ->get();

        foreach ($cargas as $carga) {
            if ($carga->grupoAula->estado_encuesta == 1) {
                $carga->encuesta_realizada = $carga->getEncuestaRealizadaPorEstudiante($idEstudiante);
            }
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
        $matricula = Matricula::with("grupoAula")->where("estudiantes_id", Auth::user()->id)->first();
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
        $periodo = Periodo::where('estado', '1')->first();
        $matricula = Matricula::where('estudiantes_id', $idEstudiante)->first();
        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion',
                'c.color as color',
                'c.id as id'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->where('ca.grupo_aulas_id', $matricula->grupo_aulas_id)
            ->orderBy('c.id')
            ->get();
        foreach ($cargaAcademica as $k => $val) {
            $curriculaDetalle = CurriculaDetalle::where([['cursos_id', $val->id], ['curriculas_id', $matricula->curriculas_id]])->first();
            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->denominacion = $val->denominacion;
            $obj->color = $val->color;
            $obj->base_path = env("EXTERNALURLIMAGE");
            $obj->cuadernillos = DB::table('cuadernillos')->select('semana', 'path', 'id')->where([
                ['tipo', '2'],
                ['periodos_id', $periodo->id],
                ['curricula_detalles_id', $curriculaDetalle->id]
            ])->get();
            $cuadernillos[] = $obj;
        }
        $response["cuadernillos"] = $cuadernillos;

        return response()->json($response);
    }
    public function getUrlCuadernillo(Request $request)
    {
        $idEstudiante = Auth::user()->id;
        $matricula = Matricula::where('estudiantes_id', $idEstudiante)->first();

        $periodo = Periodo::where('estado', '1')->first();
        // $curricula = Curricula::where('areas_id',$request->area)->first();

        $curriculaDetalle = CurriculaDetalle::where([['cursos_id', $request->curso], ['curriculas_id', $matricula->curriculas_id]])->first();
        // return response()->json($curriculaDetalle);
        if (empty($curriculaDetalle)) {
            return "";
        } else {
            $cuadernillo = DB::table('cuadernillos')->select('path')->where([
                ['semana', $request->semana],
                ['tipo', '2'],
                ['periodos_id', $periodo->id],
                ['curricula_detalles_id', $curriculaDetalle->id]
            ])->first();

            if (empty($cuadernillo)) {
                return "";
            } else {
                return response()->json($cuadernillo);
            }
        }
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
}
