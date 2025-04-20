<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbility
{
    public function handle($request, Closure $next, $ability)
    {
        if (!$request->user() || !$request->user()->tokenCan($ability)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
