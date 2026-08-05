<?php

namespace Tests\Feature;

use App\Services\ConsentTemplateAssetService;
use App\Services\ConsentTemplateContentService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConsentTemplateContentServiceTest extends TestCase
{
    public function test_it_preserves_safe_formatting_and_removes_active_content(): void
    {
        Storage::fake('public');

        config()->set(
            'consent-template-assets.disk',
            'public'
        );

        $service = new ConsentTemplateContentService(
            new ConsentTemplateAssetService()
        );

        $prepared = $service->prepare(
            '<h2 style="font-size: 24px">Heading</h2>'
            .'<p><strong>Bold</strong> '
            .'<em>Italic</em></p>'
            .'<script>alert(1)</script>'
            .'<a href="javascript:alert(1)">Unsafe</a>',
            41
        );

        $this->assertStringContainsString(
            '<strong>Bold</strong>',
            $prepared['html']
        );

        $this->assertStringContainsString(
            'font-size: 24px',
            $prepared['html']
        );

        $this->assertStringNotContainsString(
            '<script',
            $prepared['html']
        );

        $this->assertStringNotContainsString(
            'javascript:',
            $prepared['html']
        );

        $this->assertStringContainsString(
            'Heading',
            $prepared['text']
        );
    }

    public function test_external_or_cross_organization_images_are_removed(): void
    {
        Storage::fake('public');

        config()->set(
            'consent-template-assets.disk',
            'public'
        );

        $service = new ConsentTemplateContentService(
            new ConsentTemplateAssetService()
        );

        $prepared = $service->prepare(
            '<p>Document</p>'
            .'<img src="https://example.com/image.png">'
            .'<img '
            .'data-consent-asset-disk="public" '
            .'data-consent-asset-path="'
            .'consent-template-assets/99/'
            .'11111111-1111-4111-8111-111111111111.png" '
            .'src="/storage/image.png">',
            41
        );

        $this->assertStringNotContainsString(
            '<img',
            $prepared['html']
        );
    }

    public function test_same_organization_image_gets_a_canonical_storage_url(): void
    {
        Storage::fake('public');

        config()->set(
            'consent-template-assets.disk',
            'public'
        );

        $path =
            'consent-template-assets/41/'
            .'11111111-1111-4111-8111-111111111111.png';

        Storage::disk('public')->put(
            $path,
            'stored-image-bytes'
        );

        $service = new ConsentTemplateContentService(
            new ConsentTemplateAssetService()
        );

        $prepared = $service->prepare(
            '<p>Document</p><img '
            .'data-consent-asset-disk="public" '
            .'data-consent-asset-path="'.$path.'" '
            .'src="https://attacker.example/image.png">',
            41
        );

        $this->assertStringContainsString(
            Storage::disk('public')->url($path),
            $prepared['html']
        );

        $this->assertStringNotContainsString(
            'attacker.example',
            $prepared['html']
        );
    }
}
