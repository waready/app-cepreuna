<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NosotrosDirectivo;
use App\Models\NosotrosDescripcion;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NosotrosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getDirectivos()
    {
        $response["directivos"] = NosotrosDirectivo::where([["activo",'1']])
            ->orderBy("tipo")
            ->get();

        return $response;
    }

    public function getMisionVision()
    {
        $response["misionvision"] = NosotrosDescripcion::where([["activo",'1']])
            ->whereIn('nosotros_tipo_dato_id', [1, 2])
            ->orderBy("nosotros_tipo_dato_id")
            ->get();

        return $response;
    }

    public function getObjetivos()
    {
        $response["objetivos"] = NosotrosDescripcion::where([["activo",'1']])
            ->whereIn('nosotros_tipo_dato_id', [3, 4])
            ->orderBy("nosotros_tipo_dato_id")
            ->get();

        return $response;
    }

    
    public function getHistoria()
    {
        $response["historia"] = NosotrosDescripcion::where([["activo",'1'],["nosotros_tipo_dato_id", 5]])
            ->orderByDesc("created_at")
            ->get()->first();

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
