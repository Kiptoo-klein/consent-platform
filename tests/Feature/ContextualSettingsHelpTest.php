<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextualSettingsHelpTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this
            ->post('/register', [
                'organization_name' =>
                    'Contextual Help Organization',

                'name' =>
                    'Contextual Help Administrator',

                'email' =>
                    'contextual-help@example.com',

                'password' =>
                    'StrongPass1!',

                'password_confirmation' =>
                    'StrongPass1!',
            ])
            ->assertRedirect(
                route('verification.notice')
            );

        $this->verifyAuthenticatedUser();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'contextual-help@example.com'
                )
                ->firstOrFail();

        $this->organization =
            $this->administrator
                ->organization()
                ->firstOrFail();
    }

    public function test_add_user_has_detailed_page_specific_help(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-users.create',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="users-create"',
                false
            )
            ->assertSeeText(
                'Add user help'
            )
            ->assertSeeText(
                'How invitations work'
            )
            ->assertSeeText(
                'Choosing the right role'
            )
            ->assertSeeText(
                'Seat and role limits'
            )
            ->assertSeeText(
                'The invited user creates their own password'
            )
            ->assertSeeText(
                'Organization Admin'
            )
            ->assertSeeText(
                'Billing Owner'
            )
            ->assertDontSee(
                'data-help-context="settings"',
                false
            );
    }

    public function test_user_management_has_its_own_help_context(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-users.index',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="users"',
                false
            )
            ->assertSeeText(
                'User management help'
            )
            ->assertSeeText(
                'Understanding seat usage'
            )
            ->assertSeeText(
                'Disabled users continue to occupy seats'
            );
    }

    public function test_billing_management_has_detailed_page_specific_help(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-billing.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="billing-management"',
                false
            )
            ->assertSeeText(
                'Billing management help'
            )
            ->assertSeeText(
                'Billing Owner'
            )
            ->assertSeeText(
                'Invoices and payments'
            )
            ->assertSeeText(
                'Receipts'
            )
            ->assertSeeText(
                'Invoice reminders'
            )
            ->assertSeeText(
                'Subscription relationship'
            )
            ->assertSeeText(
                'Reminder Preferences'
            )
            ->assertSeeText(
                'Reminder Recipients'
            )
            ->assertDontSee(
                'data-help-context="settings"',
                false
            );
    }

    public function test_reminder_preferences_have_their_own_help(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-billing.reminder-preferences.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="billing-reminder-preferences"',
                false
            )
            ->assertSeeText(
                'Reminder preferences help'
            )
            ->assertSeeText(
                'Available reminder controls'
            );
    }

    public function test_reminder_recipients_have_their_own_help(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-billing.reminder-recipients.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="billing-reminder-recipients"',
                false
            )
            ->assertSeeText(
                'Reminder recipients help'
            )
            ->assertSeeText(
                'Choosing recipients'
            );
    }

    public function test_branding_has_its_own_help_context(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-branding.edit'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="branding"',
                false
            )
            ->assertSeeText(
                'Organization branding help'
            )
            ->assertDontSee(
                'data-help-context="settings"',
                false
            );
    }

    public function test_subscription_has_its_own_help_context(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-subscription.show'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="subscription"',
                false
            )
            ->assertSeeText(
                'Subscription help'
            )
            ->assertSeeText(
                'What to review'
            )
            ->assertDontSee(
                'data-help-context="settings"',
                false
            );
    }

    public function test_main_settings_retains_general_settings_help(): void
    {
        $this
            ->actingAs($this->administrator)
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="settings"',
                false
            )
            ->assertSeeText(
                'Settings help'
            )
            ->assertSeeText(
                'What you can manage'
            );
    }
}
