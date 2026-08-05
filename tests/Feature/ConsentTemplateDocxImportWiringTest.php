<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConsentTemplateDocxImportWiringTest extends TestCase
{
    public function test_docx_import_route_is_registered(): void
    {
        $route = Route::getRoutes()
            ->getByName(
                'consent-templates.import-docx'
            );

        $this->assertNotNull(
            $route
        );

        $this->assertContains(
            'POST',
            $route->methods()
        );
    }

    public function test_reusable_word_import_component_exists(): void
    {
        $componentPath = resource_path(
            'views/components/'
            .'consent-template-docx-import.blade.php'
        );

        $this->assertFileExists(
            $componentPath
        );

        $component = file_get_contents(
            $componentPath
        );

        $this->assertIsString(
            $component
        );

        $this->assertStringContainsString(
            'Drop a Word document here',
            $component
        );

        $this->assertStringContainsString(
            "consent-templates.import-docx",
            $component
        );

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $component
        );
    }

    public function test_standard_and_individual_builders_include_importer(): void
    {
        $standard = file_get_contents(
            resource_path(
                'views/consent-templates/create.blade.php'
            )
        );

        $individual = file_get_contents(
            resource_path(
                'views/consent-templates/'
                .'create-individual-wizard.blade.php'
            )
        );

        $this->assertIsString(
            $standard
        );

        $this->assertIsString(
            $individual
        );

        $this->assertStringContainsString(
            '<x-consent-template-docx-import />',
            $standard
        );

        $this->assertStringContainsString(
            '<x-consent-template-docx-import />',
            $individual
        );
    }

    public function test_bulk_workflow_shows_direct_word_import_option(): void
    {
        $bulk = file_get_contents(
            resource_path(
                'views/consent-campaigns/'
                .'select-template.blade.php'
            )
        );

        $this->assertIsString(
            $bulk
        );

        $this->assertStringContainsString(
            'Import Word Template',
            $bulk
        );

        $this->assertStringContainsString(
            "#word-import",
            $bulk
        );

        $this->assertStringContainsString(
            "'return_to' => 'bulk'",
            $bulk
        );
    }

    public function test_import_controller_limits_and_audits_uploads(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'ConsentTemplateDocxImportController.php'
            )
        );

        $this->assertIsString(
            $controller
        );

        $this->assertStringContainsString(
            "'max:10240'",
            $controller
        );

        $this->assertStringContainsString(
            'consent_template.docx_imported',
            $controller
        );

        $this->assertStringContainsString(
            "!== 'docx'",
            $controller
        );
    }
}
