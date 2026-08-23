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
use App\Models\Periodo;
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

    protected function syncUserRole($user, string $roleName): void
    {
        if ($user && ! $user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }
    }

    protected function redirectByRole(string $roleName)
    {
        if ($roleName === 'Docente') {
            return redirect()->route('docentes.recursos.cursos');
        }

        return redirect()->route('estudiantes.cursos');
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
        $periodo = Periodo::actual();
        $docenteApto = $periodo
            ? DocenteApto::query()
                ->habilitadoEnPeriodo($periodo->id)
                ->conIdentidadGoogle($user->id)
                ->first()
            : null;
        $estudiante = Estudiante::where('idgsuite', $user->id)->first();
        $idEstudiante = $estudiante ? $estudiante->id : '';
        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->where([
                ['matricula', '1'],
                ['estado', '1'],
            ])
            ->latest('id')
            ->first();


        if ($docenteApto) {
            $this->guard = 'docente';
            $this->syncUserRole($docenteApto, 'Docente');

            Auth::guard('docente')->login($docenteApto);

            // $usuario = Auth::guard('docente')->user();
            // dd($usuario);
            return $this->redirectByRole('Docente');
            // return Inertia::render('Dashboard');
        } elseif ($inscripcion) {
        
            $this->guard = 'estudiante';
            $this->syncUserRole($estudiante, 'Estudiante');

            Auth::guard('estudiante')->login($estudiante);

            // $usuario = Auth::guard('docente')->user();
            // dd($usuario);
            return $this->redirectByRole('Estudiante');
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
        $credenciales = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $periodo = Periodo::actual();
        $docenteApto = $periodo
            ? DocenteApto::query()
                ->habilitadoEnPeriodo($periodo->id)
                ->conCredenciales($credenciales['email'], $credenciales['password'])
                ->first()
            : null;
        $estudiante = Estudiante::where([
            ['usuario', $credenciales['email']],
            ['password', $credenciales['password']],
        ])->first();
        $idEstudiante = $estudiante ? $estudiante->id : '';
        #return $idEstudiante;
        // validar periodo para el siguiente proceso
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->where([
                ['matricula', '1'],
                ['estado', '1'],
            ])
            ->latest('id')
            ->first();
        


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
            $this->syncUserRole($docenteApto, 'Docente');

            Auth::guard('docente')->login($docenteApto);

            // $usuario = Auth::guard('docente')->user();
            // dd($usuario);
            return $this->redirectByRole('Docente');
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
            $this->syncUserRole($estudiante, 'Estudiante');

            Auth::guard('estudiante')->login($estudiante);

            // $usuario = Auth::guard('docente')->user();
            // dd($usuario);
            return $this->redirectByRole('Estudiante');
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
