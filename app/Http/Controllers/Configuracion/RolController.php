<?php

namespace App\Http\Controllers\Configuracion;

use App\DataTable\EloquentVueTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $permisos = Permission::get();
        return Inertia::render('Configuracion/Roles', ['permisos' => $permisos]);
    }

    public function tabla(Request $request)
    {
        $table = new EloquentVueTables;
        $lazyEvent = json_decode($request->lazyEvent);
        $rows = $lazyEvent->rows;

        $structure = array();

        $data = $table->get(
            new Role,
            [
                'roles.id',
                'roles.name',
            ],
            ['permissions'],
            $structure,
            $request->lazyEvent
        );
        $data = $data->where('roles.guard_name', 'sanctum');
        $data = $data->orderBy('id', 'asc');
        $roles = $data->paginate($rows);
        return response($roles);
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
            'name' => 'required|unique:permissions,name',
        ], $messages = [
            'name.required' => '* El campo nombre es obligatorio.',
        ]);

        DB::beginTransaction();
        try {

            $role = new Role;
            $role->name = $request->name;
            $role->save();

            $role->syncPermissions($request->permisos);

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Rol agregado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al agregar rol, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
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
            "name" => "required|unique:permissions,name,$id",
        ], $messages = [
            'name.required' => '* El campo nombre es obligatorio.',
        ]);

        DB::beginTransaction();
        try {

            $role = Role::find($id);
            $role->name = $request->name;
            $role->save();

            $role->syncPermissions($request->permisos);

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Rol actualizado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al actualizar rol, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
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

            $role = Role::find($id);
            $role->syncPermissions([]);
            $role->delete();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Rol eliminado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al eliminar rol, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
            $response["e"] = $e->getMessage();
        }

        return redirect()->back()
            ->with('response', $response);
    }
}
