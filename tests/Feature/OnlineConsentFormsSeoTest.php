<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineConsentFormsSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_consent_forms_page_is_public_and_indexable(): void
    {
        $response =
            $this->get(
                '/online-consent-forms'
            );

        $response->assertOk();

        $html =
            $response->getContent();

        $this->assertIsString(
            $html
        );

        foreach ([
            'Online Consent Forms | Collect Consent Online',
            'Online consent forms',
            'https://econsent.site/online-consent-forms',
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
            .'href="https:\/\/econsent\.site\/online-consent-forms"\s*>/s',
            $html
        );
    }

    public function test_online_consent_forms_structured_data_is_valid(): void
    {
        $response =
            $this->get(
                '/online-consent-forms'
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

    public function test_electronic_consent_forms_links_to_online_consent_forms(): void
    {
        $response =
            $this->get(
                '/electronic-consent-forms'
            );

        $response->assertOk();

        $response->assertSee(
            '/online-consent-forms',
            false
        );

        $response->assertSee(
            'Explore online consent forms.'
        );
    }

    public function test_online_consent_forms_links_to_related_guides(): void
    {
        $response =
            $this->get(
                '/online-consent-forms'
            );

        $response->assertOk();

        $response->assertSee(
            '/electronic-consent-forms',
            false
        );

        $response->assertSee(
            '/digital-consent-management',
            false
        );

        $response->assertSee(
            'Read about electronic consent forms.'
        );

        $response->assertSee(
            'Read the digital consent management guide.'
        );
    }

    public function test_public_sitemap_contains_online_consent_forms_page(): void
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
            '<loc>https://econsent.site/online-consent-forms</loc>',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $sitemap
            );
        }
    }
}
