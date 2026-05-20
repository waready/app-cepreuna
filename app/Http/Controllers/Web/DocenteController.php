<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TPDocente;
use App\Models\TPDocumento;
use App\Models\TPTramite;
use App\Models\TPTramiteDocumento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;


class DocenteController extends Controller
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

        //     $docentes = DB::connection('mysql2')::table('docentes')->get();
        // return $docentes;
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ], $messages = []);

        $validarDocente = DB::connection('mysql2')->table('docentes')->where([["email", $request->email], ["password", $request->password]])->first();
        // $tramiteCurso = DB::connection('mysql2')->table('docentes as d')
        //     ->select("*")
        //     ->join("tramites as t", "t.docentes_id", "d.id")
        //     ->join("tramite_documentos as td", "td.tramites_id", "t.id")
        //     ->join("documentos as do", "do.id", "td.documentos_id")
        //     ->join("tipo_documentos as tdoc", "tdoc.id", "do.tipo_documentos_id")
        //     ->where([["email", $request->email], ["password", $request->password]])
        //     ->get();

        if (isset($validarDocente)) {
            $response["docente"] = $validarDocente;
            // $response["datosTramite"] = $tramiteCurso;
            $response["status"] = true;
            $response["message"] = "Datos validados correctamente";
        } else {
            $response["status"] = false;
            $response["message"] = "Uno o mas datos son incorrectos, intentelo nuevamante";
        }

        return $response;
    }

    public function getTipoDocumentos(Request $request)
    {
        $tipoDocumentos = DB::connection('mysql2')->table('tipo_documentos')->select("*")->get();

        return $tipoDocumentos;
    }

    public function subirArchivos(Request $request)
    {
        $docente = json_decode($request->docente);
        // $dni = json_decode($request->dni);
        // dd($request->dni);
        // dd($request->docente["dni"]);
        $this->validate($request, [
            'dni' => 'required',
            'suspencion' => 'required',
            'reciboHonorarios' => 'required',
            'osce' => 'required',
            'formato1' => 'required',
            'declaracion' => 'required',
            'informe' => 'required'
        ], $messages = [
            'required' => 'El campo es obligatorio'
        ]);

        $prefijo = date('Y/m');



        DB::beginTransaction();
        try {

            $tramite = new TPTramite();
            $tramite->docentes_id =  $docente->id;
            $tramite->save();



            if (isset($request->dni) && $request->dni != "existe") {
                $archivoDni = $request->dni;
                $nombreArchivoDNI = "dni_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDni, $nombreArchivoDNI);

                $documentoDni = new TPDocumento();
                $documentoDni->path = $prefijo . '/' . $nombreArchivoDNI;
                $documentoDni->estado = '1';
                $documentoDni->tipo_documentos_id = 1;
                $documentoDni->save();

                $tramiteDocumentoDni = new TPTramiteDocumento();
                $tramiteDocumentoDni->tramites_id = $tramite->id;
                $tramiteDocumentoDni->documentos_id = $documentoDni->id;
                $tramiteDocumentoDni->save();
            }
            if (isset($request->suspencion) && $request->suspencion != "existe") {
                $archivoSuspencion = $request->suspencion;
                $nombreArchivoSuspencion = "suspencion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoSuspencion, $nombreArchivoSuspencion);

                $documentoSuspencion = new TPDocumento();
                $documentoSuspencion->path = $prefijo . '/' . $nombreArchivoSuspencion;
                $documentoSuspencion->estado = '1';
                $documentoSuspencion->tipo_documentos_id = 2;
                $documentoSuspencion->save();

                $tramiteDocumentoSuspencion = new TPTramiteDocumento();
                $tramiteDocumentoSuspencion->tramites_id = $tramite->id;
                $tramiteDocumentoSuspencion->documentos_id = $documentoSuspencion->id;
                $tramiteDocumentoSuspencion->save();
            }
            if (isset($request->reciboHonorarios) && $request->reciboHonorarios != "existe") {
                $archivoReciboHonorarios = $request->reciboHonorarios;
                $nombreArchivoReciboHonorarios = "rh_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoReciboHonorarios, $nombreArchivoReciboHonorarios);

                $documentoReciboHonorarios = new TPDocumento();
                $documentoReciboHonorarios->path = $prefijo . '/' . $nombreArchivoReciboHonorarios;
                $documentoReciboHonorarios->estado = '1';
                $documentoReciboHonorarios->tipo_documentos_id = 3;
                $documentoReciboHonorarios->save();

                $tramiteDocumentoReciboHonorarios = new TPTramiteDocumento();
                $tramiteDocumentoReciboHonorarios->tramites_id = $tramite->id;
                $tramiteDocumentoReciboHonorarios->documentos_id = $documentoReciboHonorarios->id;
                $tramiteDocumentoReciboHonorarios->save();
            }
            if (isset($request->osce)&& $request->osce != "existe") {
                $archivoOSCE = $request->osce;
                $nombreArchivoOSCE = "osce_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoOSCE, $nombreArchivoOSCE);

                $documentoOSCE = new TPDocumento();
                $documentoOSCE->path = $prefijo . '/' . $nombreArchivoOSCE;
                $documentoOSCE->estado = '1';
                $documentoOSCE->tipo_documentos_id = 4;
                $documentoOSCE->save();

                $tramiteDocumentoOSCE = new TPTramiteDocumento();
                $tramiteDocumentoOSCE->tramites_id = $tramite->id;
                $tramiteDocumentoOSCE->documentos_id = $documentoOSCE->id;
                $tramiteDocumentoOSCE->save();
            }
            if (isset($request->formato1)&& $request->formato1 != "existe") {
                $archivoFormato1 = $request->formato1;
                $nombreArchivoFormato1 = "formato1_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoFormato1, $nombreArchivoFormato1);

                $documentoFormato1 = new TPDocumento();
                $documentoFormato1->path = $prefijo . '/' . $nombreArchivoFormato1;
                $documentoFormato1->estado = '1';
                $documentoFormato1->tipo_documentos_id = 5;
                $documentoFormato1->save();

                $tramiteDocumentoFormato1 = new TPTramiteDocumento();
                $tramiteDocumentoFormato1->tramites_id = $tramite->id;
                $tramiteDocumentoFormato1->documentos_id = $documentoFormato1->id;
                $tramiteDocumentoFormato1->save();
            }
            if (isset($request->declaracion)&& $request->declaracion != "existe") {
                $archivoDeclaracion = $request->declaracion;
                $nombreArchivoDeclaracion = "declaracion_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoDeclaracion, $nombreArchivoDeclaracion);

                $documentoDeclaracion = new TPDocumento();
                $documentoDeclaracion->path = $prefijo . '/' . $nombreArchivoDeclaracion;
                $documentoDeclaracion->estado = '1';
                $documentoDeclaracion->tipo_documentos_id = 6;
                $documentoDeclaracion->save();

                $tramiteDocumentoDeclaracion = new TPTramiteDocumento();
                $tramiteDocumentoDeclaracion->tramites_id = $tramite->id;
                $tramiteDocumentoDeclaracion->documentos_id = $documentoDeclaracion->id;
                $tramiteDocumentoDeclaracion->save();
            }
            if (isset($request->informe)&& $request->informe != "existe") {
                $archivoInforme = $request->informe;
                $nombreArchivoInforme = "informe_" . $docente->dni . '_' . $docente->nombres . '_' . $docente->paterno . '_' . $docente->materno . time() . ".pdf";

                Storage::disk('docentes')->putFileAs($prefijo, $archivoInforme, $nombreArchivoInforme);

                $documentoInforme = new TPDocumento();
                $documentoInforme->path = $prefijo . '/' . $nombreArchivoInforme;
                $documentoInforme->estado = '1';
                $documentoInforme->tipo_documentos_id = 7;
                $documentoInforme->save();

                $tramiteDocumentoInforme = new TPTramiteDocumento();
                $tramiteDocumentoInforme->tramites_id = $tramite->id;
                $tramiteDocumentoInforme->documentos_id = $documentoInforme->id;
                $tramiteDocumentoInforme->save();
            }

            $tramiteCurso = DB::connection('mysql2')->table('docentes as d')
            ->select("*")
            ->join("tramites as t", "t.docentes_id", "d.id")
            ->join("tramite_documentos as td", "td.tramites_id", "t.id")
            ->join("documentos as do", "do.id", "td.documentos_id")
            ->join("tipo_documentos as tdoc", "tdoc.id", "do.tipo_documentos_id")
            ->where([["email", $docente->email], ["password", $docente->password]])
            ->get();

            DB::commit();
            $response["status"] = true;
            $response["datosTramite"] = $tramiteCurso;
            $response["message"] = "Formulario enviado correctamente";

        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al enviar formulario";
            $response["e"] = $e->getMessage();
        }

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

}
