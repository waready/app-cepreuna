<?php

namespace App\Http\Controllers\RedSocial;

use Image;
use App\Models\Like;
use Inertia\Inertia;
use Illuminate\Http\File;
use App\Models\Publicacion;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class PublicationController extends Controller
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

    public function crearPublicacion(Request $request)
    {
        // dd($request);
        $this->validate($request, [
            'texto' => 'required',
        ], $messages = [
            'texto.required' => '* El contenido es obligatorio.'
        ]);

        // $usuario = json_decode($request->usuario);
        $idRol = Auth::user()->roles[0]->id;
        $idUser = Auth::user()->id;
        // dd($usuario);
        $prefijo = date('Y/m');
        // $prefijo = date('Y');
        // dd($request->imgt);

        DB::beginTransaction();
        try {
            $publicacion = new Publicacion();

            $publicacion->estado = '1';
            if($request->tipo=='2'){
                $publicacion->tipo = '2';
            }else{
                $publicacion->tipo = '1';
            }
            $publicacion->role_id = $idRol;
            $publicacion->descripcion = $request->texto;
            $publicacion->like = 0;
            // Archivo
            if(isset($request->archivo)){
                // dd($request->archivoname);
                // $prefijoarchivo = ""
                $archivoPublicacion = $request->archivo;
                $nombreArchivoPublicacion = 'archivos/'. time().$request->archivoname;

                Storage::disk('publicaciones')->putFileAs($prefijo, $archivoPublicacion, $nombreArchivoPublicacion);

                $publicacion->archivo = $prefijo .'/' . $nombreArchivoPublicacion;
            }
            if(isset($request->imagen) && $request->imagen != "existe"){
                $imagenPublicacion = $request->imagen;
                $nombreImagenPublicacion = "imagenesPub/" . time() . ".jpg";

                Storage::disk('publicaciones')->putFileAs($prefijo, $imagenPublicacion, $nombreImagenPublicacion);

                $publicacion->imagen_pub =$prefijo .'/' . $nombreImagenPublicacion;

                $image = Image::make($imagenPublicacion);
                $image->resize(144,144);
                $nombreImgTumb = "imagenesTumb/" . time() . ".jpg";
                Storage::disk('publicaciones')->put($prefijo.'/'.$nombreImgTumb, $image->encode());
                $publicacion->imagen_tumb =$prefijo .'/' . $nombreImgTumb;
            }
            $publicacion->user_id = $idUser;
            $publicacion->save();

        //     $notificacion = new Notificacion();
        //     $notificacion->estado = '0';
        //     $notificacion->tipo = '1';
        //     $notificacion->role_id = $usuario->roles[0]->id;
        //     $notificacion->publicacion_id = $publicacion->id;
        // // descripcion = ????
        //     $notificacion->descripcion = "Ha añadido una publicación.";
        //     $notificacion->user_id = $usuario->id;
        //     $notificacion->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Publicación realizada correctamente";
        } catch (\Exception $e){
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al realizar publicación.";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function getPublicaciones(Request $request)
    {
        // $publicaciones = DB::table('publicaciones as p')
        //     ->join("roles as r", "r.id","p.role_id")
        //     ->join("estudiantes as e", "e.id", "p.user_id")
        //     ->select("e.nombres","e.paterno","e.materno","e.foto","p.descripcion","p.like","p.estado","p.tipo","p.archivo","p.imagen_tumb","p.imagen_pub","p.created_at","p.id");
        $publicaciones = Publicacion::with(["rol"]);

        if($request->tipo == '2'){
            $publicaciones = $publicaciones->where("tipo","2");
        }else{
            $publicaciones = $publicaciones->where("tipo","1");
        }




        // $publicaciones = $publicaciones->
        $publicaciones = $publicaciones->where([["estado",'1']])->orderBy("created_at","DESC")->paginate(5);
        // dd($response["publicaciones"]);
        return $publicaciones;
    }

    public function getLike($id)
    {
        $idUser = Auth::user()->id;
        $idRole = Auth::user()->roles[0]->id;

        $like = DB::connection('mysql')->table("likes as l")
            ->select("*")
            ->where([["user_id",$idUser],["role_id",$idRole],["publicacion_id",$id],["estado",'1']])
            ->get();

        if(count($like)>0){
            $response["likestatus"] = 1;
        } else{
            $response["likestatus"] = 0;
        }

        return $response;
    }

    public function ocultar($id)
    {
        DB::beginTransaction();
        try{
            $publicacion = Publicacion::find($id);
            // dd($publicacion);
            $publicacion->estado = '0';
            $publicacion->save();

            DB::commit();
            
            $response["status"] = true;
            $response["message"] = "Publicación oculta correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al ocultar publicación.";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function like($id)
    {
        $usuario = Auth::user();

        DB::beginTransaction();
        try{
            $like = new Like();
            $like->user_id = $usuario->id;
            $like->role_id = $usuario->roles[0]->id;
            $like->publicacion_id = $id;
            $like->estado = '1';
            $like->save();

            $publicacion = Publicacion::find($id);
            $publicacion->like += 1;
            $publicacion->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Like enviado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al enviar formulario";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function dislike($id)
    {
        $idUser = Auth::user()->id;
        $idRole = Auth::user()->roles[0]->id;

        DB::beginTransaction();
        try{
            $like = Like::where("publicacion_id",$id)
                    ->where("user_id",$idUser)
                    ->where("role_id",$idRole)
                    ->update(["estado" => '0']);

            $publicacion = Publicacion::find($id);
            $publicacion->like -= 1;
            $publicacion->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Like enviado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al enviar formulario";
            $response["e"] = $e->getMessage();
        }

        return $response;
    }

    public function verPublicacion($id){
        $idP = Crypt::decryptString($id);
        // dd($idP);
        // $publicaciones = DB::table('publicaciones as p')
        //     ->join("roles as r", "r.id","p.role_id")
        //     ->join("estudiantes as e", "e.id", "p.user_id")
        //     ->select("e.nombres","e.paterno","e.materno","e.foto","p.descripcion","p.like","p.estado","p.tipo","p.archivo","p.imagen_tumb","p.imagen_pub","p.created_at","p.id")
        //     ->where("p.id",$idP)->first();
        $publicaciones = Publicacion::with(["rol"])->find($idP);
        $response["publicacion"] = $publicaciones;
        return Inertia::render('RedSocial/VerPublicacion',["data"=>$response]);
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
