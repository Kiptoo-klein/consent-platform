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
        /*
         * Laravel Cloud invokes schedule:run every minute.
         * That scheduler tick does not render application views
         * and does not need platform branding. Avoid boot-time
         * database/cache and object-storage work for that tick.
         */
        if (
            $this->app->runningInConsole()
            && in_array(
                $_SERVER['argv'][1] ?? null,
                [
                    'schedule:run',
                    'schedule:work',
                ],
                true
            )
        ) {
            return;
        }

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
