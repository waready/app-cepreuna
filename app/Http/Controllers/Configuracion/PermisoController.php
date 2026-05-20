<?php

namespace App\Http\Controllers\Configuracion;

use App\DataTable\EloquentVueTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermisoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Inertia::render('Configuracion/Permisos');
    }

    public function tabla(Request $request)
    {
        $table = new EloquentVueTables;
        $lazyEvent = json_decode($request->lazyEvent);
        $rows = $lazyEvent->rows;

        $structure = array();
        // $structure = array(
        //     'user' => array(
        //         'table' => 'users',
        //         'key' => 'user_id'
        //     )
        // );

        $data = $table->get(
            new Permission(),
            [
                'permissions.id',
                'permissions.name',
            ],
            [],
            $structure,
            $request->lazyEvent
        );
        $data = $data->where('permissions.guard_name', 'sanctum');
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
            'name' => 'required|unique:permissions,name',
        ], $messages = [
            // 'required' => '* El campo :attribute es obligatorio.',
            'name.required' => '* El campo nombre es obligatorio.',
        ]);

        DB::beginTransaction();
        try {
            $permiso = new Permission;
            $permiso->name = $request->name;
            $permiso->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Permiso agregado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al agregar permiso, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
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
            "name.required" => "* El campo nombre es obligatorio.",
        ]);

        DB::beginTransaction();
        try {
            $permiso = Permission::find($id);
            $permiso->name = $request->name;
            $permiso->save();

            DB::commit();
            $response["status"] = true;
            $response["message"] = "Permiso actualizado correctamente";
        } catch (\Exception $e) {
            DB::rollback();
            $response["status"] = false;
            $response["message"] = "Error al actualizar permiso, intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema";
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

            Permission::find($id)->delete();

            DB::commit();
            $response["message"] = 'Permiso eliminado correctamente.';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] = 'Error al eliminar permiso,intentelo de nuevo si el problema persiste comuniquese con el administrador del sistema';
            $response["status"] = false;
            $response["e"] = $e->getMessage();
        }
        return redirect()->back()
            ->with('response', $response);
    }
}
