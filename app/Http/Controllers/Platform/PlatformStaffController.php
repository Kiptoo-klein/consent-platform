<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformStaffController extends Controller
{
    private const MAX_PLATFORM_STAFF = 4;

    private const ROLE_SLUGS = [
        'super-admin',
        'billing',
        'support',
        'platform-auditor',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(): View
    {
        $staff = User::query()
            ->whereNull('organization_id')
            ->whereNotNull('platform_role_id')
            ->with('platformRole')
            ->orderBy('name')
            ->paginate(20);

        $roles = PlatformRole::query()
            ->whereIn(
                'slug',
                self::ROLE_SLUGS
            )
            ->withCount([
                'users' =>
                    function ($query): void {
                        $query
                            ->whereNull(
                                'organization_id'
                            )
                            ->where(
                                'is_active',
                                true
                            );
                    },
            ])
            ->orderBy('id')
            ->get();

        $platformStaffCount =
            $this->platformStaffCount();

        return view(
            'platform.staff.index',
            [
                'staff' =>
                    $staff,

                'roles' =>
                    $roles,

                'platformStaffCount' =>
                    $platformStaffCount,

                'maximumPlatformStaff' =>
                    self::MAX_PLATFORM_STAFF,
            ]
        );
    }

    public function create(): View
    {
        $platformStaffCount =
            $this->platformStaffCount();

        return view(
            'platform.staff.create',
            [
                'roles' =>
                    $this->platformRoles(),

                'platformStaffCount' =>
                    $platformStaffCount,

                'maximumPlatformStaff' =>
                    self::MAX_PLATFORM_STAFF,

                'platformStaffLimitReached' =>
                    $platformStaffCount
                    >= self::MAX_PLATFORM_STAFF,
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                ),
            ],

            'platform_role_id' => [
                'required',
                'integer',
                Rule::exists(
                    'platform_roles',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->whereIn(
                            'slug',
                            self::ROLE_SLUGS
                        )
                ),
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $role = PlatformRole::query()
            ->whereIn(
                'slug',
                self::ROLE_SLUGS
            )
            ->findOrFail(
                $validated[
                    'platform_role_id'
                ]
            );

        $staffMember = DB::transaction(
            function () use (
                $validated,
                $role
            ): User {
                /*
                 * Every staff-creation request locks the same stable role
                 * row. This serializes concurrent creation attempts before
                 * the four-account limit is checked.
                 */
                PlatformRole::query()
                    ->where(
                        'slug',
                        'super-admin'
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $this->platformStaffCount()
                    >= self::MAX_PLATFORM_STAFF
                ) {
                    throw ValidationException::
                        withMessages([
                            'platform_staff' =>
                                'The platform allows a maximum of four '
                                .'platform staff accounts. Edit or reassign '
                                .'an existing account before creating '
                                .'another.',
                        ]);
                }

                $staffMember =
                    User::query()->create([
                        'organization_id' =>
                            null,

                        'platform_role_id' =>
                            $role->id,

                        'name' =>
                            $validated['name'],

                        'email' =>
                            $validated['email'],

                        'password' =>
                            $validated['password'],

                        'is_active' =>
                            true,
                    ]);

                $this->activityLogger->log(
                    action:
                        'platform.staff_created',

                    description:
                        "Created platform staff account {$staffMember->name}.",

                    subject:
                        $staffMember,

                    organizationId:
                        null,

                    properties: [
                        'old' => [],

                        'new' => [
                            'name' =>
                                $staffMember->name,

                            'email' =>
                                $staffMember->email,

                            'role' =>
                                $role->slug,

                            'is_active' =>
                                true,
                        ],
                    ],
                );

                return $staffMember;
            },
            3
        );

        return redirect()
            ->route(
                'platform.staff.index'
            )
            ->with(
                'success',
                "Platform staff account {$staffMember->name} created successfully."
            );
    }

    public function edit(
        User $platformStaff
    ): View {
        $this->ensurePlatformStaff(
            $platformStaff
        );

        $platformStaff->load(
            'platformRole'
        );

        return view(
            'platform.staff.edit',
            [
                'platformStaff' =>
                    $platformStaff,

                'roles' =>
                    $this->platformRoles(),
            ]
        );
    }

    public function update(
        Request $request,
        User $platformStaff
    ): RedirectResponse {
        $this->ensurePlatformStaff(
            $platformStaff
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $platformStaff
                ),
            ],

            'platform_role_id' => [
                'required',
                'integer',
                Rule::exists(
                    'platform_roles',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->whereIn(
                            'slug',
                            self::ROLE_SLUGS
                        )
                ),
            ],

            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $newRole =
            PlatformRole::query()
                ->whereIn(
                    'slug',
                    self::ROLE_SLUGS
                )
                ->findOrFail(
                    $validated[
                        'platform_role_id'
                    ]
                );

        DB::transaction(
            function () use (
                $validated,
                $newRole,
                $platformStaff
            ): void {
                $lockedStaff =
                    User::query()
                        ->with(
                            'platformRole'
                        )
                        ->whereKey(
                            $platformStaff->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $oldValues = [
                    'name' =>
                        $lockedStaff->name,

                    'email' =>
                        $lockedStaff->email,

                    'role' =>
                        $lockedStaff
                            ->platformRole
                            ?->slug,

                    'is_active' =>
                        (bool) $lockedStaff
                            ->is_active,
                ];

                if (
                    $lockedStaff->is_active
                    && $lockedStaff
                        ->platformRole
                        ?->slug
                        === 'super-admin'
                    && $newRole->slug
                        !== 'super-admin'
                    && $this
                        ->activeSuperAdminCountForUpdate()
                        <= 1
                ) {
                    throw ValidationException::
                        withMessages([
                            'platform_role_id' =>
                                'This account is the last active Super Admin. '
                                .'Create or enable another Super Admin before '
                                .'changing this role.',
                        ]);
                }

                $lockedStaff->name =
                    $validated['name'];

                $lockedStaff->email =
                    $validated['email'];

                $lockedStaff
                    ->platform_role_id =
                    $newRole->id;

                if (
                    filled(
                        $validated[
                            'password'
                        ] ?? null
                    )
                ) {
                    $lockedStaff->password =
                        $validated['password'];
                }

                $lockedStaff->save();

                $this->activityLogger->log(
                    action:
                        'platform.staff_updated',

                    description:
                        "Updated platform staff account {$lockedStaff->name}.",

                    subject:
                        $lockedStaff,

                    organizationId:
                        null,

                    properties: [
                        'old' =>
                            $oldValues,

                        'new' => [
                            'name' =>
                                $lockedStaff->name,

                            'email' =>
                                $lockedStaff->email,

                            'role' =>
                                $newRole->slug,

                            'is_active' =>
                                (bool) $lockedStaff
                                    ->is_active,
                        ],
                    ],
                );
            },
            3
        );

        return redirect()
            ->route(
                'platform.staff.index'
            )
            ->with(
                'success',
                'Platform staff account updated successfully.'
            );
    }

    public function updateStatus(
        Request $request,
        User $platformStaff
    ): RedirectResponse {
        $this->ensurePlatformStaff(
            $platformStaff
        );

        $validated = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $newStatus =
            (bool) $validated[
                'is_active'
            ];

        if (
            $request->user()->is(
                $platformStaff
            )
            && ! $newStatus
        ) {
            return back()->withErrors([
                'status' =>
                    'You cannot disable your own platform account.',
            ]);
        }

        DB::transaction(
            function () use (
                $newStatus,
                $platformStaff
            ): void {
                $lockedStaff =
                    User::query()
                        ->with(
                            'platformRole'
                        )
                        ->whereKey(
                            $platformStaff->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $oldStatus =
                    (bool) $lockedStaff
                        ->is_active;

                if (
                    $oldStatus
                    && ! $newStatus
                    && $lockedStaff
                        ->platformRole
                        ?->slug
                        === 'super-admin'
                    && $this
                        ->activeSuperAdminCountForUpdate()
                        <= 1
                ) {
                    throw ValidationException::
                        withMessages([
                            'status' =>
                                'This account is the last active Super Admin. '
                                .'Create or enable another Super Admin before '
                                .'disabling it.',
                        ]);
                }

                $lockedStaff->is_active =
                    $newStatus;

                $lockedStaff->save();

                $this->activityLogger->log(
                    action:
                        $newStatus
                            ? 'platform.staff_enabled'
                            : 'platform.staff_disabled',

                    description:
                        $newStatus
                            ? "Enabled platform staff account {$lockedStaff->name}."
                            : "Disabled platform staff account {$lockedStaff->name}.",

                    subject:
                        $lockedStaff,

                    organizationId:
                        null,

                    properties: [
                        'old' => [
                            'is_active' =>
                                $oldStatus,
                        ],

                        'new' => [
                            'is_active' =>
                                $newStatus,
                        ],
                    ],
                );
            },
            3
        );

        return redirect()
            ->route(
                'platform.staff.index'
            )
            ->with(
                'success',
                $newStatus
                    ? 'Platform staff account enabled successfully.'
                    : 'Platform staff account disabled successfully.'
            );
    }

    /**
     * Count all non-archived platform staff accounts.
     *
     * Disabled accounts still count because they remain platform users and
     * can be enabled again.
     */
    private function platformStaffCount(): int
    {
        return User::query()
            ->whereNull(
                'organization_id'
            )
            ->whereNotNull(
                'platform_role_id'
            )
            ->count();
    }

    /**
     * @return Collection<int, PlatformRole>
     */
    private function platformRoles(): Collection
    {
        return PlatformRole::query()
            ->whereIn(
                'slug',
                self::ROLE_SLUGS
            )
            ->orderBy('id')
            ->get();
    }

    private function ensurePlatformStaff(
        User $platformStaff
    ): void {
        abort_unless(
            $platformStaff
                ->organization_id
                === null
            && $platformStaff
                ->platform_role_id
                !== null,
            404
        );
    }

    private function activeSuperAdminCountForUpdate(): int
    {
        $superAdminRoleId =
            PlatformRole::query()
                ->where(
                    'slug',
                    'super-admin'
                )
                ->value('id');

        if ($superAdminRoleId === null) {
            return 0;
        }

        return User::query()
            ->whereNull(
                'organization_id'
            )
            ->where(
                'platform_role_id',
                $superAdminRoleId
            )
            ->where(
                'is_active',
                true
            )
            ->lockForUpdate()
            ->get()
            ->count();
    }
}
