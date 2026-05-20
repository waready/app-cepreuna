<?php

namespace App\Http\Controllers\Web\DocenteApto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DocenteAptoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Web/Docente/DocenteApto');
    }

    public function buscar(Request $request)
    {
        $this->validate($request, [
            'dni' => 'required',
        ], $messages = [            
            'required' => '* El campo es obligatorio.',
        ]);

        $docente = DB::table('docente_aptos as da')
            ->join("docentes as d", "d.id", "da.docentes_id")
            ->select("da.enviar_acceso as acceso","d.nombres","d.paterno","d.materno","d.nro_documento","d.email")
            ->where([["d.nro_documento", $request->dni]])
            ->first();

        if(isset($docente)){
            $response["docente"] = $docente;
            $response["status"] = true;
        } else {
            $response["status"] = false;
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
