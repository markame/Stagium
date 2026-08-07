<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoordinatorUser
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isStudent(), 403);
        return $next($request);
    }
}
