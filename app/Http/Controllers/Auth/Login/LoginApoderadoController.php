<?php

namespace App\Http\Controllers\Auth\Login;

use Inertia\Inertia;
use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Models\Parentesco;
use Illuminate\Http\Request;
use App\Models\EstudianteApoderado;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginApoderadoController extends Controller
{
    protected $guard = '';

    protected $redirectTo = '/dashboard';

    public function index(){
        $response["parentescos"] =  Parentesco::all();
        return Inertia::render('Auth/LoginApoderado',["data"=>$response]);
    }
    protected function guard()
    {
        return Auth::guard($this->guard);
    }
    public function login(Request $request){
        $rules = $request->validate([
            'paterno' => 'required',
            'materno' => 'required',
            'documento' => 'required',
            'fecha_nac' => 'required',
            'documento_apoderado' => 'required',
            'paterno_apoderado' => 'required',
            'materno_apoderado' => 'required',
            'nombres_apoderado' => 'required',
            'celular_apoderado' => 'required',
            'parentesco_apoderado' => 'required',

        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
            'fecha_nac.required' => '* El campo fecha de nacimiento es obligatorio.',
        ]);

        $date = new \DateTime($request->fecha_nac);
        $fechaFormat = $date->format('Y-m-d');
        // dd(mb_strtoupper($request->paterno),$fechaFormat);
        $estudiante = Estudiante::where([
            ["paterno","=",mb_strtoupper($request->paterno)],
            ["materno","=",mb_strtoupper($request->materno)],
            ["nro_documento","=",$request->documento],
            ["fecha_nac","=",$fechaFormat]
        ])->first();
        if($estudiante){

            $apoderados = EstudianteApoderado::select("a.id")
                    ->join("apoderados as a","a.id","estudiante_apoderados.apoderados_id")
                    ->where("estudiante_apoderados.estudiantes_id",$estudiante->id)
                    ->where([
                        ["a.paterno","=",mb_strtoupper($request->paterno_apoderado)],
                        ["a.materno","=",mb_strtoupper($request->materno_apoderado)],
                        ["a.nombres","=",mb_strtoupper($request->nombres_apoderado)]
                    ])
                    ->first();
            // dd($apoderados);
            if($apoderados){
                $changeApoderado = Apoderado::find($apoderados->id);
                $changeApoderado->nro_documento = $request->documento_apoderado;
                $changeApoderado->paterno = mb_strtoupper($request->paterno_apoderado);
                $changeApoderado->materno = mb_strtoupper($request->materno_apoderado);
                $changeApoderado->nombres = mb_strtoupper($request->nombres_apoderado);
                $changeApoderado->celular = $request->celular_apoderado;
                $changeApoderado->parentescos_id = $request->parentesco_apoderado;
                $changeApoderado->acceso = $changeApoderado->acceso+1;
                $changeApoderado->save();
                // dd("existe");
            }else{
                $newApoderado = new Apoderado;
                $newApoderado->nro_documento = $request->documento_apoderado;
                $newApoderado->paterno = mb_strtoupper($request->paterno_apoderado);
                $newApoderado->materno = mb_strtoupper($request->materno_apoderado);
                $newApoderado->nombres = mb_strtoupper($request->nombres_apoderado);
                $newApoderado->celular = $request->celular_apoderado;
                $newApoderado->parentescos_id = $request->parentesco_apoderado;
                $newApoderado->acceso = 1;
                $newApoderado->save();

                $newEstdudianteApoderado = new EstudianteApoderado;
                $newEstdudianteApoderado->estudiantes_id = $estudiante->id;
                $newEstdudianteApoderado->apoderados_id = $newApoderado->id;
                $newEstdudianteApoderado->estado = "1";
                $newEstdudianteApoderado->save();
                // dd("no existe");

            }


            $this->guard = 'estudiante';
            Auth::guard('estudiante')->login($estudiante);
            $token = $estudiante->createToken('auth-token')->plainTextToken;
            return redirect('dashboard');

            // dd($query);
        }else{
            $response["status"] = false;
            $response["message"] = "Datos Incorrecto intente nuevamente.";
        }
        return redirect()->back()
            ->with('response', $response);
        // dd($request);
    }
    public function getApoderados(Request $request){
        // dd($request);
        $date = new \DateTime($request->fecha_nac);
        $fechaFormat = $date->format('Y-m-d');
        // dd(mb_strtoupper($request->paterno),$fechaFormat);
        $estudiante = Estudiante::where([
            ["paterno","=",mb_strtoupper($request->paterno)],
            ["materno","=",mb_strtoupper($request->materno)],
            ["nro_documento","=",$request->documento],
            ["fecha_nac","=",$fechaFormat]
        ])->first();
        // dd($estudiante);
        if($estudiante){

            $apoderados = EstudianteApoderado::select("a.*")
                    ->join("apoderados as a","a.id","estudiante_apoderados.apoderados_id")
                    ->where("estudiante_apoderados.estudiantes_id",$estudiante->id)
                    ->where([
                        ["a.nro_documento","=",$request->documento_apoderado]
                    ])
                    ->first();
            if($apoderados){
                $response["apoderado"]= $apoderados;
                $response["status"] = true;
            }else{
                $response["apoderado"]= array();
                $response["status"] = false;
            }
        }else{
            $response["apoderado"]= array();
            $response["status"] = false;
        }
        return $response;
    }
}
