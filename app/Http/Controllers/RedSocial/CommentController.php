<?php

namespace App\Http\Controllers\RedSocial;

use Inertia\Inertia;
use App\Models\Comentario;
use App\Models\Publicacion;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    public function crearComentario(Request $request)
    {
        $this->validate($request, [
            'texto' => 'required',
        ], $messages = [
            'texto.required' => '* El contenido es obligatorio.'
        ]);
        $idRol = Auth::user()->roles[0]->id;
        $idUser = Auth::user()->id;
        // $usuario = json_decode($request->usuario);

        DB::beginTransaction();
        try {
            $comentario = new Comentario();
            $comentario->descripcion = $request->texto;
            $comentario->estado = '1';
            $comentario->user_id = $idUser;
            $comentario->role_id = $idRol;
            $comentario->publicacion_id = $request->id;
            if($request->comentario != null){
                $comentario->comentario_id = $request->comentario;
                $comentario->tipo = '2';
            } else {
                $comentario->tipo = '1';
            }
            $comentario->save();

            $publicacion = Publicacion::find($request->id);

            $notificacion = new Notificacion();
            $notificacion->estado = '0';
            $notificacion->tipo = '2';
            $notificacion->role_id = $publicacion->role_id;
            $notificacion->publicacion_id = $request->id;
            $notificacion->comentario_id = $comentario->id;
        // descripcion = ???
            $notificacion->descripcion = "Ha realizado un comentario.";
            $notificacion->user_id = $publicacion->user_id;
            $notificacion->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Comentario realizado correctamente.";
        } catch (\Exception $e){
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al realizar comentario.";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function getComentarios($id)
    {
        $response["comentarios"] = Comentario::with(["rol"])->where([["tipo", '1'],["publicacion_id",$id]])->orderBy("created_at","DESC")->get();
        // dd($id);
        // $response["comentarios"] = DB::connection('mysql')->table('comentarios as c')
        //     ->join("roles as r", "r.id","c.role_id")
        //     ->join("estudiantes as e", "e.id", "c.user_id")
        //     ->select("e.nombres","e.paterno","e.materno","e.foto","c.descripcion","c.estado","c.tipo","c.created_at","c.id","c.comentario_id","c.publicacion_id","c.user_id","c.role_id")
        //     ->where([["r.name",'Estudiante'],["c.tipo", '1'],["c.publicacion_id",$id]])
        //     ->orderBy("c.created_at","DESC")
        //     ->get();
        // // dd($response["publicaciones"]);
        return $response;
    }

    public function getSubComentarios($id)
    {
        // dd($request);
        $response["subcomentarios"] = Comentario::with(["rol"])->where([["tipo", '2'],["comentario_id",$id]])->orderBy("created_at","DESC")->get();

        // dd($response["subcomentarios"]);
        return $response;
    }

    public function countComentarios($id)
    {
        $response["countComentarios"] = Comentario::with(["rol"])->where([["publicacion_id",$id]])->count();

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