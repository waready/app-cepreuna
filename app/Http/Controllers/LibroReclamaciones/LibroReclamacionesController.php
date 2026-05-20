<?php

namespace App\Http\Controllers\LibroReclamaciones;

use App\DataTable\EloquentVueTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReclamoLR;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LibroReclamacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('LibroReclamaciones/LibroReclamaciones');
    }

    public function tabla(Request $request)
    {
        $table = new EloquentVueTables;
        $lazyEvent = json_decode($request->lazyEvent);
        $rows = $lazyEvent->rows;
        $structure = array();

        $user = Auth::user();
        if(isset($user->docentes_id)){
            $data = $table->get(
                new ReclamoLR(),
                [
                    'reclamos.descripcion',
                    'reclamos.fecha_ingreso',
                    'reclamos.estado',
                    'reclamos.respuesta',
                    'reclamos.fecha_respuesta',
                ],
                [],
                $structure,
                $request->lazyEvent
            );
            $data = $data->join("docentes as d", "d.nro_documento", "reclamos.dni");
            $data = $data->where("d.id",$user->docentes_id);
            $data = $data->orderBy('fecha_ingreso', 'desc');                $reclamos = $data->paginate($rows);
            // dd($reclamos);
            return response($reclamos);
        }
        else{
            $data = $table->get(
                new ReclamoLR(),
                [
                    'reclamos.descripcion',
                    'reclamos.fecha_ingreso',
                    'reclamos.estado',
                    'reclamos.respuesta',
                    'reclamos.fecha_respuesta',
                ],
                [],
                $structure,
                $request->lazyEvent
            );
            $data = $data->where("reclamos.dni",$user->nro_documento);
            $data = $data->orderBy('fecha_ingreso', 'desc');                $reclamos = $data->paginate($rows);
            // dd($reclamos);
            return response($reclamos);
        }
        
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
        // dd($request);
        // dd($request->evidencia["file"]->getPathname());

        $this->validate($request, [
            'user_domicilio' => 'required',
            'user_correo' => 'required',
            'user_celular' => 'required',
            'descripcion' => 'required',
            'tipo_reclamacion' => 'required',
            'detalle' => 'required',
            'pedido' => 'required',
            'evidencia' => 'required',
        ], $messages = [
            'user_domicilio.required' => '* El campo es obligatorio.',
            'user_correo.required' => '* El campo es obligatorio.',
            'user_celular.required' => '* El campo es obligatorio.',
            'descripcion.required' => '* El campo es obligatorio.',
            'tipo_reclamacion.required' => '* El campo es obligatorio.',
            'detalle.required' => '* El campo es obligatorio.',
            'pedido.required' => '* El campo es obligatorio.',
            'evidencia' => '* El campo es obligatorio.',
        ]);
        // dd($request->user_name);
        DB::beginTransaction();
        try{
            $reclamo = new ReclamoLR;
            $reclamo->nombres = $request-> user_name;
            $reclamo->paterno = $request-> user_paterno;
            $reclamo->materno = $request-> user_materno;
            $reclamo->dni = $request-> user_dni;
            $reclamo->domicilio = $request-> user_domicilio;
            $reclamo->correo = $request-> user_correo;
            $reclamo->domicilio = $request-> user_domicilio;
            $reclamo->fecha_ingreso =  $request-> fecha;
            $reclamo->celular = $request-> user_celular;
            if(isset($request-> apoderado)){
                $reclamo->apoderado = $request-> apoderado;
            }
            $reclamo->descripcion = $request-> descripcion;
            if($request->tipo_reclamacion["name"] == "Queja"){
                $reclamo->tipo_reclamacion = '0';
            } else {
                $reclamo->tipo_reclamacion = '1';
            }

            $reclamo->detalle = $request-> detalle;
            $reclamo->pedido = $request-> pedido;
            $reclamo->estado = '0';

            $prefijo = date('Y/m');

            if(isset($request->evidencia)){
                $archivoEvidencia = $request->evidencia["file"]->getRealPath();
                $nombreArchivoEvidencia = "evidencia_reclamo_" . $request->user_dni . time() . ".jpg";

                Storage::disk('evidenciasReclamos')->putFileAs($prefijo, $archivoEvidencia, $nombreArchivoEvidencia);

                $reclamo->path = $prefijo . '/' . $nombreArchivoEvidencia;
            }
            $reclamo->save();
            DB::commit();
            $response["status"]=true;
            $response["message"] = "Reclamo enviado correctamente";

        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al agregar evidencia";
            $response["e"] = $e->getMessage();
        }
        return redirect()->back()
        ->with('response', $response);
    }

    public function showEvidencia($id)
    {
        $reclamo = DB::connection('mysql')->table('reclamos')->where('id', $id)->first();
        // dd($reclamo->path);
        if(!empty($reclamo->path)){
            $name = explode('.', explode('/', $reclamo->path)[2])[0];

            $files = Storage::disk('evidenciasReclamos')->download($reclamo->path, $name, [
                'Content-Disposition' => 'inline'
            ]);
            return $files;
        }
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
