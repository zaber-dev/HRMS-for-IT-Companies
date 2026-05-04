<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user() &&
            $request->user()->must_change_password === true &&
            ! $request->routeIs('security.edit', 'user-password.update')
        ) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
