<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\ConsentTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTemplateArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_template_can_be_archived_without_deleting_published_history(): void
    {
        $organization = Organization::create([
            'name' => 'Archive Test Organization',
            'slug' => 'archive-test-organization',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        [$template, $version] = $this->createPublishedTemplate(
            $organization,
            $user
        );

        /*
         * The template has published history but is currently offline.
         */
        $template->update([
            'active_version_id' => null,
            'status' => 'draft',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'consent-templates.archive',
                    $template
                )
            );

        $response
            ->assertRedirect(
                route('consent-templates.manage')
            )
            ->assertSessionHas(
                'success',
                'Consent template archived successfully.'
            );

        $template->refresh();

        $this->assertSame(
            'archived',
            $template->status
        );

        $this->assertNull(
            $template->active_version_id
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

    public function test_live_template_must_be_unpublished_before_it_can_be_archived(): void
    {
        $organization = Organization::create([
            'name' => 'Live Template Organization',
            'slug' => 'live-template-organization',
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
            ->from(route('consent-templates.manage'))
            ->post(
                route(
                    'consent-templates.archive',
                    $template
                )
            );

        $response
            ->assertRedirect(
                route('consent-templates.manage')
            )
            ->assertSessionHasErrors('template');

        $template->refresh();

        $this->assertSame(
            'published',
            $template->status
        );

        $this->assertSame(
            $version->id,
            $template->active_version_id
        );

        $this->assertDatabaseHas(
            'consent_template_versions',
            [
                'id' => $version->id,
            ]
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
            'title' => 'Template to Archive',
            'description' => 'Archive test template.',
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
            'status' => 'published',
        ]);

        return [$template, $version];
    }
}
