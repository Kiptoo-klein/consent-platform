<?php

namespace App\Http\Controllers;

use App\Models\ConsentTemplate;
use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SigningStationController extends Controller
{
    public function index(Request $request): View
    {
        $organizationId = $this->organizationId($request);

        $signingStations = SigningStation::query()
            ->where('organization_id', $organizationId)
            ->with([
                'consentTemplate:id,title',
            ])
            ->withCount([
                'consentSessions',

                'consentSessions as completed_sessions_count' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->latest()
            ->paginate(12);

        return view(
            'signing-stations.index',
            compact('signingStations')
        );
    }

    public function create(Request $request): View
    {
        $organizationId = $this->organizationId($request);

        $consentTemplates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'published')
            ->whereNotNull('active_version_id')
            ->whereIn('usage_type', [
                ConsentTemplate::USAGE_SIGNING_STATION,
                ConsentTemplate::USAGE_BOTH,
            ])
            ->orderBy('title')
            ->get();

        return view(
            'signing-stations.create',
            compact('consentTemplates')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $organizationId = $this->organizationId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'consent_template_id' => [
                'required',
                'integer',

                Rule::exists('consent_templates', 'id')
                    ->where('organization_id', $organizationId),
            ],

            'require_email' => [
                'nullable',
                'boolean',
            ],

            'require_reference' => [
                'nullable',
                'boolean',
            ],


            'sender_name' => [
                'nullable',
                'string',
                'max:120',
            ],

            'reply_to_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'email_description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'auto_reset_seconds' => [
                'required',
                'integer',
                'min:3',
                'max:300',
            ],
        ]);

        $template = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($validated['consent_template_id']);

        if (
            ! $template->supportsSigningStation()
            || ! $template->isLive()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'consent_template_id' =>
                        'Select a published template enabled for public signing stations.',
                ]);
        }

        if ($this->activeKioskLimitReached($organizationId)) {
            return back()
                ->withInput()
                ->withErrors([
                    'subscription' =>
                        'The active signing station limit for this subscription plan has been reached.',
                ]);
        }

        $station = SigningStation::create([
            'organization_id' => $organizationId,
            'consent_template_id' => $template->id,
            'created_by' => Auth::id(),
            'name' => $validated['name'],
            'station_token' => Str::random(64),
            'active' => true,
            'require_email' => $request->boolean('require_email'),
            'require_reference' => false,

            'sender_name' => $validated['sender_name'] ?? null,
            'reply_to_email' => $validated['reply_to_email'] ?? null,
            'email_description' => $validated['email_description'] ?? null,
            'auto_reset_seconds' => $validated['auto_reset_seconds'],
        ]);

        return redirect()
            ->route('signing-stations.show', $station)
            ->with(
                'success',
                'Signing station created successfully.'
            );
    }

    public function show(
        Request $request,
        SigningStation $signingStation
    ): View {
        $this->authorizeStation($request, $signingStation);

        $signingStation->load([
            'consentTemplate',
            'creator',
        ]);

        $signingStation->loadCount([
            'consentSessions',

            'consentSessions as completed_sessions_count' => function ($query) {
                $query->where('status', 'completed');
            },

            'consentSessions as pending_sessions_count' => function ($query) {
                $query->where('status', 'pending');
            },

            'consentSessions as in_progress_sessions_count' => function ($query) {
                $query->where('status', 'in_progress');
            },

            'consentSessions as cancelled_sessions_count' => function ($query) {
                $query->where('status', 'cancelled');
            },
        ]);

        $recentSessions = $signingStation
            ->consentSessions()
            ->latest()
            ->limit(10)
            ->get();

        return view(
            'signing-stations.show',
            compact(
                'signingStation',
                'recentSessions'
            )
        );
    }

    public function edit(
        Request $request,
        SigningStation $signingStation
    ): View {
        $this->authorizeStation($request, $signingStation);

        $organizationId = $this->organizationId($request);

        $consentTemplates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'published')
            ->whereNotNull('active_version_id')
            ->whereIn('usage_type', [
                ConsentTemplate::USAGE_SIGNING_STATION,
                ConsentTemplate::USAGE_BOTH,
            ])
            ->orderBy('title')
            ->get();

        return view(
            'signing-stations.edit',
            compact(
                'signingStation',
                'consentTemplates'
            )
        );
    }

    public function update(
        Request $request,
        SigningStation $signingStation
    ): RedirectResponse {
        $this->authorizeStation($request, $signingStation);

        $organizationId = $this->organizationId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'consent_template_id' => [
                'required',
                'integer',

                Rule::exists('consent_templates', 'id')
                    ->where('organization_id', $organizationId),
            ],

            'require_email' => [
                'nullable',
                'boolean',
            ],

            'require_reference' => [
                'nullable',
                'boolean',
            ],


            'sender_name' => [
                'nullable',
                'string',
                'max:120',
            ],

            'reply_to_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'email_description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'auto_reset_seconds' => [
                'required',
                'integer',
                'min:3',
                'max:300',
            ],
        ]);

        $template = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($validated['consent_template_id']);

        if (
            ! $template->supportsSigningStation()
            || ! $template->isLive()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'consent_template_id' =>
                        'Select a published template enabled for public signing stations.',
                ]);
        }

        $signingStation->update([
            'name' => $validated['name'],
            'consent_template_id' => $template->id,
            'require_email' => $request->boolean('require_email'),
            'require_reference' => false,

            'sender_name' => $validated['sender_name'] ?? null,
            'reply_to_email' => $validated['reply_to_email'] ?? null,
            'email_description' => $validated['email_description'] ?? null,
            'auto_reset_seconds' => $validated['auto_reset_seconds'],
        ]);

        return redirect()
            ->route('signing-stations.show', $signingStation)
            ->with(
                'success',
                'Signing station updated successfully.'
            );
    }

    public function toggle(
        Request $request,
        SigningStation $signingStation
    ): RedirectResponse {
        $this->authorizeStation($request, $signingStation);

        if (
            ! $signingStation->active
            && $this->activeKioskLimitReached(
                (int) $signingStation->organization_id
            )
        ) {
            return back()->withErrors([
                'subscription' =>
                    'The active signing station limit for this subscription plan has been reached.',
            ]);
        }

        $signingStation->update([
            'active' => ! $signingStation->active,
        ]);

        $message = $signingStation->active
            ? 'Signing station activated.'
            : 'Signing station paused.';

        return back()->with('success', $message);
    }

    public function regenerateToken(
        Request $request,
        SigningStation $signingStation
    ): RedirectResponse {
        $this->authorizeStation($request, $signingStation);

        $signingStation->update([
            'station_token' => Str::random(64),
        ]);

        return redirect()
            ->route('signing-stations.show', $signingStation)
            ->with(
                'success',
                'The station link was regenerated. The previous link no longer works.'
            );
    }

    public function qrCode(
        Request $request,
        SigningStation $signingStation
    ): Response {
        $this->authorizeStation($request, $signingStation);

        $stationUrl = route(
            'public-signing-stations.show',
            $signingStation->station_token
        );

        $qrOutput = (new QRCode())->render($stationUrl);

        $svg = $this->extractQrImageContent($qrOutput);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',

            'Content-Disposition' =>
                'inline; filename="signing-station-' .
                $signingStation->id .
                '.svg"',

            'Cache-Control' =>
                'private, no-store, no-cache, must-revalidate, max-age=0',

            'Pragma' => 'no-cache',

            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadQrCode(
        Request $request,
        SigningStation $signingStation
    ): Response {
        $this->authorizeStation($request, $signingStation);

        $stationUrl = route(
            'public-signing-stations.show',
            $signingStation->station_token
        );

        $qrOutput = (new QRCode())->render($stationUrl);

        $svg = $this->extractQrImageContent($qrOutput);

        $filename =
            Str::slug($signingStation->name) .
            '-qr-code.svg';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',

            'Content-Disposition' =>
                'attachment; filename="' .
                $filename .
                '"',

            'Content-Length' => (string) strlen($svg),

            'Cache-Control' =>
                'private, no-store, no-cache, must-revalidate, max-age=0',

            'Pragma' => 'no-cache',

            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function extractQrImageContent(string $qrOutput): string
    {
        if (! str_starts_with($qrOutput, 'data:image/')) {
            return $qrOutput;
        }

        $parts = explode(',', $qrOutput, 2);

        abort_unless(
            count($parts) === 2,
            500,
            'The QR code image could not be generated.'
        );

        [$metadata, $encodedContent] = $parts;

        if (str_contains($metadata, ';base64')) {
            $decodedContent = base64_decode(
                $encodedContent,
                true
            );

            abort_if(
                $decodedContent === false,
                500,
                'The QR code image could not be decoded.'
            );

            return $decodedContent;
        }

        return urldecode($encodedContent);
    }

    /**
     * Determine whether the organization has used all active kiosk slots.
     */
    private function activeKioskLimitReached(
        int $organizationId
    ): bool {
        $maximumActiveKiosks = OrganizationSubscription::query()
            ->where('organization_id', $organizationId)
            ->with('plan')
            ->first()
            ?->plan
            ?->max_active_kiosks;

        /*
         * A missing subscription or plan limit is handled by the
         * subscription-access layer rather than treated as a zero limit.
         */
        if ($maximumActiveKiosks === null) {
            return false;
        }

        $activeKiosks = SigningStation::query()
            ->where('organization_id', $organizationId)
            ->where('active', true)
            ->count();

        return $activeKiosks >= (int) $maximumActiveKiosks;
    }

    private function authorizeStation(
        Request $request,
        SigningStation $signingStation
    ): void {
        abort_unless(
            (int) $signingStation->organization_id ===
                (int) $this->organizationId($request),
            403
        );
    }

    private function organizationId(Request $request): int
    {
        $user = $request->user();

        $organizationId =
            $user->organization_id
            ?? $user->current_organization_id
            ?? optional($user->organization)->id;

        abort_unless(
            $organizationId,
            403,
            'No organization is available.'
        );

        return (int) $organizationId;
    }
}
