<?php

namespace App\Providers;

use App\Services\PlatformBrandingSettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(
        PlatformBrandingSettingsService $brandingService
    ): void {
        $platformBrand =
            $brandingService->viewData();

        config([
            'app.name' =>
                $platformBrand[
                    'platform_name'
                ],
        ]);

        View::share(
            'platformBrand',
            $platformBrand
        );
    }
}
