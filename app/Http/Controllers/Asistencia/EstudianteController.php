<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaEstudiante;
use App\Models\AsistenciaEstudianteDetalle;
use App\Models\Estudiante;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $response["aperturados"] = AsistenciaEstudiante::with("grupo")->get();
        $response["aperturados"] = DB::table("asistencia_estudiantes as ae")
            ->select(
                DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as denominacion"),
                "ae.id",
                "ae.estado",
                "ga.id as grupo"
            )
            ->join("grupo_aulas as ga", "ga.id", "ae.grupo_aulas_id")
            ->join("grupos as g", "g.id", "ga.grupos_id")
            ->join("aulas as a", "a.id", "ga.aulas_id")
            ->join("locales as l", "l.id", "a.locales_id")
            ->join("sedes as s", "s.id", "l.sedes_id")
            ->where([["fecha", date("Y-m-d")], ["ae.users_id", Auth::user()->id]])
            ->get();

        return Inertia::render('Asistencia/Estudiantes', ['data' => $response]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function aperturarAsistencia(Request $request)
    {
        // dd($request->grupo["id"]);
        $this->validate($request, [
            'grupo' => 'required',
        ], $messages = [
            // 'required' => '* El campo :attribute es obligatorio.',
            'grupo.required' => '* El campo grupo es obligatorio.',
        ]);
        $id = Auth::user()->id;

        $asistenciaEstudiante = AsistenciaEstudiante::where([["fecha", date("Y-m-d")], ["grupo_aulas_id", $request->grupo["id"]]])->first();

        if (isset($asistenciaEstudiante)) {
            $response["status"] = false;
            $response["message"] = "La asistencia del grupo seleccionado ya fue apuerturada anteriormente";
        } else {

            DB::beginTransaction();
            try {
                $data = new AsistenciaEstudiante;
                $data->fecha = date("Y-m-d");
                $data->grupo_aulas_id = $request->grupo["id"];
                $data->observacion = $request->observacion;
                $data->users_id = $id;
                $data->save();

                DB::commit();

                $response["status"] = true;
                $response["message"] = "Asistencia aperturada correctamente";
                $response["aperturados"] = DB::table("asistencia_estudiantes as ae")
                    ->select(
                        DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as denominacion"),
                        "ae.id",
                        "ae.estado",
                        "ga.id as grupo"
                    )
                    ->join("grupo_aulas as ga", "ga.id", "ae.grupo_aulas_id")
                    ->join("grupos as g", "g.id", "ga.grupos_id")
                    ->join("aulas as a", "a.id", "ga.aulas_id")
                    ->join("locales as l", "l.id", "a.locales_id")
                    ->join("sedes as s", "s.id", "l.sedes_id")
                    ->where([["fecha", date("Y-m-d")], ["ae.users_id", Auth::user()->id]])
                    ->get();
            } catch (\Exception $e) {
                DB::rollback();
                $response["status"] = false;
                $response["message"] = "Error al aperturar asistencia, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
                $response["e"] = $e->getMessage();
            }
        }
        return redirect()->back()
            ->with('response', $response);
    }

    public function buscarEstudiante(Request $request)
    {
        // dd(env("EXTERNALURLIMAGE"));
        $validarGrupo = Estudiante::join("matriculas as m", "m.estudiantes_id", "estudiantes.id")
            ->where([["m.grupo_aulas_id", $request->grupo], ["estudiantes.nro_documento", $request->dni]])
            ->first();

        $validarAsistencia = AsistenciaEstudiante::select("*")
            ->join("asistencia_estudiante_detalles as aed", "aed.asistencia_estudiantes_id", "asistencia_estudiantes.id")
            ->join("estudiantes as e", "e.id", "aed.estudiantes_id")
            ->where([["asistencia_estudiantes.fecha", date("Y-m-d")], ["e.nro_documento", $request->dni]])
            ->first();

        if (isset($validarGrupo)) {
            if (isset($validarAsistencia)) {
                $response["estudiante"] = Estudiante::select(
                    "estudiantes.id",
                    "estudiantes.nombres",
                    "estudiantes.foto",
                    DB::raw("CONCAT(estudiantes.paterno,' ',estudiantes.materno) as apellidos"),
                    DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as grupo"),
                    "ae.id",
                    DB::raw("CASE
                                WHEN aed.estado = 1 THEN 'PRESENTE'
                                WHEN aed.estado = 2 THEN 'TARDE'
                                ELSE 'FALTA'
                            END  as estado")
                )
                    ->join("asistencia_estudiante_detalles as aed", "aed.estudiantes_id", "estudiantes.id")
                    ->join("asistencia_estudiantes as ae", "ae.id", "aed.asistencia_estudiantes_id")
                    ->join("grupo_aulas as ga", "ga.id", "ae.grupo_aulas_id")
                    ->join("grupos as g", "g.id", "ga.grupos_id")
                    ->join("aulas as a", "a.id", "ga.aulas_id")
                    ->join("locales as l", "l.id", "a.locales_id")
                    ->join("sedes as s", "s.id", "l.sedes_id")
                    ->where([["fecha", date("Y-m-d")], ["estudiantes.nro_documento", $request->dni]])
                    ->first();
                $response["exist"] = $response["estudiante"]->estado;
            } else {
                $response["estudiante"] = Estudiante::select(
                    "estudiantes.id",
                    "estudiantes.nombres",
                    "estudiantes.foto",
                    DB::raw("CONCAT(estudiantes.paterno,' ',estudiantes.materno) as apellidos"),
                    DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as grupo")
                )
                    ->join("matriculas as m", "m.estudiantes_id", "estudiantes.id")
                    ->join("grupo_aulas as ga", "ga.id", "m.grupo_aulas_id")
                    ->join("grupos as g", "g.id", "ga.grupos_id")
                    ->join("aulas as a", "a.id", "ga.aulas_id")
                    ->join("locales as l", "l.id", "a.locales_id")
                    ->join("sedes as s", "s.id", "l.sedes_id")
                    ->where("estudiantes.nro_documento", $request->dni)
                    ->first();
                $response["exist"] = "";
            }

            $response["status"] = true;
        } else {
            $estudiante = Estudiante::where("nro_documento", $request->dni)->first();
            if (isset($estudiante)) {
                $response["message"] = "Error, el estudiante " . $estudiante->paterno . " " . $estudiante->materno . " " . $estudiante->nombres . " no pertenece al grupo " . $request->grupoActual . ".";
            } else {
                $response["message"] = "Error, El dni ingresado no existe";
            }
            $response["status"] = false;

            $response["estudiante"] = "";
        }
        $response["baseUrl"] = env("EXTERNALURLIMAGE");
        return $response;
    }
    public function guardarAsistencia(Request $request)
    {

        DB::beginTransaction();
        try {
            $data = new AsistenciaEstudianteDetalle;
            $data->estado = $request->estado;
            $data->asistencia_estudiantes_id = $request->asistencia;
            $data->estudiantes_id = $request->estudiante;
            $data->save();

            DB::commit();

            $response["status"] = true;
            $response["message"] = "Asistencia registrada correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al registrar asistencia, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
        }
        return $response;
    }
    public function listaAsistencia(Request $request)
    {

        $response["asistencia"] = AsistenciaEstudiante::with("asistencia_estudiante_detalle")
            ->where("asistencia_estudiantes.id", $request->asistencia)->first();

        return $response;
    }
    public function cerrarAsistencia(Request $request)
    {

        $asistencia = AsistenciaEstudiante::where("id", $request->asistencia)->first();
        $estudiantesAsistencia = AsistenciaEstudianteDetalle::select("estudiantes_id")
            ->where("asistencia_estudiantes_id", $request->asistencia)->get();

        $estudiantesFaltantes = Matricula::where("grupo_aulas_id", $asistencia->grupo_aulas_id)
            ->whereNotIn("estudiantes_id", $estudiantesAsistencia)->get();

        DB::beginTransaction();
        try {
            foreach ($estudiantesFaltantes as $key => $value) {
                $data = new AsistenciaEstudianteDetalle;
                $data->estado = '3';
                $data->asistencia_estudiantes_id = $request->asistencia;
                $data->estudiantes_id = $value->estudiantes_id;
                $data->save();
            }

            $asistencia->estado = '0';
            $asistencia->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Asistencia cerrada correctamente";
            $response["aperturados"] = DB::table("asistencia_estudiantes as ae")
                ->select(
                    DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as denominacion"),
                    "ae.id",
                    "ae.estado",
                    "ga.id as grupo"
                )
                ->join("grupo_aulas as ga", "ga.id", "ae.grupo_aulas_id")
                ->join("grupos as g", "g.id", "ga.grupos_id")
                ->join("aulas as a", "a.id", "ga.aulas_id")
                ->join("locales as l", "l.id", "a.locales_id")
                ->join("sedes as s", "s.id", "l.sedes_id")
                ->where([["fecha", date("Y-m-d")], ["ae.users_id", Auth::user()->id]])
                ->get();
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al cerrar asistencia, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
        }
        return $response;
    }
}
