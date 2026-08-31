<?php

namespace App\Http\Middleware;

use App\Support\DocentePreguntasDemoAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTeacherQuestionDemoAccess
{
    public function handle(Request $request, Closure $next)
    {
        $cuenta = Auth::guard('docente')->user();

        abort_unless(app(DocentePreguntasDemoAccess::class)->permite($cuenta), 404);

        return $next($request);
    }
}
