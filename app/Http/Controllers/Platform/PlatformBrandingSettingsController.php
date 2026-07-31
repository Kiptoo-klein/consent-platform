<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformBrandingSetting;
use App\Services\ActivityLogger;
use App\Services\PlatformBrandingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PlatformBrandingSettingsController extends Controller
{
    private const SECTIONS = [
        'identity',
        'assets',
        'colors',
        'contact',

        'logo',
        'favicon',];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(
        Request $request,
        PlatformBrandingSettingsService $settingsService
    ): View {
        $this->ensureSuperAdmin(
            $request
        );

        return view(
            'platform.platform-branding-settings.index',
            [
                'settings' =>
                    $settingsService->settings(),

                'branding' =>
                    $settingsService->viewData(),
            ]
        );
    }

    public function update(
        Request $request,
        PlatformBrandingSettingsService $settingsService
    ): RedirectResponse {
        $this->ensureSuperAdmin(
            $request
        );

        $section =
            $request->validate([
                'section' => [
                    'required',
                    'string',
                    Rule::in(
                        self::SECTIONS
                    ),
                ],
            ])['section'];

        $oldSettings =
            $settingsService->settings();

        $changes = [];
        $newLogoPath = null;
        $newFaviconPath = null;

        try {
            if ($section === 'identity') {
                $validated =
                    $request->validate([
                        'platform_name' => [
                            'required',
                            'string',
                            'max:120',
                        ],

                        'short_name' => [
                            'required',
                            'string',
                            'max:10',
                        ],

                        'tagline' => [
                            'nullable',
                            'string',
                            'max:255',
                        ],

                        'description' => [
                            'nullable',
                            'string',
                            'max:2000',
                        ],
                    ]);

                $changes = [
                    'platform_name' =>
                        trim(
                            $validated[
                                'platform_name'
                            ]
                        ),

                    'short_name' =>
                        trim(
                            $validated[
                                'short_name'
                            ]
                        ),

                    'tagline' =>
                        $this->nullableText(
                            $validated[
                                'tagline'
                            ] ?? null
                        ),

                    'description' =>
                        $this->nullableText(
                            $validated[
                                'description'
                            ] ?? null
                        ),
                ];
            }

            if ($section === 'logo') {
                $request->validate([
                    'logo' => [
                        'nullable',
                        'image',
                        'mimes:jpg,jpeg,png,webp',
                        'max:5120',
                    ],

                    'remove_logo' => [
                        'nullable',
                        'boolean',
                    ],
                ], [
                    'logo.image' =>
                        'Choose a valid PNG, JPEG, or WebP image.',

                    'logo.mimes' =>
                        'The logo must be a PNG, JPEG, or WebP image.',

                    'logo.max' =>
                        'The logo must not be larger than 5 MB.',
                ]);

                if (
                    ! $request->hasFile('logo')
                    && ! $request->boolean(
                        'remove_logo'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'logo' =>
                            'Choose a logo image or select '
                            .'Remove current logo.',
                    ]);
                }

                if ($request->hasFile('logo')) {
                    $newLogoPath =
                        $request
                            ->file('logo')
                            ->store(
                                'platform-branding/logos',
                                'public'
                            );

                    if (
                        ! is_string($newLogoPath)
                        || $newLogoPath === ''
                        || ! Storage::disk('public')
                            ->exists($newLogoPath)
                    ) {
                        throw ValidationException::withMessages([
                            'logo' =>
                                'The uploaded logo could not be stored. '
                                .'Check public storage and try again.',
                        ]);
                    }

                    $changes = [
                        'logo_path' =>
                            $newLogoPath,
                    ];
                } else {
                    $changes = [
                        'logo_path' =>
                            null,
                    ];
                }
            }

            if ($section === 'favicon') {
                $request->validate([
                    'favicon' => [
                        'nullable',
                        'image',
                        'mimes:png',
                        'max:1024',
                    ],

                    'remove_favicon' => [
                        'nullable',
                        'boolean',
                    ],
                ], [
                    'favicon.image' =>
                        'Choose a valid square PNG image.',

                    'favicon.mimes' =>
                        'The favicon must be a PNG image.',

                    'favicon.max' =>
                        'The favicon must not be larger than 1 MB.',
                ]);

                if (
                    ! $request->hasFile('favicon')
                    && ! $request->boolean(
                        'remove_favicon'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'favicon' =>
                            'Choose a favicon image or select '
                            .'Remove current favicon.',
                    ]);
                }

                if ($request->hasFile('favicon')) {
                    $newFaviconPath =
                        $request
                            ->file('favicon')
                            ->store(
                                'platform-branding/favicons',
                                'public'
                            );

                    if (
                        ! is_string($newFaviconPath)
                        || $newFaviconPath === ''
                        || ! Storage::disk('public')
                            ->exists($newFaviconPath)
                    ) {
                        throw ValidationException::withMessages([
                            'favicon' =>
                                'The uploaded favicon could not be stored. '
                                .'Check public storage and try again.',
                        ]);
                    }

                    $changes = [
                        'favicon_path' =>
                            $newFaviconPath,
                    ];
                } else {
                    $changes = [
                        'favicon_path' =>
                            null,
                    ];
                }
            }

            if ($section === 'assets') {
                $request->validate([
                    'logo' => [
                        'nullable',
                        'image',
                        'mimes:jpg,jpeg,png,webp',
                        'max:5120',
                    ],

                    'favicon' => [
                        'nullable',
                        'file',
                        'mimes:png',
                        'max:512',
                    ],

                    'remove_logo' => [
                        'nullable',
                        'boolean',
                    ],

                    'remove_favicon' => [
                        'nullable',
                        'boolean',
                    ],
                ], [
                    'logo.max' =>
                        'The logo must not be larger than 5 MB.',

                    'favicon.max' =>
                        'The favicon must not be larger than 512 KB.',
                ]);

                if ($request->hasFile('logo')) {
                    $newLogoPath =
                        $request
                            ->file('logo')
                            ->store(
                                'platform-branding/logos',
                                'public'
                            );

                    if (
                        ! is_string($newLogoPath)
                        || $newLogoPath === ''
                        || ! Storage::disk('public')
                            ->exists($newLogoPath)
                    ) {
                        throw ValidationException::withMessages([
                            'logo' =>
                                'The uploaded logo could not be stored. '
                                .'Check public storage and try again.',
                        ]);
                    }

                    $changes['logo_path'] =
                        $newLogoPath;
                } elseif (
                    $request->boolean(
                        'remove_logo'
                    )
                ) {
                    $changes['logo_path'] =
                        null;
                }

                if ($request->hasFile('favicon')) {
                    $newFaviconPath =
                        $request
                            ->file('favicon')
                            ->store(
                                'platform-branding/favicons',
                                'public'
                            );

                    if (
                        ! is_string($newFaviconPath)
                        || $newFaviconPath === ''
                        || ! Storage::disk('public')
                            ->exists($newFaviconPath)
                    ) {
                        throw ValidationException::withMessages([
                            'favicon' =>
                                'The uploaded favicon could not be stored. '
                                .'Check public storage and try again.',
                        ]);
                    }

                    $changes['favicon_path'] =
                        $newFaviconPath;
                } elseif (
                    $request->boolean(
                        'remove_favicon'
                    )
                ) {
                    $changes['favicon_path'] =
                        null;
                }
            }

            if ($section === 'colors') {
                $validated =
                    $request->validate([
                        'primary_color' => [
                            'required',
                            'string',
                            'regex:/^#[0-9A-Fa-f]{6}$/',
                        ],

                        'accent_color' => [
                            'required',
                            'string',
                            'regex:/^#[0-9A-Fa-f]{6}$/',
                        ],

                        'pdf_primary_color' => [
                            'required',
                            'string',
                            'regex:/^#[0-9A-Fa-f]{6}$/',
                        ],
                    ], [
                        'primary_color.regex' =>
                            'Enter a valid color such as #312E81.',

                        'accent_color.regex' =>
                            'Enter a valid color such as #4F46E5.',

                        'pdf_primary_color.regex' =>
                            'Enter a valid color such as #312E81.',
                    ]);

                $changes = [
                    'primary_color' =>
                        strtoupper(
                            $validated[
                                'primary_color'
                            ]
                        ),

                    'accent_color' =>
                        strtoupper(
                            $validated[
                                'accent_color'
                            ]
                        ),

                    'pdf_primary_color' =>
                        strtoupper(
                            $validated[
                                'pdf_primary_color'
                            ]
                        ),
                ];
            }

            if ($section === 'contact') {
                $validated =
                    $request->validate([
                        'support_email' => [
                            'nullable',
                            'email',
                            'max:255',
                        ],

                        'website_url' => [
                            'nullable',
                            'url',
                            'max:255',
                        ],

                        'footer_text' => [
                            'nullable',
                            'string',
                            'max:1000',
                        ],
                    ]);

                $changes = [
                    'support_email' =>
                        $this->nullableText(
                            $validated[
                                'support_email'
                            ] ?? null
                        ),

                    'website_url' =>
                        $this->nullableText(
                            $validated[
                                'website_url'
                            ] ?? null
                        ),

                    'footer_text' =>
                        $this->nullableText(
                            $validated[
                                'footer_text'
                            ] ?? null
                        ),
                ];
            }

            if ($section === 'assets') {
                /*
                 * Asset submissions are strictly isolated from identity,
                 * color, contact, and footer fields.
                 */
                $changes =
                    array_intersect_key(
                        $changes,
                        array_flip([
                            'logo_path',
                            'favicon_path',
                        ])
                    );
            }

            $savedSettings =
                DB::transaction(
                    function () use (
                        $request,
                        $section,
                        $oldSettings,
                        $changes
                    ): array {
                        $setting =
                            PlatformBrandingSetting::query()
                                ->where(
                                    'singleton_key',
                                    PlatformBrandingSetting::
                                        SINGLETON_KEY
                                )
                                ->lockForUpdate()
                                ->first();

                        if ($setting === null) {
                            $setting =
                                new PlatformBrandingSetting();

                            $setting->singleton_key =
                                PlatformBrandingSetting::
                                    SINGLETON_KEY;

                            /*
                             * Initialize a new singleton with the current
                             * service defaults. This prevents an asset-only
                             * update from clearing identity, colors, or
                             * contact values.
                             */
                            $setting->fill(
                                $this->recordValues(
                                    $oldSettings
                                )
                            );
                        }

                        $setting->fill(
                            array_merge(
                                $changes,
                                [
                                    'updated_by_user_id' =>
                                        $request->user()->id,
                                ]
                            )
                        );

                        $setting->save();
                        $setting->refresh();

                        if (
                            $section === 'assets'
                            && (
                                $setting->platform_name
                                    !== $oldSettings['platform_name']
                                || $setting->short_name
                                    !== $oldSettings['short_name']
                            )
                        ) {
                            throw new \RuntimeException(
                                'An asset update attempted to alter '
                                .'the platform identity.'
                            );
                        }

                        $newSettings =
                            $this->settingsFromModel(
                                $setting
                            );

                        $this->activityLogger->log(
                            action:
                                'platform.branding_settings_updated',

                            description:
                                'Platform branding section updated: '
                                .$section.'.',

                            subject:
                                $setting,

                            organizationId:
                                null,

                            properties: [
                                'section' =>
                                    $section,

                                'changed_fields' =>
                                    array_keys(
                                        $changes
                                    ),

                                'old' =>
                                    $oldSettings,

                                'new' =>
                                    $newSettings,
                            ],
                        );

                        return $newSettings;
                    },
                    3
                );

            if ($section === 'logo') {
                $this->deleteReplacedAsset(
                    oldPath:
                        $oldSettings[
                            'logo_path'
                        ] ?? null,

                    newPath:
                        $savedSettings[
                            'logo_path'
                        ] ?? null
                );
            }

            if ($section === 'favicon') {
                $this->deleteReplacedAsset(
                    oldPath:
                        $oldSettings[
                            'favicon_path'
                        ] ?? null,

                    newPath:
                        $savedSettings[
                            'favicon_path'
                        ] ?? null
                );
            }

            if ($section === 'assets') {
                $this->deleteReplacedAsset(
                    oldPath:
                        $oldSettings[
                            'logo_path'
                        ] ?? null,

                    newPath:
                        $savedSettings[
                            'logo_path'
                        ] ?? null
                );

                $this->deleteReplacedAsset(
                    oldPath:
                        $oldSettings[
                            'favicon_path'
                        ] ?? null,

                    newPath:
                        $savedSettings[
                            'favicon_path'
                        ] ?? null
                );
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            foreach ([
                $newLogoPath,
                $newFaviconPath,
            ] as $newPath) {
                if (
                    is_string($newPath)
                    && Storage::disk('public')
                        ->exists($newPath)
                ) {
                    Storage::disk('public')
                        ->delete($newPath);
                }
            }

            report(
                $exception
            );

            return back()
                ->withInput()
                ->withErrors([
                    'branding' =>
                        'The selected branding section could not be saved.',
                ]);
        }

        $settingsService->forgetCache();

        $platformBrand =
            $settingsService->viewData();

        config([
            'app.name' =>
                $platformBrand[
                    'platform_name'
                ],
        ]);

        ViewFacade::share(
            'platformBrand',
            $platformBrand
        );

        $successMessage =
            match ($section) {
                'identity' =>
                    'Platform identity updated successfully.',

                'logo' =>
                    'Platform logo updated successfully.',

                'favicon' =>
                    'Browser favicon updated successfully.',

                'assets' =>
                    'Logo and favicon updated successfully.',

                'colors' =>
                    'Platform colors updated successfully.',

                'contact' =>
                    'Platform contact and footer updated successfully.',

                default =>
                    'Platform branding updated successfully.',
            };

        return redirect()
            ->route(
                'platform.branding-settings.index'
            )
            ->with(
                'success',
                $successMessage
            )
            ->with(
                'success_section',
                $section
            );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function recordValues(
        array $settings
    ): array {
        return [
            'platform_name' =>
                $settings[
                    'platform_name'
                ],

            'short_name' =>
                $settings[
                    'short_name'
                ],

            'tagline' =>
                $settings[
                    'tagline'
                ] ?? null,

            'description' =>
                $settings[
                    'description'
                ] ?? null,

            'logo_path' =>
                $settings[
                    'logo_path'
                ] ?? null,

            'favicon_path' =>
                $settings[
                    'favicon_path'
                ] ?? null,

            'primary_color' =>
                $settings[
                    'primary_color'
                ],

            'accent_color' =>
                $settings[
                    'accent_color'
                ],

            'pdf_primary_color' =>
                $settings[
                    'pdf_primary_color'
                ],

            'support_email' =>
                $settings[
                    'support_email'
                ] ?? null,

            'website_url' =>
                $settings[
                    'website_url'
                ] ?? null,

            'footer_text' =>
                $settings[
                    'footer_text'
                ] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsFromModel(
        PlatformBrandingSetting $setting
    ): array {
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

    private function ensureSuperAdmin(
        Request $request
    ): void {
        $user =
            $request->user();

        $user?->loadMissing(
            'platformRole'
        );

        abort_unless(
            $user
                ?->platformRole
                ?->slug
                === 'super-admin',
            403
        );
    }

    private function nullableText(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function deleteReplacedAsset(
        mixed $oldPath,
        mixed $newPath
    ): void {
        if (
            ! is_string($oldPath)
            || $oldPath === ''
            || $oldPath === $newPath
        ) {
            return;
        }

        $disk =
            Storage::disk('public');

        if ($disk->exists($oldPath)) {
            $disk->delete(
                $oldPath
            );
        }
    }
}
