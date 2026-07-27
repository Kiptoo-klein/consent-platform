<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ValidateConsentToken
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token = $request->route(
            'accessToken'
        );

        abort_unless(
            is_string($token)
            && Str::isUuid($token),
            404
        );

        return $next($request);
    }
}
