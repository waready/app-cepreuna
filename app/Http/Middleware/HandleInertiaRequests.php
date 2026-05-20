<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;
use App\Models\DocenteApto;
use App\Models\Estudiante;
use App\Models\User;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function permissions()
    {
        $permissions = [];
        if (auth()->user()->hasRole('Super Admin')) {
            foreach (Permission::get() as $key => $value) {
                array_push($permissions, $value->name);
            }
        } else {
            foreach (Auth::user()->getAllPermissions() as $key => $value) {
                array_push($permissions, $value->name);
            }
        }

        return $permissions;
    }
    public function share(Request $request)
    {
        $data = [];
        if (Auth::guard('docente')->check()) {

            $usuario = DocenteApto::with("docente")->find(Auth::guard('docente')->user()->id);
            $data = new \stdClass;
            $data->nombres = $usuario->docente->nombres;
            $data->paterno = $usuario->docente->paterno;
            $data->materno = $usuario->docente->materno;
            $data->dni = $usuario->docente->nro_documento;
            $data->email = $usuario->usuario;
            $data->profile_photo_path = env("EXTERNALURLIMAGE") . '/storage/fotos/' . $usuario->docente->foto;
            $data->profile_photo_url = "https://ui-avatars.com/api/?name=" . str_replace(" ", "+", $usuario->docente->nombres) . "&color=7F9CF5&background=EBF4FF";
            $data->url = env("EXTERNALURLIMAGE");
        } elseif (Auth::guard('estudiante')->check()) {
            $usuario = Estudiante::find(Auth::guard('estudiante')->user()->id);
            $data = new \stdClass;
            $data->nombres = $usuario->nombres;
            $data->paterno = $usuario->paterno;
            $data->materno = $usuario->materno;
            $data->dni = $usuario->nro_documento;
            $data->email = $usuario->usuario;
            $data->profile_photo_path = env("EXTERNALURLIMAGE") . '/storage/fotos/' . $usuario->foto;
            $data->profile_photo_url = "https://ui-avatars.com/api/?name=" . str_replace(" ", "+", $usuario->nombres) . "&color=7F9CF5&background=EBF4FF";
            $data->url = env("EXTERNALURLIMAGE");
        } elseif (Auth::check()) {
            $usuario = User::find(Auth::user()->id);
            $data = new \stdClass;
            $data->nombres = $usuario->name;
            $data->paterno = $usuario->paterno;
            $data->materno = $usuario->materno;
            $data->dni = $usuario->nro_documento;
            $data->email = $usuario->email;
            $data->profile_photo_path = $usuario->profile_photo_path;
            $data->profile_photo_url = "https://ui-avatars.com/api/?name=" . str_replace(" ", "+", $usuario->name) . "&color=7F9CF5&background=EBF4FF";
            $data->url = env("EXTERNALURLIMAGE");
        }
        // dd(Auth::guard('docente')->check());
        return array_merge(parent::share($request), [
            'permissions' => Auth::check() ? $this->permissions() : null,
            'response' => $request->session()->get('response'),
            'usuario' => $data
        ]);
    }
}
