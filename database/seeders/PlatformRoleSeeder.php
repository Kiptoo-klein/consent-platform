<?php

namespace Database\Seeders;

use App\Models\PlatformRole;
use Illuminate\Database\Seeder;

class PlatformRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access to the entire platform.',
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Customer support staff.',
            ],
            [
                'name' => 'Billing',
                'slug' => 'billing',
                'description' => 'Manage subscriptions and billing.',
            ],
            [
                'name' => 'Platform Auditor',
                'slug' => 'platform-auditor',
                'description' => 'Read-only access to platform data.',
            ],
        ];

        foreach ($roles as $role) {
            PlatformRole::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
