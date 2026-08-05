<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentTemplateEditorImageUrlTest extends TestCase
{
    public function test_editor_prefers_the_server_provided_image_url(): void
    {
        $script = file_get_contents(
            resource_path(
                'js/consent-template-rich-editor.js'
            )
        );

        $this->assertIsString($script);

        $this->assertStringContainsString(
            "const returnedUrl = String(",
            $script
        );

        $this->assertStringContainsString(
            "data.url || ''",
            $script
        );

        $this->assertStringContainsString(
            'const fallbackUrl =',
            $script
        );

        $this->assertStringContainsString(
            'returnedUrl || fallbackUrl',
            $script
        );

        $this->assertStringContainsString(
            'The uploaded image URL was not returned.',
            $script
        );
    }
}
