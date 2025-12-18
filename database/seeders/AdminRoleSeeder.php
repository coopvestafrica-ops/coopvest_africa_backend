<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminRole;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full system access and control',
                'level' => 0,
                'permissions' => [
                    'manage_features',
                    'manage_admins',
                    'manage_roles',
                    'view_logs',
                    'manage_users',
                    'manage_loans',
                    'manage_guarantors',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access with limited control',
                'level' => 1,
                'permissions' => [
                    'manage_features',
                    'view_logs',
                    'manage_users',
                    'manage_loans',
                    'manage_guarantors',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Moderator',
                'slug' => 'moderator',
                'description' => 'Moderation and content management',
                'level' => 2,
                'permissions' => [
                    'view_logs',
                    'manage_users',
                    'manage_loans',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Customer support and assistance',
                'level' => 3,
                'permissions' => [
                    'view_logs',
                    'manage_users',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            AdminRole::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
