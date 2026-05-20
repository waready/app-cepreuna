<?php

namespace App\Http\Controllers\Configuracion;

use App\DataTable\EloquentVueTables;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        if (Auth::user()->hasRole('Super Admin')) {
            $roles = Role::where('guard_name', 'sanctum')->get();
            // dd($plantas);
        } else {
            $roles = Role::where([['name', '!=', 'Super Admin'], ['guard_name', 'sanctum']])->get();
        }

        $response['roles'] = $roles;

        return Inertia::render('Configuracion/Users', ['data' => $response]);
    }

    public function tabla(Request $request)
    {

        $table = new EloquentVueTables;
        $lazyEvent = json_decode($request->lazyEvent);
        $rows = $lazyEvent->rows;

        // $structure = array();
        $structure = array(
            'model_has_role' => array(
                'table' => 'model_has_roles',
                'key' => 'model_id'
            ),
            'role' => array(
                'table' => 'roles',
                'key' => 'roles_id'
            ),
        );

        $data = $table->get(
            new User(),
            [
                'users.id',
                'users.name',
                'users.dni',
                'users.email',
                'users.profile_photo_path',
            ],
            ['model_has_role'],
            $structure,
            $request->lazyEvent
        );
        // dd(Auth::user()->getRoleNames());

        // if (!Auth::user()->hasRole('Super Admin')) {
        //     $data = $data->leftjoin('model_has_roles as mr', 'mr.model_id', 'users.id')
        //         ->leftjoin('roles as r', 'r.id', 'mr.role_id')
        //         ->where([['r.name', '!=', 'Super Admin'], ['r.guard_name', 'sanctum']]);
        // } else {
        //     $data = $data->leftjoin('model_has_roles as mr', 'mr.model_id', 'users.id')
        //         ->leftjoin('roles as r', 'r.id', 'mr.role_id')
        //         ->where([['r.guard_name', 'sanctum']]);
        // }
        if (!Auth::user()->hasRole('Super Admin')) {
            $data = $data->leftjoin('model_has_roles as mr', function ($join) {
                $join->on('mr.model_id', 'users.id')
                    ->where("mr.model_type", "App\Models\User");
                // ->orWhere("mr.model_type", 'App\\Models\\Estudiante')
                // ->orWhere("mr.model_type", 'App\\Models\\Docente');
            })
                ->leftjoin('roles as r', function ($join) {
                    $join->on('r.id', 'mr.role_id')
                        ->where([['r.name', '!=', 'Super Admin'], ['r.guard_name', 'sanctum']]);
                });
        } else {
            $data = $data->leftjoin("model_has_roles as mr", function ($join) {
                $join->on("mr.model_id", "users.id")
                    ->where("mr.model_type", "App\Models\User");
            })
                ->leftjoin("roles as r", function ($join) {
                    $join->on("r.id", "mr.role_id")
                        ->where("r.guard_name", "sanctum");
                });
        }
        $data = $data->orderBy('id', 'asc');
        $permisos = $data->paginate($rows);
        return response($permisos);
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
        $this->validate($request, [
            'name' => 'required',
            'dni' => 'required',
            'rol' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required',
        ], $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'dni.required' => 'El campo dni es obligatorio.',
            'email.required' => 'El campo email es obligatorio.',
            'password.required' => 'El campo contraseña es obligatorio.',
        ]);

        DB::beginTransaction();
        try {

            $usuario = new User();
            $usuario->name = $request->name;
            $usuario->dni = $request->dni;
            $usuario->email = $request->email;
            $usuario->password = Hash::make($request->password);
            $usuario->save();

            $usuario->syncRoles($request->rol['name']);

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Usuario agregado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al agregar usuario, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
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
        $this->validate($request, [
            "name" => "required",
            "dni" => "required",
            "rol" => "required",
            "email" => "required|unique:users,email,$id",
        ], $messages = [
            "name.required" => "El campo nombre es obligatorio.",
            "dni.required" => "El campo dni es obligatorio.",
            "email.required" => "El campo email es obligatorio.",
        ]);

        DB::beginTransaction();
        try {

            $usuario = User::find($id);
            $usuario->name = $request->name;
            $usuario->dni = $request->dni;
            $usuario->email = $request->email;
            if (!empty($request->password)) {
                $usuario->password = Hash::make($request->password);
            }
            $usuario->save();

            $usuario->syncRoles($request->rol['name']);

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Usuario actualizado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al actualizar usuario, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
        }

        return redirect()->back()
            ->with('response', $response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {

            $usuario = User::find($id);
            $usuario->syncRoles([]);
            $usuario->delete();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Usuario eliminado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al eliminar usuario, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
        }

        return redirect()->back()
            ->with('response', $response);
    }
}
