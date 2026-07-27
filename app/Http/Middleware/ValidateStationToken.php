<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateStationToken
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $token = $request->route(
            'stationToken'
        );

        abort_unless(
            is_string($token)
            && preg_match(
                '/\A[A-Za-z0-9]{40,128}\z/D',
                $token
            ) === 1,
            404
        );

        return $next($request);
    }
}
