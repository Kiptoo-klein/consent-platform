<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppLayoutBodyMarkupTest extends TestCase
{
    public function test_app_layout_body_tag_has_no_stray_greater_than_character(): void
    {
        $layout = file_get_contents(
            resource_path(
                'views/layouts/app.blade.php'
            )
        );

        $this->assertIsString($layout);

        $this->assertStringContainsString(
            'data-path="{{ request()->path() }}">',
            $layout
        );

        $this->assertStringNotContainsString(
            'data-path="{{ request()->path() }}">>',
            $layout
        );
    }
}
