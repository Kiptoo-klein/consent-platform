<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTemplateRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_template_can_be_restored_as_an_offline_draft(): void
    {
        $organization = Organization::create([
            'name' => 'Restore Test Organization',
            'slug' => 'restore-test-organization',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $template = ConsentTemplate::create([
            'organization_id' => $organization->id,
            'title' => 'Archived Template',
            'description' => 'Template restore test.',
            'category' => 'Testing',
            'usage_type' => ConsentTemplate::USAGE_BOTH,
            'template_schema' => [
                'sections' => [],
            ],
            'active_version_id' => null,
            'has_unpublished_changes' => false,
            'status' => 'draft',
        ]);

        $version = $template
            ->versions()
            ->create([
                'version_number' => 1,
                'title' => $template->title,
                'description' => $template->description,
                'template_schema' => $template->template_schema,
                'published_at' => now(),
                'published_by' => $user->id,
            ]);

        $template->update([
            'active_version_id' => null,
            'status' => 'archived',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(
                route(
                    'consent-templates.restore',
                    $template
                )
            );

        $response
            ->assertRedirect(
                route('consent-templates.manage')
            )
            ->assertSessionHas(
                'success',
                'Consent template restored successfully.'
            );

        $template->refresh();

        $this->assertSame(
            'draft',
            $template->status
        );

        $this->assertNull(
            $template->active_version_id
        );

        $this->assertFalse(
            $template->has_unpublished_changes
        );

        $this->assertDatabaseHas(
            'consent_template_versions',
            [
                'id' => $version->id,
                'consent_template_id' => $template->id,
                'version_number' => 1,
            ]
        );

        $this->assertSame(
            1,
            $template->versions()->count()
        );
    }
}
