<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityHardeningMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $this->validateTrustedHost($request);
        $this->validateOrganizationBoundary($request);

        $response = $next($request);

        $this->applySecurityHeaders(
            $request,
            $response
        );

        return $response;
    }

    private function validateTrustedHost(
        Request $request
    ): void {
        $allowedHosts = config(
            'security-hardening.allowed_hosts',
            []
        );

        if (
            ! is_array($allowedHosts)
            || count($allowedHosts) === 0
        ) {
            return;
        }

        $host = strtolower($request->getHost());

        $allowed = collect($allowedHosts)
            ->filter(fn ($pattern) => filled($pattern))
            ->contains(
                fn ($pattern) =>
                    Str::is(
                        strtolower(
                            (string) $pattern
                        ),
                        $host
                    )
            );

        if ($allowed) {
            return;
        }

        Log::warning(
            'Security hardening rejected an untrusted host.',
            [
                'host' => $host,
                'route' =>
                    $request->route()?->getName(),
            ]
        );

        abort(400, 'Invalid request host.');
    }

    private function validateOrganizationBoundary(
        Request $request
    ): void {
        $user = $request->user();

        if (
            ! $user
            || blank($user->organization_id)
        ) {
            return;
        }

        $route = $request->route();

        if (! $route) {
            return;
        }

        foreach (
            $route->parameters() as $parameter
        ) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $recordOrganizationId =
                $parameter->getAttribute(
                    'organization_id'
                );

            if ($recordOrganizationId === null) {
                continue;
            }

            if (
                (int) $recordOrganizationId
                === (int) $user->organization_id
            ) {
                continue;
            }

            Log::warning(
                'Cross-organization access was blocked.',
                [
                    'user_id' => $user->id,
                    'user_organization_id' =>
                        $user->organization_id,
                    'record_type' =>
                        $parameter::class,
                    'record_id' =>
                        $parameter->getKey(),
                    'record_organization_id' =>
                        $recordOrganizationId,
                    'route' =>
                        $route->getName(),
                ]
            );

            abort(
                403,
                'You do not have permission to access this record.'
            );
        }
    }

    private function applySecurityHeaders(
        Request $request,
        Response $response
    ): void {
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        $response->headers->set(
            'Permissions-Policy',
            implode(', ', [
                'camera=()',
                'microphone=()',
                'geolocation=()',
                'payment=()',
                'usb=()',
            ])
        );

        $response->headers->set(
            'Cross-Origin-Opener-Policy',
            'same-origin'
        );

        $routeName =
            $request->route()?->getName();

        if (
            is_string($routeName)
            && (
                str_starts_with(
                    $routeName,
                    'public-consent.'
                )
                || str_starts_with(
                    $routeName,
                    'public-signing-stations.'
                )
            )
        ) {
            $response->headers->set(
                'Cache-Control',
                'no-store, private, max-age=0'
            );

            $response->headers->set(
                'Pragma',
                'no-cache'
            );

            $response->headers->set(
                'Expires',
                '0'
            );
        }

        if (
            $request->isSecure()
            && app()->environment('production')
        ) {
            $maxAge = max(
                0,
                (int) config(
                    'security-hardening.hsts_max_age',
                    31536000
                )
            );

            if ($maxAge > 0) {
                $response->headers->set(
                    'Strict-Transport-Security',
                    "max-age={$maxAge}; includeSubDomains"
                );
            }
        }

        $cspMode = strtolower(
            (string) config(
                'security-hardening.csp_mode',
                'report-only'
            )
        );

        $policy = trim(
            (string) config(
                'security-hardening.csp_policy',
                ''
            )
        );

        if (
            $policy !== ''
            && $cspMode === 'enforce'
        ) {
            $response->headers->set(
                'Content-Security-Policy',
                $policy
            );
        } elseif (
            $policy !== ''
            && $cspMode === 'report-only'
        ) {
            $response->headers->set(
                'Content-Security-Policy-Report-Only',
                $policy
            );
        }
    }
}
