<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicConsentFormsSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_electronic_consent_forms_page_is_public_and_indexable(): void
    {
        $response =
            $this->get(
                '/electronic-consent-forms'
            );

        $response->assertOk();

        $html =
            $response->getContent();

        $this->assertIsString(
            $html
        );

        foreach ([
            'Electronic Consent Forms | Create &amp; Sign Online',
            'Electronic consent forms',
            'https://econsent.site/electronic-consent-forms',
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
            .'href="https:\/\/econsent\.site\/electronic-consent-forms"\s*>/s',
            $html
        );
    }

    public function test_electronic_consent_forms_structured_data_is_valid(): void
    {
        $response =
            $this->get(
                '/electronic-consent-forms'
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

    public function test_existing_public_pages_link_to_electronic_consent_forms(): void
    {
        foreach ([
            '/',
            '/digital-consent-management',
        ] as $path) {
            $response =
                $this->get($path);

            $response->assertOk();

            $response->assertSee(
                '/electronic-consent-forms',
                false
            );

            $response->assertSee(
                'Consent forms'
            );
        }
    }

    public function test_electronic_consent_forms_links_to_digital_consent_guide(): void
    {
        $response =
            $this->get(
                '/electronic-consent-forms'
            );

        $response->assertOk();

        $response->assertSee(
            '/digital-consent-management',
            false
        );

        $response->assertSee(
            'digital consent management guide'
        );
    }

    public function test_public_sitemap_contains_electronic_consent_forms_page(): void
    {
        $sitemap =
            file_get_contents(
                public_path('sitemap.xml')
            );

        $this->assertIsString(
            $sitemap
        );

        foreach ([
            '<loc>https://econsent.site/</loc>',
            '<loc>https://econsent.site/digital-consent-management</loc>',
            '<loc>https://econsent.site/electronic-consent-forms</loc>',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $sitemap
            );
        }
    }
}
