<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
// use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Database\QueryException;
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
        if (! $user) {
            return;
        }

        $user->unsetRelation('roles');

        if ($user->hasRole($roleName)) {
            return;
        }

        try {
            $user->assignRole($roleName);
        } catch (QueryException $exception) {
            // Some legacy records already have the pivot row even when the
            // permission package does not resolve it through hasRole().
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
        }
    }

    protected function loginWithGuard(Request $request, $user, string $guard, string $roleName)
    {
        $this->guard = $guard;
        $this->syncUserRole($user, $roleName);

        Auth::guard($guard)->login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($roleName);
    }

    protected function accessDenied(string $message = 'Acceso Denegado')
    {
        return redirect()->route('loginHome')->with('response', [
            'status' => false,
            'message' => $message,
        ]);
    }

    protected function finishLogin(Request $request, $docenteApto, $estudiante)
    {
        $inscripcion = $estudiante
            ? Inscripciones::query()
                ->delEstudiante($estudiante->id)
                ->delPeriodoActual()
                ->where([
                    ['matricula', '1'],
                    ['estado', '1'],
                ])
                ->latest('id')
                ->first()
            : null;

        if ($docenteApto) {
            return $this->loginWithGuard($request, $docenteApto, 'docente', 'Docente');
        }

        if ($inscripcion) {
            return $this->loginWithGuard($request, $estudiante, 'estudiante', 'Estudiante');
        }

        return $this->accessDenied(
            'La cuenta no corresponde a un docente o estudiante habilitado en el periodo actual.'
        );
    }

    protected function verifiedGoogleEmail($googleUser): ?string
    {
        $providerData = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        $verified = $providerData['email_verified']
            ?? $providerData['verified_email']
            ?? false;
        $email = strtolower(trim((string) $googleUser->getEmail()));

        if (! filter_var($verified, FILTER_VALIDATE_BOOLEAN)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    protected function findGoogleTeacher($periodoId, string $googleId, ?string $email)
    {
        $docenteApto = DocenteApto::query()
            ->habilitadoEnPeriodo($periodoId)
            ->conIdentidadGoogle($googleId)
            ->first();

        if ($docenteApto || ! $email) {
            return $docenteApto;
        }

        return DocenteApto::query()
            ->habilitadoEnPeriodo($periodoId)
            ->where(function ($identity) use ($email) {
                $identity->whereRaw('LOWER(usuario) = ?', [$email])
                    ->orWhereHas('docente', function ($docente) use ($email) {
                        $docente->whereRaw('LOWER(usuario) = ?', [$email])
                            ->orWhereRaw('LOWER(email) = ?', [$email]);
                    });
            })
            ->first();
    }

    protected function findGoogleStudent(string $googleId, ?string $email)
    {
        $estudiante = Estudiante::where('idgsuite', $googleId)->first();

        if ($estudiante || ! $email) {
            return $estudiante;
        }

        return Estudiante::query()
            ->where(function ($identity) use ($email) {
                $identity->whereRaw('LOWER(usuario) = ?', [$email])
                    ->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();
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
    public function handleProviderCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            report($exception);

            return $this->accessDenied(
                'No se pudo validar la cuenta de Google. Intente iniciar sesión nuevamente.'
            );
        }

        $googleId = (string) $googleUser->getId();
        $googleEmail = $this->verifiedGoogleEmail($googleUser);
        $periodo = Periodo::actual();
        $docenteApto = $periodo
            ? $this->findGoogleTeacher($periodo->id, $googleId, $googleEmail)
            : null;
        $estudiante = $this->findGoogleStudent($googleId, $googleEmail);

        return $this->finishLogin($request, $docenteApto, $estudiante);
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

    public function loginSinGsuit(Request $request)
    {
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

        return $this->finishLogin($request, $docenteApto, $estudiante);
    }
}
