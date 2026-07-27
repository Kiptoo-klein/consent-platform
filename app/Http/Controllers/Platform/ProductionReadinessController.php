<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\ProductionReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProductionReadinessController extends Controller
{
    public function index(
        ProductionReadinessService $service
    ): View {
        $checks = $service->checks();

        return view(
            'platform.production-readiness.index',
            [
                'checks' => $checks,
                'summary' =>
                    $service->summary(
                        $checks
                    ),

                'metrics' =>
                    $service->runtimeMetrics(),

                'latestBackup' =>
                    $service->latestBackup(),

                'maintenanceMode' =>
                    app()->isDownForMaintenance(),

                'formatter' => $service,
            ]
        );
    }

    public function createBackup(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call(
                'production:backup',
                [
                    '--prune' => true,
                ]
            );

            if ($exitCode !== 0) {
                return back()->withErrors([
                    'production_action' =>
                        trim(
                            Artisan::output()
                        )
                        ?: 'The backup command failed.',
                ]);
            }

            return back()->with(
                'success',
                trim(
                    Artisan::output()
                )
                ?: 'Backup created successfully.'
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'production_action' =>
                    'Backup failed: '
                    .$exception->getMessage(),
            ]);
        }
    }

    public function prune(): RedirectResponse
    {
        Artisan::call(
            'production:prune'
        );

        return back()->with(
            'success',
            trim(
                Artisan::output()
            )
            ?: 'Retention cleanup completed.'
        );
    }

    public function optimize(): RedirectResponse
    {
        try {
            Artisan::call(
                'optimize'
            );

            return back()->with(
                'success',
                'Configuration, routes, events and views were optimized.'
            );
        } catch (Throwable $exception) {
            Artisan::call(
                'optimize:clear'
            );

            return back()->withErrors([
                'production_action' =>
                    'Optimization failed and caches were cleared: '
                    .$exception->getMessage(),
            ]);
        }
    }

    public function restartQueue(): RedirectResponse
    {
        Artisan::call(
            'queue:restart'
        );

        return back()->with(
            'success',
            'Queue workers received a graceful restart signal.'
        );
    }

    public function enableMaintenance(
        Request $request
    ): View|RedirectResponse {
        $validated = $request->validate([
            'confirmation' => [
                'required',
                'in:MAINTENANCE',
            ],
        ]);

        $secret = Str::random(48);

        try {
            Artisan::call(
                'down',
                [
                    '--retry' => 60,
                    '--refresh' => 15,
                    '--secret' => $secret,
                ]
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'production_action' =>
                    'Maintenance mode could not be enabled: '
                    .$exception->getMessage(),
            ]);
        }

        return view(
            'platform.production-readiness.maintenance-enabled',
            [
                'bypassUrl' =>
                    rtrim(
                        $request->getSchemeAndHttpHost(),
                        '/'
                    )
                    .'/'
                    .$secret,
            ]
        );
    }

    public function disableMaintenance(): RedirectResponse
    {
        Artisan::call('up');

        return redirect()
            ->route(
                'platform.production-readiness.index'
            )
            ->with(
                'success',
                'Maintenance mode was disabled.'
            );
    }
}
