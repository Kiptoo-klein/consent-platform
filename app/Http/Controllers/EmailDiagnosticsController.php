<?php

namespace App\Http\Controllers;

use App\Mail\EmailConfigurationTestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailDiagnosticsController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensurePlatformAdmin($request);

        $mailer = (string) config('mail.default', 'log');
        $mailerConfig = config("mail.mailers.{$mailer}", []);

        if (! is_array($mailerConfig)) {
            $mailerConfig = [];
        }

        $host = data_get($mailerConfig, 'host');
        $port = data_get($mailerConfig, 'port');
        $username = data_get($mailerConfig, 'username');
        $password = data_get($mailerConfig, 'password');

        $encryption =
            data_get($mailerConfig, 'scheme')
            ?? data_get($mailerConfig, 'encryption');

        $fromAddress = (string) config(
            'mail.from.address',
            ''
        );

        $checks = [
            [
                'label' => 'Configured mailer',
                'status' => in_array(
                    $mailer,
                    ['log', 'array'],
                    true
                ) ? 'warning' : 'pass',
                'details' => in_array(
                    $mailer,
                    ['log', 'array'],
                    true
                )
                    ? "The {$mailer} mailer does not deliver to a real inbox."
                    : "The application is using the {$mailer} mailer.",
            ],
            [
                'label' => 'SMTP host',
                'status' => $mailer !== 'smtp' || filled($host)
                    ? 'pass'
                    : 'fail',
                'details' => $mailer === 'smtp'
                    ? ($host ?: 'MAIL_HOST is missing.')
                    : 'Not required by the selected mailer.',
            ],
            [
                'label' => 'SMTP port',
                'status' => $mailer !== 'smtp' || filled($port)
                    ? 'pass'
                    : 'fail',
                'details' => $mailer === 'smtp'
                    ? ($port ?: 'MAIL_PORT is missing.')
                    : 'Not required by the selected mailer.',
            ],
            [
                'label' => 'SMTP username',
                'status' => $mailer !== 'smtp' || filled($username)
                    ? 'pass'
                    : 'warning',
                'details' => $mailer === 'smtp'
                    ? $this->mask((string) $username)
                    : 'Not required by the selected mailer.',
            ],
            [
                'label' => 'SMTP password',
                'status' => $mailer !== 'smtp' || filled($password)
                    ? 'pass'
                    : 'fail',
                'details' => filled($password)
                    ? 'A password is configured and hidden.'
                    : 'No SMTP password is configured.',
            ],
            [
                'label' => 'From address',
                'status' => filter_var(
                    $fromAddress,
                    FILTER_VALIDATE_EMAIL
                ) ? 'pass' : 'fail',
                'details' => $fromAddress ?: 'MAIL_FROM_ADDRESS is missing.',
            ],
            [
                'label' => 'Queue connection',
                'status' => config('queue.default') === 'sync'
                    ? 'warning'
                    : 'pass',
                'details' => 'Current connection: '
                    .config('queue.default', 'sync'),
            ],
            [
                'label' => 'Domain authentication',
                'status' => 'deferred',
                'details' =>
                    'SPF, DKIM and DMARC will be completed after purchasing a domain.',
            ],
        ];

        return view(
            'settings.email-diagnostics',
            [
                'checks' => $checks,
                'configuration' => [
                    'mailer' => $mailer,
                    'host' => $host ?: 'Not configured',
                    'port' => $port ?: 'Not configured',
                    'encryption' =>
                        $encryption ?: 'Not configured',
                    'username' =>
                        $this->mask((string) $username),
                    'passwordConfigured' =>
                        filled($password),
                    'fromAddress' =>
                        $fromAddress ?: 'Not configured',
                    'fromName' => config(
                        'mail.from.name',
                        config('app.name')
                    ),
                    'queue' => config(
                        'queue.default',
                        'sync'
                    ),
                ],
            ]
        );
    }

    public function sendTest(
        Request $request
    ): RedirectResponse {
        $this->ensurePlatformAdmin($request);

        $validated = $request->validate([
            'recipient' => [
                'required',
                'email:rfc',
                'max:255',
            ],

            'delivery_mode' => [
                'required',
                Rule::in([
                    'immediate',
                    'queued',
                ]),
            ],

            'include_attachment' => [
                'nullable',
                'boolean',
            ],
        ]);

        $mailer = (string) config(
            'mail.default',
            'log'
        );

        $mailable =
            new EmailConfigurationTestMail(
                requestedBy:
                    (string) $request->user()->email,

                deliveryMode:
                    $validated['delivery_mode'],

                mailerName:
                    $mailer,

                includeAttachment:
                    $request->boolean(
                        'include_attachment'
                    ),
            );

        try {
            $pendingMail = Mail::mailer($mailer)
                ->to($validated['recipient']);

            if (
                $validated['delivery_mode']
                === 'queued'
                || app(
                    \App\Services\EmailQuotaService::class
                )->shouldQueue()
            ) {
                $pendingMail->queue($mailable);
                $action = 'queued';
            } else {
                $pendingMail->send($mailable);
                $action = 'sent';
            }

            Log::info(
                'Email diagnostics test accepted.',
                [
                    'requested_by' =>
                        $request->user()->id,
                    'recipient_domain' =>
                        str($validated['recipient'])
                            ->after('@')
                            ->lower()
                            ->value(),
                    'mailer' => $mailer,
                    'delivery_mode' =>
                        $validated['delivery_mode'],
                    'attachment' =>
                        $request->boolean(
                            'include_attachment'
                        ),
                ]
            );

            $message =
                "Test email {$action} using the {$mailer} mailer.";

            if (
                in_array(
                    $mailer,
                    ['log', 'array'],
                    true
                )
            ) {
                $message .=
                    ' This mailer does not deliver to a real inbox.';
            }

            if (
                $validated['delivery_mode']
                === 'queued'
                && config('queue.default') === 'sync'
            ) {
                $message .=
                    ' The sync queue processed it immediately.';
            }

            return back()->with(
                'success',
                $message
            );
        } catch (Throwable $exception) {
            Log::error(
                'Email diagnostics test failed.',
                [
                    'requested_by' =>
                        $request->user()->id,
                    'mailer' => $mailer,
                    'error_type' =>
                        $exception::class,
                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return back()
                ->withInput()
                ->withErrors([
                    'email_test' =>
                        'The test email failed: '
                        .$exception->getMessage(),
                ]);
        }
    }

    private function ensurePlatformAdmin(
            Request $request
        ): void {
            $user = $request->user();

            abort_unless(
                $user
                && filled(
                    $user->platform_role_id
                ),
                403
            );
        }

        

    private function mask(string $value): string
    {
        if ($value === '') {
            return 'Not configured';
        }

        if (str_contains($value, '@')) {
            [$name, $domain] = explode(
                '@',
                $value,
                2
            );

            $visible = mb_substr($name, 0, 2);

            return $visible
                .str_repeat(
                    '•',
                    max(
                        3,
                        mb_strlen($name) - 2
                    )
                )
                .'@'
                .$domain;
        }

        return mb_substr($value, 0, 2)
            .str_repeat(
                '•',
                max(
                    4,
                    mb_strlen($value) - 2
                )
            );
    }
}
