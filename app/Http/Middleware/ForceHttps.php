<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $secure = $request->secure();
        $env = app()->environment('production');
        Log::debug('ForceHttps: secure=' . var_export($secure, true) . ', env production=' . var_export($env, true));
        if (!$secure && $env) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
