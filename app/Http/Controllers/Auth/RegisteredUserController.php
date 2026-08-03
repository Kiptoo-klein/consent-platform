<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming organization registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $permissionRegistrar = app(PermissionRegistrar::class);
        $previousTeamId = $permissionRegistrar->getPermissionsTeamId();

        try {
            $user = DB::transaction(function () use (
                $validated,
                $permissionRegistrar
            ): User {
                $basicPlan = SubscriptionPlan::query()
                    ->where('slug', 'basic')
                    ->where('is_active', true)
                    ->first();

                if ($basicPlan === null) {
                    throw new \RuntimeException(
                        'The active Basic subscription plan is unavailable.'
                    );
                }

                $organization = Organization::create([
                    'name' => $validated['organization_name'],
                    'slug' => Str::slug(
                        $validated['organization_name']
                    ).'-'.uniqid(),
                ]);

                $administratorRole = null;

                foreach (OrganizationRole::cases() as $roleDetails) {
                    $role = Role::query()->firstOrCreate([
                        'organization_id' => $organization->id,
                        'name' => $roleDetails->label(),
                        'guard_name' => 'web',
                    ]);

                    if (
                        $roleDetails
                        === OrganizationRole::ORGANIZATION_ADMINISTRATOR
                    ) {
                        $administratorRole = $role;
                    }
                }

                if ($administratorRole === null) {
                    throw new \RuntimeException(
                        'Organization Admin role could not be created.'
                    );
                }

                $user = User::create([
                    'organization_id' => $organization->id,
                    'platform_role_id' => null,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make(
                        $validated['password']
                    ),
                    'is_active' => true,
                ]);

                $permissionRegistrar
                    ->setPermissionsTeamId($organization->id);

                $user->assignRole($administratorRole);

                $organization->subscription()->create([
                    'subscription_plan_id' => $basicPlan->id,
                    'billing_owner_user_id' => $user->id,
                    'status' =>
                        OrganizationSubscriptionStatus::TRIALING,
                    'payment_status' =>
                        SubscriptionPaymentStatus::UNPAID,
                    'starts_at' => now(),
                ]);

                $permissionRegistrar->forgetCachedPermissions();

                return $user;
            });
        } finally {
            $permissionRegistrar
                ->setPermissionsTeamId($previousTeamId);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()
            ->route(
                'organization-subscription-plans.index'
            )
            ->with(
                'success',
                'Welcome to eConsent. Choose the subscription plan '
                .'and billing cycle that fit your organization.'
            );
    }
}
