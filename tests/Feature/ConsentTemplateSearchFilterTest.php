<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTemplateSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_templates_can_be_searched_by_title_description_or_category(): void
    {
        [$organization, $user] = $this->createOrganizationUser();

        $titleMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Patient Photography Consent',
            description: 'Standard consent form.',
            category: 'General'
        );

        $descriptionMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Medical Treatment',
            description: 'Includes photography permission.',
            category: 'Clinical'
        );

        $categoryMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Research Participation',
            description: 'Research agreement.',
            category: 'Photography'
        );

        $nonMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Data Processing',
            description: 'Privacy consent.',
            category: 'Compliance'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('consent-templates.manage', [
                    'search' => 'photography',
                ])
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'consentTemplates',
                function ($templates) use (
                    $titleMatch,
                    $descriptionMatch,
                    $categoryMatch,
                    $nonMatch
                ): bool {
                    $ids = $templates
                        ->pluck('id')
                        ->all();

                    return in_array($titleMatch->id, $ids, true)
                        && in_array($descriptionMatch->id, $ids, true)
                        && in_array($categoryMatch->id, $ids, true)
                        && ! in_array($nonMatch->id, $ids, true);
                }
            )
            ->assertViewHas('search', 'photography')
            ->assertViewHas('filter', 'all');
    }

    public function test_templates_can_be_filtered_by_live_and_unpublished_status(): void
    {
        [$organization, $user] = $this->createOrganizationUser();

        $liveTemplate = $this->createTemplate(
            $organization,
            $user,
            title: 'Live Template'
        );

        $this->publishTemplate(
            $liveTemplate,
            $user
        );

        $liveChangedTemplate = $this->createTemplate(
            $organization,
            $user,
            title: 'Live Template With Changes'
        );

        $this->publishTemplate(
            $liveChangedTemplate,
            $user
        );

        $liveChangedTemplate->update([
            'has_unpublished_changes' => true,
        ]);

        $offlineTemplate = $this->createTemplate(
            $organization,
            $user,
            title: 'Offline Template',
            hasUnpublishedChanges: false
        );

        $changedTemplate = $this->createTemplate(
            $organization,
            $user,
            title: 'Changed Template',
            hasUnpublishedChanges: true
        );

        $this
            ->actingAs($user)
            ->get(
                route('consent-templates.manage', [
                    'filter' => 'live',
                ])
            )
            ->assertOk()
            ->assertViewHas(
                'consentTemplates',
                function ($templates) use (
                    $liveTemplate,
                    $liveChangedTemplate
                ): bool {
                    $ids = $templates->pluck('id')->all();

                    return count($ids) === 1
                        && in_array($liveTemplate->id, $ids, true)
                        && ! in_array(
                            $liveChangedTemplate->id,
                            $ids,
                            true
                        );
                }
            );

        $this
            ->actingAs($user)
            ->get(
                route('consent-templates.manage', [
                    'filter' => 'unpublished',
                ])
            )
            ->assertOk()
            ->assertViewHas(
                'consentTemplates',
                function ($templates) use (
                    $offlineTemplate,
                    $changedTemplate,
                    $liveChangedTemplate,
                    $liveTemplate
                ): bool {
                    $ids = $templates->pluck('id')->all();

                    return in_array(
                        $offlineTemplate->id,
                        $ids,
                        true
                    )
                        && in_array(
                            $changedTemplate->id,
                            $ids,
                            true
                        )
                        && in_array(
                            $liveChangedTemplate->id,
                            $ids,
                            true
                        )
                        && ! in_array(
                            $liveTemplate->id,
                            $ids,
                            true
                        );
                }
            );
    }

    public function test_archived_templates_have_separate_search_results(): void
    {
        [$organization, $user] = $this->createOrganizationUser();

        $archivedMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Archived Photography Consent',
            status: 'archived'
        );

        $activeMatch = $this->createTemplate(
            $organization,
            $user,
            title: 'Active Photography Consent'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('consent-templates.archived', [
                    'search' => 'photography',
                ])
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'consentTemplates',
                function ($templates) use (
                    $archivedMatch,
                    $activeMatch
                ): bool {
                    $ids = $templates
                        ->pluck('id')
                        ->all();

                    return in_array($archivedMatch->id, $ids, true)
                        && ! in_array($activeMatch->id, $ids, true);
                }
            )
            ->assertViewHas('showingArchived', true)
            ->assertViewHas('search', 'photography');
    }

    public function test_consent_template_index_opens_with_search_and_filter_defaults(): void
    {
        [$organization, $user] = $this->createOrganizationUser();

        $this->createTemplate(
            $organization,
            $user,
            title: 'Index Route Template'
        );

        $this
            ->actingAs($user)
            ->get(route('consent-templates.index'))
            ->assertOk()
            ->assertViewIs('consent-templates.manage')
            ->assertViewHas('search', '')
            ->assertViewHas('filter', 'all');
    }

    /**
     * @return array{Organization, User}
     */
    private function createOrganizationUser(): array
    {
        $organization = Organization::create([
            'name' => 'Template Search Organization',
            'slug' => 'template-search-organization',
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $this->enablePaidOrganizationAccess(
            $organization,
            $user
        );

        return [$organization, $user];
    }

    private function createTemplate(
        Organization $organization,
        User $user,
        string $title,
        string $description = 'Template description.',
        string $category = 'Testing',
        bool $hasUnpublishedChanges = false,
        string $status = 'draft'
    ): ConsentTemplate {
        return ConsentTemplate::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'usage_type' => ConsentTemplate::USAGE_BOTH,
            'template_schema' => [
                'sections' => [],
            ],
            'active_version_id' => null,
            'has_unpublished_changes' =>
                $hasUnpublishedChanges,
            'status' => $status,
        ]);
    }

    private function publishTemplate(
        ConsentTemplate $template,
        User $publisher
    ): void {
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
    }
}
