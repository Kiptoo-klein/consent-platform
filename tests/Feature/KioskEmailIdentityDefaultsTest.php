<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class KioskEmailIdentityDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_kiosk_uses_organization_email_identity_defaults(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Example Medical Centre',
            'slug' => 'example-medical-centre',
            'email' => 'consent@example.org',
            'support_email' => 'support@example.org',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user);

        $html = Blade::render(
            <<<'BLADE'
                @include(
                    'signing-stations.partials.email-delivery-settings'
                )
            BLADE,
            [
                'errors' => new ViewErrorBag(),
            ]
        );

        $this->assertStringContainsString(
            'name="sender_name"',
            $html
        );

        $this->assertStringContainsString(
            'value="Example Medical Centre"',
            $html
        );

        $this->assertStringContainsString(
            'name="reply_to_email"',
            $html
        );

        $this->assertStringContainsString(
            'value="consent@example.org"',
            $html
        );
    }

    public function test_saved_kiosk_values_override_organization_defaults(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Example Medical Centre',
            'slug' => 'example-medical-centre',
            'email' => 'consent@example.org',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user);

        $signingStation = new SigningStation([
            'sender_name' => 'Westlands Clinic Desk',
            'reply_to_email' => 'westlands@example.org',
        ]);

        $html = Blade::render(
            <<<'BLADE'
                @include(
                    'signing-stations.partials.email-delivery-settings',
                    ['signingStation' => $signingStation]
                )
            BLADE,
            [
                'signingStation' => $signingStation,
                'errors' => new ViewErrorBag(),
            ]
        );

        $this->assertStringContainsString(
            'value="Westlands Clinic Desk"',
            $html
        );

        $this->assertStringContainsString(
            'value="westlands@example.org"',
            $html
        );

        $this->assertStringNotContainsString(
            'value="consent@example.org"',
            $html
        );
    }

    public function test_support_email_is_used_only_when_primary_email_is_missing(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Example Medical Centre',
            'slug' => 'example-medical-centre',
            'email' => null,
            'support_email' => 'support@example.org',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user);

        $html = Blade::render(
            <<<'BLADE'
                @include(
                    'signing-stations.partials.email-delivery-settings'
                )
            BLADE,
            [
                'errors' => new ViewErrorBag(),
            ]
        );

        $this->assertStringContainsString(
            'value="support@example.org"',
            $html
        );
    }
}
