<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('app.auth_user');
        $pass = config('app.auth_password');

        if (empty($user) || empty($pass)) {
            return $next($request);
        }

        if ($request->getUser() !== $user || $request->getPassword() !== $pass) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Logtime"',
            ]);
        }

        return $next($request);
    }
}
