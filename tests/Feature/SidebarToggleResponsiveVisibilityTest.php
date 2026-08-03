<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarToggleResponsiveVisibilityTest extends TestCase
{
    public function test_sidebar_edge_toggle_is_hidden_on_mobile(): void
    {
        $navigation = file_get_contents(
            resource_path(
                'views/layouts/navigation.blade.php'
            )
        );

        $this->assertIsString($navigation);

        $this->assertStringContainsString(
            'class="hidden econsent-sidebar-edge-toggle lg:inline-flex"',
            $navigation
        );
    }

    public function test_sidebar_edge_toggle_has_mobile_and_desktop_display_rules(): void
    {
        $css = file_get_contents(
            resource_path('css/app.css')
        );

        $this->assertIsString($css);

        $this->assertMatchesRegularExpression(
            '/\.econsent-sidebar-edge-toggle\s*\{\s*display:\s*none;/s',
            $css
        );

        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\).*?'
            .'\.econsent-sidebar-edge-toggle\s*\{.*?'
            .'display:\s*inline-flex;/s',
            $css
        );
    }
}
