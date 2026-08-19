<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentTemplateStyledConfirmationTest extends TestCase
{
    public function test_confirmation_component_uses_a_normal_submit_button(): void
    {
        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'action-confirmation-modal.blade.php'
            )
        );

        $this->assertIsString($component);

        $this->assertStringContainsString(
            'fixed inset-0 z-[100]',
            $component
        );

        $this->assertStringContainsString(
            'items-center justify-center',
            $component
        );

        $this->assertStringContainsString(
            'max-w-md',
            $component
        );

        $this->assertStringNotContainsString(
            '<x-modal',
            $component
        );

        $this->assertStringContainsString(
            'type="submit"',
            $component
        );

        $this->assertStringContainsString(
            'data-action-confirmation-submit',
            $component
        );

        $this->assertStringNotContainsString(
            'requestSubmit',
            $component
        );

        $this->assertStringNotContainsString(
            'HTMLFormElement',
            $component
        );
    }

    public function test_manage_page_uses_explicit_publish_and_unpublish_modals(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-templates/'
                .'manage.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-template-publish-confirmation',
            'data-template-unpublish-confirmation',
            'publish-template-{{ $consentTemplate->id }}',
            'unpublish-template-{{ $consentTemplate->id }}',
            '<x-action-confirmation-modal',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('{{ \$consentTemplate->has_unpublished_changes",
            $view
        );

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Take this consent template offline?",
            $view
        );
    }

    public function test_more_dropdown_owns_outside_click_instead_of_the_action_button(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-templates/'
                .'manage.blade.php'
            )
        );

        $this->assertIsString($view);

        $this->assertMatchesRegularExpression(
            '/<div\s+'
            .'class="relative w-24"\s+'
            .'x-on:click\.outside="open = false"\s+'
            .'x-on:keydown\.escape\.window="open = false"\s*>/s',
            $view
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<button\s+'
            .'type="button"\s+'
            .'x-on:click="open = ! open"\s+'
            .'x-on:click\.outside="open = false"/s',
            $view
        );
    }

    public function test_preview_publish_uses_the_explicit_styled_modal(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-templates/'
                .'preview.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-preview-publish-confirmation',
            'preview-publish-template-{{ $consentTemplate->id }}',
            'Publish Version {{ $nextVersionNumber }}?',
            '<x-action-confirmation-modal',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Publish this working copy",
            $view
        );
    }
}
