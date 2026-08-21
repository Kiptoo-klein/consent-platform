<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalConsentManagementSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_consent_management_page_is_public_and_indexable(): void
    {
        $response =
            $this->get(
                '/digital-consent-management'
            );

        $response->assertOk();

        $html =
            $response->getContent();

        $this->assertIsString(
            $html
        );

        foreach ([
            'What Is Digital Consent Management?',
            'Digital consent management guide',
            'https://econsent.site/digital-consent-management',
            'property="og:title"',
            'property="og:description"',
            'property="og:url"',
            'name="twitter:card"',
            'name="twitter:title"',
            'name="twitter:description"',
            'type="application/ld+json"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $html
            );
        }

        $this->assertStringNotContainsString(
            'noindex',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<link\s+'
            .'rel="canonical"\s+'
            .'href="https:\/\/econsent\.site\/digital-consent-management"\s*>/s',
            $html
        );
    }

    public function test_digital_consent_management_structured_data_is_valid(): void
    {
        $response =
            $this->get(
                '/digital-consent-management'
            );

        $response->assertOk();

        $html =
            $response->getContent();

        $this->assertIsString(
            $html
        );

        $matched = preg_match(
            '/<script type="application\/ld\+json">'
            .'(.*?)'
            .'<\/script>/s',
            $html,
            $matches
        );

        $this->assertSame(
            1,
            $matched
        );

        $structuredData = json_decode(
            $matches[1],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'https://schema.org',
            $structuredData['@context']
        );

        $types = array_column(
            $structuredData['@graph'],
            '@type'
        );

        foreach ([
            'Organization',
            'WebSite',
            'WebPage',
            'BreadcrumbList',
        ] as $expectedType) {
            $this->assertContains(
                $expectedType,
                $types
            );
        }
    }

    public function test_homepage_links_to_digital_consent_management_page(): void
    {
        $response =
            $this->get('/');

        $response->assertOk();

        $response->assertSee(
            '/digital-consent-management',
            false
        );

        $response->assertSee(
            'Digital consent'
        );
    }

    public function test_public_sitemap_contains_digital_consent_management_page(): void
    {
        $sitemap =
            file_get_contents(
                public_path('sitemap.xml')
            );

        $this->assertIsString(
            $sitemap
        );

        $this->assertStringContainsString(
            '<loc>https://econsent.site/</loc>',
            $sitemap
        );

        $this->assertStringContainsString(
            '<loc>https://econsent.site/digital-consent-management</loc>',
            $sitemap
        );
    }
}
