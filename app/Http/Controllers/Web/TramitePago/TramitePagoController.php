<?php

namespace App\Http\Controllers\web\TramitePago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TPDocente;
use App\Models\TPDocumento;
use App\Models\TPTramite;
use App\Models\TPExpediente;
// use App\Models\TPTramiteDocumento;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class TramitePagoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::connection('mysql2')->table('docentes')->get();

        // dd($data);

        return Inertia::render('Web/Docente/Index');
    }

    //Login - Validacion
    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ], $messages = []);

        $validarDocente = DB::connection('mysql2')->table('docentes')->where([["email", $request->email], ["password", $request->password]])->first();
        $expedienteCurso = DB::connection('mysql2')->table('docentes as d')
            ->join("expedientes as e", "e.docente_id", "d.id")
            ->join("periodos as p", "e.periodo_id", "p.id")
            ->join("tramites as t", "t.expediente_id", "e.id")
            ->join("oficinas as o", "o.id", "t.oficina_id")
            ->select("d.*", "e.*", "p.id as p_id", "p.inicio_ciclo as inicio_ciclo", "p.fin_ciclo as fin_ciclo", "t.id as t_id", "t.estado as t_estado", "t.activo as t_activo", "t.mensaje", "o.id as o_id", "o.denominacion as o_denominacion")
            ->where([["email", $request->email], ["password", $request->password], ["t.activo", '1']])
            ->get();

        if (isset($validarDocente)) {
            $response["docente"] = $validarDocente;
            $response["datosExpediente"] = $expedienteCurso;
            $response["status"] = true;
            $response["message"] = "Datos validados correctamente";
        } else {
            $response["status"] = false;
            $response["message"] = "Uno o mas datos son incorrectos, intentelo nuevamante";
        }

        return $response;
    }


    public function subirArchivos(Request $request)
    {
        $docente = json_decode($request->docente);

        $prefijo = date('Y/m');

        DB::beginTransaction();
        try {
            if (isset($request->dni) && $request->dni != "existe") {
                $archivoDni = $request->dni;
                $nombreArchivoDNI = "dni_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDni, $nombreArchivoDNI);

                $documentoDni = TPDocumento::find($request->id_dni);
                $documentoDni->path = $prefijo . '/' . $nombreArchivoDNI;
                $documentoDni->save();
            }
            if (isset($request->suspencion) && $request->suspencion != "existe") {
                $archivoSuspencion = $request->suspencion;
                $nombreArchivoSuspencion = "suspencion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoSuspencion, $nombreArchivoSuspencion);

                $documentoSuspencion = TPDocumento::find($request->id_suspencion);
                $documentoSuspencion->path = $prefijo . '/' . $nombreArchivoSuspencion;
                $documentoSuspencion->save();
            }
            if (isset($request->osce) && $request->osce != "existe") {
                $archivoOSCE = $request->osce;
                $nombreArchivoOSCE = "osce_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoOSCE, $nombreArchivoOSCE);

                $documentoOSCE = TPDocumento::find($request->id_osce);
                $documentoOSCE->path = $prefijo . '/' . $nombreArchivoOSCE;
                $documentoOSCE->save();
            }
            if (isset($request->formato1) && $request->formato1 != "existe") {
                $archivoFormato1 = $request->formato1;
                $nombreArchivoFormato1 = "formato1_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoFormato1, $nombreArchivoFormato1);

                $documentoFormato1 = TPDocumento::find($request->id_formato1);
                $documentoFormato1->path = $prefijo . '/' . $nombreArchivoFormato1;
                $documentoFormato1->save();
            }
            if (isset($request->declaracion) && $request->declaracion != "existe") {
                $archivoDeclaracion = $request->declaracion;
                $nombreArchivoDeclaracion = "declaracion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDeclaracion, $nombreArchivoDeclaracion);

                $documentoDeclaracion = TPDocumento::find($request->id_declaracion);
                $documentoDeclaracion->path = $prefijo . '/' . $nombreArchivoDeclaracion;
                $documentoDeclaracion->save();
            }
            if (isset($request->informe) && $request->informe != "existe") {
                $archivoInforme = $request->informe;
                $nombreArchivoInforme = "informe_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoInforme, $nombreArchivoInforme);

                $documentoInforme = TPDocumento::find($request->id_informe);
                $documentoInforme->path = $prefijo . '/' . $nombreArchivoInforme;
                $documentoInforme->save();
            }
            if (isset($request->reciboHonorarios) && $request->reciboHonorarios != "existe") {
                $archivoReciboHonorarios = $request->reciboHonorarios;
                $nombreArchivoReciboHonorarios = "rh_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoReciboHonorarios, $nombreArchivoReciboHonorarios);

                $documentoReciboHonorarios = TPDocumento::find($request->id_reciboHonorarios);
                $documentoReciboHonorarios->path = $prefijo . '/' . $nombreArchivoReciboHonorarios;
                $documentoReciboHonorarios->save();
            }

            $tramitesAnteriores = TPTramite::where("expediente_id", $request->id_expediente)
                ->update(["activo" => '0']);

            $tramite = new TPTramite();
            $tramite->estado = '1';
            $tramite->oficina_id = '2';
            $tramite->mensaje = $request->mensaje;
            $tramite->expediente_id = $request->id_expediente;
            $tramite->save();

            DB::commit();
            $response["status"] = true;
            $response["datosExpediente"] = DB::connection('mysql2')->table('docentes as d')
                ->join("expedientes as e", "e.docente_id", "d.id")
                ->join("periodos as p", "e.periodo_id", "p.id")
                ->join("tramites as t", "t.expediente_id", "e.id")
                ->join("oficinas as o", "o.id", "t.oficina_id")
                ->select("d.*", "e.*", "p.id as p_id", "p.inicio_ciclo as inicio_ciclo", "p.fin_ciclo as fin_ciclo", "t.id as t_id", "t.estado as t_estado", "t.activo as t_activo", "t.mensaje", "o.id as o_id", "o.denominacion as o_denominacion")
                ->where([["email", $request->email], ["password", $request->password], ["t.activo", '1']])
                ->get();
            $response["message"] = "Formulario enviado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al enviar formulario";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function getDocumentosExpediente($id)
    {
        $response["documentos"] = DB::connection('mysql2')->table('expedientes as e')
            ->join("documentos as d", "d.expediente_id", "e.id")
            ->join("tipo_documentos as td", "td.id", "d.tipo_documento_id")
            ->join("tramites as t", "t.expediente_id", "d.expediente_id")
            ->select("d.id as d_id", "d.estado", "d.path", "d.activo", "d.tipo_documento_id", "td.*", "t.id as t_id", "t.mensaje as t_mensaje", "t.expediente_id", "t.oficina_id as oficina_id")
            ->where([["d.activo", "1"], ["t.expediente_id", $id]])
            ->get();

        $response["mensaje"] = DB::connection('mysql2')->table('expedientes as e')
            ->join("tramites as t", "t.expediente_id", "e.id")
            ->select("t.id as t_id", "t.mensaje as t_mensaje", "t.expediente_id", "t.oficina_id as oficina_id")
            ->where([["t.expediente_id", $id]])
            ->get();

        return $response;
    }

    // public function getMensaje($id)
    // {
    //     $response["mensaje"] = DB::connection('mysql2')->table('expedientes as e')
    //         ->join("tramites as t", "t.expediente_id", "e.id")
    //         // ->join("documentos as d", "d.expediente_id", "e.id")
    //         ->select("e.id as e_id", "t.id as t_id", "t.mensaje as mensaje")
    //         ->where([["e.id",$id]])
    //         ->get();

    //     return $response;
    // }

    public function getActualizarDocumentosExpediente($id)
    {
        $response["documentos"] = DB::connection('mysql2')->table('expedientes as e')
            ->join("documentos as d", "d.expediente_id", "e.id")
            ->join("tipo_documentos as td", "td.id", "d.tipo_documento_id")
            ->select("d.*", "td.*")
            ->where([["d.activo", "1"], ["d.expediente_id", $id]])
            ->get();

        return $response;
    }

    public function actualizarDocumentosExpediente(Request $request)
    {
        $docente = json_decode($request->docente);
        $id_expediente = json_decode($request->id_expediente);

        $prefijo = date('Y/m');

        DB::beginTransaction();
        try {
            if (isset($request->dni) && $request->dni != "existe") {
                $archivoDni = $request->dni;
                $nombreArchivoDNI = "dni_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDni, $nombreArchivoDNI);

                $docsDniAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 1]])
                    ->update(["activo" => '0']);
                $documentoDni = new TPDocumento();
                $documentoDni->path = $prefijo . '/' . $nombreArchivoDNI;
                $documentoDni->estado = '0';
                $documentoDni->tipo_documento_id = 1;
                $documentoDni->expediente_id = $request->id_expediente;
                $documentoDni->save();
            }
            if (isset($request->suspencion) && $request->suspencion != "existe") {
                $archivoSuspencion = $request->suspencion;
                $nombreArchivoSuspencion = "suspencion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoSuspencion, $nombreArchivoSuspencion);

                $docsSuspecionAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 2]])
                    ->update(["activo" => '0']);
                $documentoSuspencion = new TPDocumento();
                $documentoSuspencion->path = $prefijo . '/' . $nombreArchivoSuspencion;
                $documentoSuspencion->estado = '0';
                $documentoSuspencion->tipo_documento_id = 2;
                $documentoSuspencion->expediente_id = $id_expediente;
                $documentoSuspencion->save();
            }
            if (isset($request->reciboHonorarios) && $request->reciboHonorarios != "existe") {
                $archivoReciboHonorarios = $request->reciboHonorarios;
                $nombreArchivoReciboHonorarios = "rh_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoReciboHonorarios, $nombreArchivoReciboHonorarios);

                $docsReciboAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 3]])
                    ->update(["activo" => '0']);
                $documentoReciboHonorarios = new TPDocumento();
                $documentoReciboHonorarios->path = $prefijo . '/' . $nombreArchivoReciboHonorarios;
                $documentoReciboHonorarios->estado = '0';
                $documentoReciboHonorarios->tipo_documento_id = 3;
                $documentoReciboHonorarios->expediente_id = $id_expediente;
                $documentoReciboHonorarios->save();
            }
            if (isset($request->osce) && $request->osce != "existe") {
                $archivoOSCE = $request->osce;
                $nombreArchivoOSCE = "osce_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoOSCE, $nombreArchivoOSCE);

                $docsOSCEAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 4]])
                    ->update(["activo" => '0']);
                $documentoOSCE = new TPDocumento();
                $documentoOSCE->path = $prefijo . '/' . $nombreArchivoOSCE;
                $documentoOSCE->estado = '0';
                $documentoOSCE->tipo_documento_id = 4;
                $documentoOSCE->expediente_id = $id_expediente;
                $documentoOSCE->save();
            }
            if (isset($request->formato1) && $request->formato1 != "existe") {
                $archivoFormato1 = $request->formato1;
                $nombreArchivoFormato1 = "formato1_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoFormato1, $nombreArchivoFormato1);

                $docsFormato1Anteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 5]])
                    ->update(["activo" => '0']);
                $documentoFormato1 = new TPDocumento();
                $documentoFormato1->path = $prefijo . '/' . $nombreArchivoFormato1;
                $documentoFormato1->estado = '0';
                $documentoFormato1->tipo_documento_id = 5;
                $documentoFormato1->expediente_id = $id_expediente;
                $documentoFormato1->save();
            }
            if (isset($request->declaracion) && $request->declaracion != "existe") {
                $archivoDeclaracion = $request->declaracion;
                $nombreArchivoDeclaracion = "declaracion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDeclaracion, $nombreArchivoDeclaracion);

                $docsDeclaracioAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 6]])
                    ->update(["activo" => '0']);
                $documentoDeclaracion = new TPDocumento();
                $documentoDeclaracion->path = $prefijo . '/' . $nombreArchivoDeclaracion;
                $documentoDeclaracion->estado = '0';
                $documentoDeclaracion->tipo_documento_id = 6;
                $documentoDeclaracion->expediente_id = $id_expediente;
                $documentoDeclaracion->save();
            }
            if (isset($request->informe) && $request->informe != "existe") {
                $archivoInforme = $request->informe;
                $nombreArchivoInforme = "informe_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoInforme, $nombreArchivoInforme);

                $docsInformeAnteriores = TPDocumento::where([["expediente_id", $request->id_expediente], ["tipo_documento_id", 7]])
                    ->update(["activo" => '0']);
                $documentoInforme = new TPDocumento();
                $documentoInforme->path = $prefijo . '/' . $nombreArchivoInforme;
                $documentoInforme->estado = '0';
                $documentoInforme->tipo_documento_id = 7;
                $documentoInforme->expediente_id = $id_expediente;
                $documentoInforme->save();
            }
            $tramitesAnteriores = TPTramite::where("expediente_id", $request->id_expediente)
                ->update(["activo" => '0']);

            $tramite = new TPTramite();
            $tramite->estado = '1';
            $tramitemensaje = $request->mensaje;
            $tramite->expediente_id = $id_expediente;
            $tramite->oficina_id = '2';
            $tramite->save();

            DB::commit();
            $response["status"] = true;
            $response["datosExpediente"] = DB::connection('mysql2')->table('docentes as d')
                ->join("expedientes as e", "e.docente_id", "d.id")
                ->join("periodos as p", "e.periodo_id", "p.id")
                ->join("tramites as t", "t.expediente_id", "e.id")
                ->join("oficinas as o", "o.id", "t.oficina_id")
                ->select("d.*", "e.*", "p.id as p_id", "p.inicio_ciclo as inicio_ciclo", "p.fin_ciclo as fin_ciclo", "t.id as t_id", "t.estado as t_estado", "t.activo as t_activo", "t.mensaje", "o.id as o_id", "o.denominacion as o_denominacion")
                ->where([["email", $request->email], ["password", $request->password], ["t.activo", '1']])
                ->get();
            $response["message"] = "Formulario enviado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al enviar formulario";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function getDetallesHoras($id)
    {
        $horas = DB::connection('mysql2')->table('expediente_detalles as ed')
            ->join("expedientes as e", "e.id", "ed.expediente_id")
            ->join("horas_docente as hd", "hd.id", "ed.hora_docente_id")
            ->join("tipo_pagos as tp", "tp.id", "hd.tipo_pago_id")
            ->select("tp.denominacion as tp_denominacion", DB::raw("SUM(hd.cantidad) as hd_cantidad"))
            ->where([["ed.expediente_id", $id]])
            ->groupBy("tp.denominacion")
            ->get();
        
        $response["eDetalles"] = $horas;
            
        return $response;
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

    public function docs_Requeridos(Request $request)
    {
        $docente = json_decode($request->docente);
        $expedienteCursoDocs = DB::connection('mysql2')->table('docentes as d')
            ->join("expedientes as e", "e.docente_id", "d.id")
            ->join("periodos as p", "e.periodos_id", "p.id")
            ->join("tramite as t", "t.expedientes_id", "e.id")
            ->join("documentos as doc", "doc.expedientes_id", "e.id")
            ->join("tipo_documentos as tdoc", "tdoc.id", "doc.tipo_documento_id")
            ->join("oficina as o", "o.id", "t.oficina_id")
            ->select("d.*", "e.*", "p.id as p_id", "p.denominacion as p_denominacion", "t.id as t_id", "t.estado as t_estado", "t.mensaje", "o.id as o_id", "o.denominacion as o_denominacion", "doc.id as doc_id", "doc.estado as doc_estado", "doc.observacion as doc_observacion", "doc.path as doc_path", "tdoc.id as tdoc_id", "tdoc.denominacion as tdoc_denominacion")
            ->where([["email", $request->email], ["password", $request->password]])
            ->get();

        // dd($expedienteCursoDocs);

        $response["datosExpedienteDocs"] = $expedienteCursoDocs;

        return $response;
    }

    public function showDocument($id)
    {
        $documento = DB::connection('mysql2')->table('documentos')->where('id', $id)->first();
        // dd($documento->path);
        if (!empty($documento->path)) {
            $name = explode('.', explode('/', $documento->path)[2])[0];

            $files = Storage::disk('docentes')->download($documento->path, $name, [
                'Content-Disposition' => 'inline'
            ]);
            return $files;
        }
    }
}
