<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\ConsentTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTemplateUnpublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_template_can_be_taken_offline_without_deleting_history(): void
    {
        $organization = Organization::create([
            'name' => 'Test Organization',
            'slug' => 'test-organization',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        [$template, $version] = $this->createPublishedTemplate(
            $organization,
            $user
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'consent-templates.unpublish',
                    $template
                )
            );

        $response
            ->assertRedirect(
                route('consent-templates.manage')
            )
            ->assertSessionHas(
                'success',
                'The consent template is now offline.'
            );

        $template->refresh();

        $this->assertNull(
            $template->active_version_id
        );

        $this->assertSame(
            'draft',
            $template->status
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
    }

    public function test_user_cannot_unpublish_another_organizations_template(): void
    {
        $ownerOrganization = Organization::create([
            'name' => 'Owner Organization',
            'slug' => 'owner-organization',
        ]);

        $otherOrganization = Organization::create([
            'name' => 'Other Organization',
            'slug' => 'other-organization',
        ]);

        $owner = User::factory()->create([
            'organization_id' => $ownerOrganization->id,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'organization_id' => $otherOrganization->id,
            'is_active' => true,
        ]);

        [$template, $version] = $this->createPublishedTemplate(
            $ownerOrganization,
            $owner
        );

        $this
            ->actingAs($otherUser)
            ->post(
                route(
                    'consent-templates.unpublish',
                    $template
                )
            )
            ->assertForbidden();

        $template->refresh();

        $this->assertSame(
            $version->id,
            $template->active_version_id
        );

        $this->assertSame(
            'published',
            $template->status
        );
    }

    /**
     * @return array{ConsentTemplate, ConsentTemplateVersion}
     */
    private function createPublishedTemplate(
        Organization $organization,
        User $publisher
    ): array {
        $template = ConsentTemplate::create([
            'organization_id' => $organization->id,
            'title' => 'Test Consent',
            'description' => 'Test consent template.',
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
                'published_by' => $publisher->id,
            ]);

        $template->update([
            'active_version_id' => $version->id,
            'has_unpublished_changes' => false,
            'status' => 'published',
        ]);

        return [$template, $version];
    }
}
