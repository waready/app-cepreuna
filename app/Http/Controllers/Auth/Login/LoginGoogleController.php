<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
// use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\DocenteApto;
use App\Models\Estudiante;
use App\Models\Inscripciones;
use Inertia\Inertia;

class LoginGoogleController extends Controller
{
    // use Authenticates;

    protected $guard = '';

    protected $redirectTo = '/dashboard';
    // public function __construct()
    // {
    //     $this->middleware('guest:docente')->except('logout');
    // }
    public function index()
    {
        // return view('auth.login.login-google');
        return Inertia::render('Auth/LoginSocial');
    }

    protected function guard()
    {
        return Auth::guard($this->guard);
    }

    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('google')->stateless()->user();
        // dd($user);
        $docenteApto = DocenteApto::where('idgsuite', $user->id)->first();
        $estudiante = Estudiante::where('idgsuite', $user->id)->first();
        $idEstudiante = $estudiante ? $estudiante->id : '';
        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::where([['matricula', '1'], ['estudiantes_id', $idEstudiante]])->first();


        if ($docenteApto) {
            $this->guard = 'docente';

            Auth::guard('docente')->login($docenteApto);

            // $usuario = Auth::guard('docente')->user();
            $token = $docenteApto->createToken('auth-token')->plainTextToken;
            // dd($usuario);
            return redirect('dashboard');
            // return Inertia::render('Dashboard');
        } elseif ($inscripcion) {
        
            $this->guard = 'estudiante';

            Auth::guard('estudiante')->login($estudiante);

            // $usuario = Auth::guard('docente')->user();
            $token = $estudiante->createToken('auth-token')->plainTextToken;
            // dd($usuario);
            return redirect('dashboard');
            // return Inertia::render('Dashboard');
        } else {
            // Auth::login($findUser);
            // return redirect('/web/login')->with('status', 'Acceso Denegado');
            $response["status"] = false;
            $response["message"] = 'Acceso Denegado';

            return redirect()->back()->with('response', $response);
        }
    }
    public function logout()
    {
        // dd("docente");
        if (Auth::guard('docente')->check()) // this means that the admin was logged in.
        {
            Auth::guard('docente')->logout();
            return redirect('/');
            // return Inertia::render('Auth/LoginSocial');
        } elseif (Auth::guard('estudiante')->check()) {
            Auth::guard('estudiante')->logout();
            dd("estudiante");
            return redirect('/');
            // return Inertia::render('Auth/LoginSocial');
        }

        // $this->guard()->logout();
        // $request->session()->invalidate();

        return redirect('/');
    }

    public function loginSinGsuit(request $request){
       
        $docenteApto = DocenteApto::where([['usuario', $request->email],['password',$request->password]])->first();
        $estudiante = Estudiante::where([['usuario', $request->email],['password',$request->password]])->first();
        $idEstudiante = $estudiante ? $estudiante->id : '';
        #return $idEstudiante;
        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::where([['matricula', '1'], ['estudiantes_id', $idEstudiante]])->first();
        


        if ($docenteApto) {

        	/*if($request->email == 'd_razapana@cepreuna.edu.pe'){
				    if (! $docenteApto->hasRole('Docente')) {
        				$docenteApto->assignRole('Docente');
			    	}

			    //if (! $docenteApto->hasPermissionTo('menu dashboard')) {
			    //    $docenteApto->givePermissionTo('menu dashboard');
			   // }
        	}*/

        
            $this->guard = 'docente';

            Auth::guard('docente')->login($docenteApto);

            // $usuario = Auth::guard('docente')->user();
            $token = $docenteApto->createToken('auth-token')->plainTextToken;
            // dd($usuario);
            return redirect('dashboard');
            // return Inertia::render('Dashboard');
        } elseif ($inscripcion) {
        	/*if($request->email == '60341156@cepreuna.edu.pe'){
				    if (! $estudiante->hasRole('Estudiante')) {
        				$estudiante->assignRole('Estudiante');
			    	}

			    //if (! $estudiante->hasPermissionTo('menu dashboard')) {
			    //    $estudiante->givePermissionTo('menu dashboard');
			   // }
        	}*/

            $this->guard = 'estudiante';

            Auth::guard('estudiante')->login($estudiante);

            // $usuario = Auth::guard('docente')->user();
            $token = $estudiante->createToken('auth-token')->plainTextToken;
            
            // dd($usuario);
            return redirect('dashboard');
            // return Inertia::render('Dashboard');
        } else {
            // Auth::login($findUser);
            // return redirect('/web/login')->with('status', 'Acceso Denegado');
            $response["status"] = false;
            $response["message"] = 'Acceso Denegado';

            return redirect()->back()->with('response', $response);
        }

    }
}
