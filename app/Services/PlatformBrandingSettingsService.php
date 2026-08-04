<?php

namespace App\Services;

use App\Models\PlatformBrandingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PlatformBrandingSettingsService
{
    public const CACHE_KEY =
        'platform_branding_settings';

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        /*
         * Laravel Cloud runs Composer package discovery while building the
         * image. The production database may not be attached or reachable
         * during that phase, so branding must safely use built-in defaults.
         */
        try {
            $brandingTableExists =
                Schema::hasTable(
                    'platform_branding_settings'
                );
        } catch (\Throwable) {
            return $this->defaults();
        }

        if (! $brandingTableExists) {
            return $this->defaults();
        }

        return Cache::rememberForever(
            self::CACHE_KEY,
            function (): array {
                $setting =
                    PlatformBrandingSetting::query()
                        ->where(
                            'singleton_key',
                            PlatformBrandingSetting::
                                SINGLETON_KEY
                        )
                        ->first();

                if ($setting === null) {
                    return $this->defaults();
                }

                return [
                    'platform_name' =>
                        $setting->platform_name,

                    'short_name' =>
                        $setting->short_name,

                    'tagline' =>
                        $setting->tagline,

                    'description' =>
                        $setting->description,

                    'logo_path' =>
                        $setting->logo_path,

                    'favicon_path' =>
                        $setting->favicon_path,

                    'primary_color' =>
                        $setting->primary_color,

                    'accent_color' =>
                        $setting->accent_color,

                    'pdf_primary_color' =>
                        $setting->pdf_primary_color,

                    'support_email' =>
                        $setting->support_email,

                    'website_url' =>
                        $setting->website_url,

                    'footer_text' =>
                        $setting->footer_text,
                ];
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $settings =
            $this->settings();

        $settings['logo_url'] =
            $this->publicUrl(
                $settings['logo_path']
                ?? null
            );

        $settings['favicon_url'] =
            $this->publicUrl(
                $settings['favicon_path']
                ?? null
            )
            ?? asset('favicon.svg');

        return $settings;
    }

    public function logoDataUri(): ?string
    {
        $path =
            $this->settings()[
                'logo_path'
            ] ?? null;

        if (
            ! is_string($path)
            || $path === ''
        ) {
            return null;
        }

        try {
            $disk =
                Storage::disk($this->brandingDisk());

            if (! $disk->exists($path)) {
                return null;
            }

            $mimeType =
                $disk->mimeType($path)
                ?? 'image/png';

            if (
                ! in_array(
                    $mimeType,
                    [
                        'image/png',
                        'image/jpeg',
                    ],
                    true
                )
            ) {
                return null;
            }

            return 'data:'
                .$mimeType
                .';base64,'
                .base64_encode(
                    $disk->get($path)
                );
        } catch (Throwable) {
            return null;
        }
    }

    public function forgetCache(): void
    {
        Cache::forget(
            self::CACHE_KEY
        );
    }

    private function publicUrl(
        mixed $path
    ): ?string {
        if (
            ! is_string($path)
            || trim($path) === ''
        ) {
            return null;
        }

        try {
            $disk =
                Storage::disk($this->brandingDisk());

            if (! $disk->exists($path)) {
                return null;
            }

            $diskName =
                $this->brandingDisk();

            $url =
                $diskName === 'public'
                    ? '/storage/'
                        .ltrim(
                            $path,
                            '/'
                        )
                    : $disk->url(
                        $path
                    );

            try {
                $version =
                    $disk->lastModified($path);

                return $url
                    .(
                        str_contains(
                            $url,
                            '?'
                        )
                            ? '&v='
                            : '?v='
                    )
                    .$version;
            } catch (Throwable) {
                return $url;
            }
        } catch (Throwable) {
            return null;
        }
    }

    private function brandingDisk(): string
    {
        return (string) config(
            'platform-branding.disk',
            'public'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'platform_name' =>
                config(
                    'ui-brand.name',
                    'eConsent'
                ),

            'short_name' =>
                'eC',

            'tagline' =>
                config(
                    'ui-brand.tagline',
                    'Secure consent. Simple workflows. Better records.'
                ),

            'description' =>
                config(
                    'ui-brand.description',
                    'Create, collect, sign and securely manage digital consent records.'
                ),

            'logo_path' =>
                null,

            'favicon_path' =>
                null,

            'primary_color' =>
                '#312E81',

            'accent_color' =>
                '#4F46E5',

            'pdf_primary_color' =>
                '#312E81',

            'support_email' =>
                null,

            'website_url' =>
                null,

            'footer_text' =>
                'Secure digital consent management.',
        ];
    }
}
