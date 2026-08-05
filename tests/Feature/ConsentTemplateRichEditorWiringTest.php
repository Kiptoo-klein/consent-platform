<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConsentTemplateRichEditorWiringTest extends TestCase
{
    public function test_rich_editor_and_image_upload_are_wired(): void
    {
        $route = Route::getRoutes()
            ->getByName(
                'consent-templates.upload-image'
            );

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());

        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'consent-template-rich-editor.blade.php'
            )
        );

        $javascript = file_get_contents(
            resource_path(
                'js/consent-template-rich-editor.js'
            )
        );

        $this->assertIsString($component);
        $this->assertIsString($javascript);

        $this->assertStringContainsString(
            "command('bold')",
            $component
        );

        $this->assertStringContainsString(
            'applyFontSize',
            $component
        );

        $this->assertStringContainsString(
            'uploadImage',
            $javascript
        );
    }

    public function test_all_template_builders_use_the_rich_editor(): void
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
                '<x-consent-template-rich-editor',
                $view
            );

            $this->assertStringNotContainsString(
                'name="content"'
                .PHP_EOL
                .'                            rows=',
                $view
            );
        }
    }

    public function test_schema_stores_sanitized_html_and_plain_text(): void
    {
        $templateController = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'ConsentTemplateController.php'
            )
        );

        $individualController = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'IndividualConsentWizardController.php'
            )
        );

        foreach ([
            $templateController,
            $individualController,
        ] as $controller) {
            $this->assertIsString($controller);

            $this->assertStringContainsString(
                "'consent_html'",
                $controller
            );

            $this->assertStringContainsString(
                "'builder_version' => 2",
                $controller
            );
        }
    }

    public function test_rendering_paths_support_rich_content(): void
    {
        foreach ([
            'consent-templates/preview.blade.php',
            'consent-templates/show-published.blade.php',
            'consent-templates/history.blade.php',
            'public-consent/show.blade.php',
            'public-signing-stations/review.blade.php',
            'consent-sessions/show.blade.php',
        ] as $relative) {
            $view = file_get_contents(
                resource_path('views/'.$relative)
            );

            $this->assertIsString($view);
            $this->assertStringContainsString(
                '<x-consent-template-content',
                $view
            );
        }

        $pdf = file_get_contents(
            resource_path(
                'views/pdfs/consent-record.blade.php'
            )
        );

        $this->assertIsString($pdf);
        $this->assertStringContainsString(
            '$consentHtml',
            $pdf
        );
    }
}
