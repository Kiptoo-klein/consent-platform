<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the standard organization roles for every organization.
 *
 * Spatie Permission teams are enabled, and the configured team key is
 * organization_id. This means each organization receives its own separate
 * copies of these roles.
 */
class OrganizationRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Clear cached permissions
        |--------------------------------------------------------------------------
        |
        | Spatie caches role and permission information. Clearing the cache
        | ensures newly created roles become available immediately.
        |
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roleNames = [
            'Organization Admin',
            'Consent Manager',
            'Staff',
            'Auditor',
        ];

        Organization::query()
            ->orderBy('id')
            ->each(function (Organization $organization) use ($roleNames): void {
                foreach ($roleNames as $roleName) {
                    Role::query()->firstOrCreate([
                        'organization_id' => $organization->id,
                        'name' => $roleName,
                        'guard_name' => 'web',
                    ]);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
