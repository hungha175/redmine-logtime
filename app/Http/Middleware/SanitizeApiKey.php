<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redact API key from request to prevent exposure in logs, error pages, or debug output.
 */
class SanitizeApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('api_key')) {
            $request->attributes->set('_api_key_raw', $request->input('api_key'));
            $request->merge(['api_key' => '[REDACTED]']);
        }
        if ($request->has('login_password')) {
            $request->attributes->set('_login_password_raw', $request->input('login_password'));
            $request->merge(['login_password' => '[REDACTED]']);
        }

        return $next($request);
    }
}
