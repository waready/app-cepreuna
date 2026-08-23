<?php

namespace App\Http\Middleware;

use App\Models\Periodo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCurrentTeacherPeriod
{
    public function handle(Request $request, Closure $next)
    {
        $docenteApto = Auth::guard('docente')->user();

        if (!$docenteApto) {
            return $next($request);
        }

        $periodo = Periodo::actual();

        if ($periodo && $docenteApto->estaHabilitadoEnPeriodo($periodo->id)) {
            return $next($request);
        }

        Auth::guard('docente')->logout();

        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }

        $response = [
            'status' => false,
            'message' => 'Su cuenta docente no pertenece al periodo activo.',
        ];

        if ($request->expectsJson()) {
            return response()->json($response, 403);
        }

        return redirect('/')->with('response', $response);
    }
}
