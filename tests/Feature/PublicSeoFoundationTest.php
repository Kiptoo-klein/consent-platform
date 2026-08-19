<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_exposes_public_seo_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertIsString($html);

        foreach ([
            'Digital Consent Management Software',
            'Digital consent management that feels',
            'https://econsent.site/',
            'property="og:type"',
            'property="og:site_name"',
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

        $this->assertMatchesRegularExpression(
            '/<link\s+'
            .'rel="canonical"\s+'
            .'href="https:\/\/econsent\.site\/"\s*>/s',
            $html
        );
    }

    public function test_homepage_structured_data_is_valid_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertIsString($html);

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

        $this->assertContains(
            'Organization',
            $types
        );

        $this->assertContains(
            'WebSite',
            $types
        );
    }

    public function test_public_sitemap_contains_canonical_homepage(): void
    {
        $sitemap = file_get_contents(
            public_path('sitemap.xml')
        );

        $this->assertIsString($sitemap);

        $this->assertStringContainsString(
            '<?xml version="1.0" encoding="UTF-8"?>',
            $sitemap
        );

        $this->assertStringContainsString(
            '<loc>https://econsent.site/</loc>',
            $sitemap
        );
    }

    public function test_robots_file_advertises_public_sitemap(): void
    {
        $robots = file_get_contents(
            public_path('robots.txt')
        );

        $this->assertIsString($robots);

        $this->assertStringContainsString(
            'User-agent: *',
            $robots
        );

        $this->assertStringContainsString(
            'Sitemap: https://econsent.site/sitemap.xml',
            $robots
        );
    }

    public function test_public_homepage_remains_indexable(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertIsString($html);

        $this->assertStringNotContainsString(
            'noindex',
            $html
        );
    }

    public function test_guest_authentication_pages_are_noindex(): void
    {
        foreach ([
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password/test-token',
        ] as $path) {
            $response = $this->get($path);

            $response->assertOk();

            $html = $response->getContent();

            $this->assertIsString($html);

            $this->assertMatchesRegularExpression(
                '/<meta\s+'
                .'name="robots"\s+'
                .'content="noindex,\s*nofollow"\s*>/s',
                $html
            );
        }
    }

    public function test_authenticated_authentication_pages_are_noindex(): void
    {
        $user = \App\Models\User::factory()
            ->unverified()
            ->create();

        foreach ([
            '/verify-email',
            '/confirm-password',
        ] as $path) {
            $response = $this
                ->actingAs($user)
                ->get($path);

            $response->assertOk();

            $html = $response->getContent();

            $this->assertIsString($html);

            $this->assertMatchesRegularExpression(
                '/<meta\s+'
                .'name="robots"\s+'
                .'content="noindex,\s*nofollow"\s*>/s',
                $html
            );
        }
    }
}
