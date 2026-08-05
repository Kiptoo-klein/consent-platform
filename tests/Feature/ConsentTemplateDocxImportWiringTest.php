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

        $this->assertNotNull($route);
        $this->assertContains(
            'POST',
            $route->methods()
        );
    }

    public function test_reusable_word_import_component_exists(): void
    {
        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'consent-template-docx-import.blade.php'
            )
        );

        $this->assertIsString($component);

        $this->assertStringContainsString(
            'Drop a Word document here',
            $component
        );

        $this->assertStringContainsString(
            'econsent-set-rich-content',
            $component
        );

        $this->assertStringContainsString(
            'embedded JPG',
            $component
        );
    }

    public function test_all_template_builders_include_importer(): void
    {
        foreach ([
            'create.blade.php',
            'create-individual-wizard.blade.php',
            'edit.blade.php',
        ] as $filename) {
            $view = file_get_contents(
                resource_path(
                    'views/consent-templates/'
                    .$filename
                )
            );

            $this->assertIsString($view);

            $this->assertStringContainsString(
                '<x-consent-template-docx-import />',
                $view
            );
        }
    }

    public function test_import_controller_persists_formatted_content(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'ConsentTemplateDocxImportController.php'
            )
        );

        $extractor = file_get_contents(
            app_path(
                'Services/'
                .'ConsentTemplateDocxTextExtractor.php'
            )
        );

        $this->assertIsString($controller);
        $this->assertIsString($extractor);

        $this->assertStringContainsString(
            'prepareImportedHtml',
            $controller
        );

        $this->assertStringContainsString(
            "'image_count'",
            $controller
        );

        $this->assertStringContainsString(
            "'HTML'",
            $extractor
        );
    }
}
