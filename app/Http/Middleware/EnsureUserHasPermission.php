<?php

namespace App\Http\Middleware;

use App\Support\AccessControl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        AccessControl::seedDefaults();

        if (! $request->user()?->hasPermission($permission)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
