<?php

namespace App\Http\Middleware;

use App\Support\BancoPreguntasRevisionAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureQuestionBankReviewAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            app(BancoPreguntasRevisionAccess::class)->permite(Auth::user()),
            404
        );

        return $next($request);
    }
}
