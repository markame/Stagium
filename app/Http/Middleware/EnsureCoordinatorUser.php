<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoordinatorUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isStudent()) {
            return redirect()->route('student.portal');
        }

        return $next($request);
    }
}
