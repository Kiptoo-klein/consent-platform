<?php

namespace App\Http\Middleware;

use App\Models\ConsentSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class HardenSignatureSubmission
{
    private const PREFIX =
        'data:image/png;base64,';

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $accessToken = (string) $request->route(
            'accessToken'
        );

        $consentSession =
            ConsentSession::query()
                ->where(
                    'access_token',
                    $accessToken
                )
                ->firstOrFail();

        if ($consentSession->isCompleted()) {
            Log::notice(
                'Duplicate consent submission was blocked.',
                [
                    'consent_session_id' =>
                        $consentSession->id,
                    'token_fingerprint' =>
                        $this->fingerprint(
                            $accessToken
                        ),
                ]
            );

            return redirect()->route(
                'public-consent.completed',
                $accessToken
            );
        }

        if ($consentSession->isCancelled()) {
            abort(
                410,
                'This consent record is no longer available.'
            );
        }

        $signatureData =
            $request->input('signature_data');

        if (! is_string($signatureData)) {
            $this->reject(
                $request,
                $consentSession,
                'Signature data was missing.',
                'Please draw your signature before submitting.'
            );
        }

        if (
            ! str_starts_with(
                $signatureData,
                self::PREFIX
            )
        ) {
            $this->reject(
                $request,
                $consentSession,
                'Signature data used an invalid prefix.',
                'The signature image format is invalid.'
            );
        }

        $encodedImage = substr(
            $signatureData,
            strlen(self::PREFIX)
        );

        $decodedImage = base64_decode(
            $encodedImage,
            true
        );

        if ($decodedImage === false) {
            $this->reject(
                $request,
                $consentSession,
                'Signature data was not valid base64.',
                'The signature image could not be processed.'
            );
        }

        $byteLength = strlen($decodedImage);

        $maxBytes = max(
            1000,
            (int) config(
                'security-hardening.signature.max_bytes',
                1500000
            )
        );

        if (
            $byteLength < 100
            || $byteLength > $maxBytes
        ) {
            $this->reject(
                $request,
                $consentSession,
                'Signature image size was rejected.',
                'The signature image size is invalid. Please clear it and sign again.'
            );
        }

        $pngSignature = "\x89PNG\r\n\x1a\n";

        if (
            ! str_starts_with(
                $decodedImage,
                $pngSignature
            )
        ) {
            $this->reject(
                $request,
                $consentSession,
                'Signature bytes were not a PNG image.',
                'The submitted signature is not a valid PNG image.'
            );
        }

        if (
            function_exists(
                'getimagesizefromstring'
            )
        ) {
            $imageInfo =
                @getimagesizefromstring(
                    $decodedImage
                );

            if (
                ! is_array($imageInfo)
                || ($imageInfo['mime'] ?? null)
                    !== 'image/png'
            ) {
                $this->reject(
                    $request,
                    $consentSession,
                    'Signature image metadata was invalid.',
                    'The submitted signature image is invalid.'
                );
            }

            $maxDimension = max(
                256,
                (int) config(
                    'security-hardening.signature.max_dimension',
                    4096
                )
            );

            $width = (int) (
                $imageInfo[0] ?? 0
            );

            $height = (int) (
                $imageInfo[1] ?? 0
            );

            if (
                $width < 1
                || $height < 1
                || $width > $maxDimension
                || $height > $maxDimension
            ) {
                $this->reject(
                    $request,
                    $consentSession,
                    'Signature dimensions were rejected.',
                    'The signature image dimensions are invalid.'
                );
            }
        }

        return $next($request);
    }

    private function reject(
        Request $request,
        ConsentSession $consentSession,
        string $logMessage,
        string $userMessage
    ): void {
        Log::warning(
            $logMessage,
            [
                'consent_session_id' =>
                    $consentSession->id,
                'route' =>
                    $request->route()?->getName(),
                'ip_fingerprint' =>
                    $this->fingerprint(
                        $request->ip()
                    ),
            ]
        );

        throw ValidationException::withMessages([
            'signature_data' => $userMessage,
        ]);
    }

    private function fingerprint(
        string $value
    ): string {
        return substr(
            hash_hmac(
                'sha256',
                $value,
                (string) config(
                    'app.key',
                    'consent-platform'
                )
            ),
            0,
            20
        );
    }
}
