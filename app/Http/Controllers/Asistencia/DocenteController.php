<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AsistenciaDocente;
use App\Models\CalificacionDocente;
use App\Models\Horario;
use App\Models\CargaAcademica;
use App\Models\Sesiones;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class DocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response["aperturados"] = DB::table("asistencia_estudiantes as ae")
            ->select(
                DB::raw("CONCAT(g.denominacion,' (',s.denominacion,')') as denominacion"),
                "ae.id",
                "ae.estado"
            )
            ->join("grupo_aulas as ga", "ga.id", "ae.grupo_aulas_id")
            ->join("grupos as g", "g.id", "ga.grupos_id")
            ->join("aulas as a", "a.id", "ga.aulas_id")
            ->join("locales as l", "l.id", "a.locales_id")
            ->join("sedes as s", "s.id", "l.sedes_id")
            ->where("fecha", date("Y-m-d"))
            ->get();

        return Inertia::render('Asistencia/Docentes', ['data' => $response]);
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
        // dd($request->file('imagen'));
        $date = date("Y-m-d");
        $rules = $request->validate([
            'status_tema' => 'required',
            'cantidadEstudiantes' => 'required',
            'horasAsistidas' => 'required',
            // 'imagen' => 'image',
            // 'fecha_tema' => 'required_if:status_tema,2',
            'tema' => 'required_if:status_tema,2',

        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
            'required_if' => '* El campo :attribute es obligatorio.',
            'imagen.image' => '* Ingrese una imagen valida'
        ]);

        $id = Auth::user()->id;
        $fecha = new \DateTime($date);
        $semana = $fecha->format("N");
        $horario = Horario::select("horarios.*", "ph.hora_inicio", "ph.hora_fin")
            ->where([
                ["carga_academicas_id", $request->carga],
                ["dia", $semana]
            ])
            ->join("plantilla_horarios as ph", "ph.id", "horarios.plantilla_horarios_id")
            ->orderBy("hora_inicio", "asc")
            ->get();
        // dd(reset($horario));
        // var_dump($horario);
        $cantidadHoras = count($horario);
        if(isset($request->imagen)){
            // $path_imagen = $this->save_file($request->file('imagen'), $request->file('imagen')->getClientOriginalExtension());
        }
        DB::beginTransaction();
        try {

            $data = new AsistenciaDocente;
            $data->estado = $request->estado;
            $data->fecha = $date;
            $data->hora_inicio = $horario[0]->hora_inicio;
            $data->hora_fin = $horario[$cantidadHoras - 1]->hora_fin;
            $data->cantidad_horas = $cantidadHoras;
            $data->horas_pago = $request->horasAsistidas;
            $data->docentes_id = $request->docente;
            $data->carga_academicas_id = $request->carga;
            if($request->status_tema==1){
                $data->sesiones_id = $request->sesion;
            }
            if($request->status_tema==2&&$request->estado!=3){
                $sesion = new Sesiones;
                $sesion->tema = $request->tema;
                $sesion->fecha = $date;
                $sesion->carga_academicas_id = $request->carga;
                $sesion->save();
                $data->sesiones_id = $sesion->id;
            }
            $data->cantidad_estudiantes = $request->cantidadEstudiantes;
            if(isset($request->imagen)){

                $data->path_imagen = $path_imagen;
            }
            $data->observacion = $request->observacion;
            $data->users_id = $id;
            $data->save();

            if ($request->estado == 1 || $request->estado == 2) {
                $calificacion = new CalificacionDocente;
                $calificacion->participantes = 0;
                $calificacion->docentes_id = $request->docente;
                $calificacion->carga_academicas_id = $request->carga;
                $calificacion->asistencia_docentes_id = $data->id;
                $calificacion->save();
            }


            DB::commit();
            $response["message"] = 'Asistencia Validada';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al validar, intentelo nuevamante.';
            $response["status"] =  false;
        }


        return redirect()->back()
            ->with('response', $response);
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
}
