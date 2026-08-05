<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageContactSectionWiringTest extends TestCase
{
    public function test_homepage_contains_contact_navigation_and_section(): void
    {
        $source = file_get_contents(
            resource_path('views/welcome.blade.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'href="#contact"',
            $source
        );

        $this->assertStringContainsString(
            'id="contact"',
            $source
        );

        $this->assertStringContainsString(
            'Contact eConsent',
            $source
        );
    }

    public function test_homepage_contact_details_are_clickable(): void
    {
        $source = file_get_contents(
            resource_path('views/welcome.blade.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'mailto:kleinluche@gmail.com',
            $source
        );

        $this->assertStringContainsString(
            'tel:+254716583388',
            $source
        );

        $this->assertStringContainsString(
            '+254 716 583 388',
            $source
        );
    }
}
