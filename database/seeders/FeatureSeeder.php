<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'Loan Application',
                'slug' => 'loan_application',
                'description' => 'Allow users to apply for loans',
                'category' => 'core',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => true,
                'metadata' => [
                    'min_amount' => 1000,
                    'max_amount' => 100000,
                ],
            ],
            [
                'name' => 'Guarantor System',
                'slug' => 'guarantor_system',
                'description' => 'Enable guarantor functionality for loans',
                'category' => 'core',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => true,
                'metadata' => [
                    'min_guarantors' => 1,
                    'max_guarantors' => 5,
                ],
            ],
            [
                'name' => 'QR Code Verification',
                'slug' => 'qr_verification',
                'description' => 'Enable QR code based verification',
                'category' => 'security',
                'platforms' => ['mobile'],
                'is_enabled' => false,
                'metadata' => null,
            ],
            [
                'name' => 'Two Factor Authentication',
                'slug' => 'two_factor_auth',
                'description' => 'Enable two-factor authentication',
                'category' => 'security',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => false,
                'metadata' => null,
            ],
            [
                'name' => 'Advanced Analytics',
                'slug' => 'advanced_analytics',
                'description' => 'Enable advanced analytics dashboard',
                'category' => 'analytics',
                'platforms' => ['web'],
                'is_enabled' => false,
                'metadata' => null,
            ],
            [
                'name' => 'Mobile App Push Notifications',
                'slug' => 'push_notifications',
                'description' => 'Enable push notifications in mobile app',
                'category' => 'notifications',
                'platforms' => ['mobile'],
                'is_enabled' => false,
                'metadata' => null,
            ],
            [
                'name' => 'Email Notifications',
                'slug' => 'email_notifications',
                'description' => 'Enable email notifications',
                'category' => 'notifications',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => true,
                'metadata' => null,
            ],
            [
                'name' => 'Referral Program',
                'slug' => 'referral_program',
                'description' => 'Enable user referral program',
                'category' => 'marketing',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => false,
                'metadata' => [
                    'referral_bonus' => 500,
                    'max_referrals' => 10,
                ],
            ],
            [
                'name' => 'Investment Features',
                'slug' => 'investment_features',
                'description' => 'Enable investment and savings features',
                'category' => 'core',
                'platforms' => ['web', 'mobile'],
                'is_enabled' => false,
                'metadata' => null,
            ],
            [
                'name' => 'Mobile App Offline Mode',
                'slug' => 'offline_mode',
                'description' => 'Enable offline mode in mobile app',
                'category' => 'mobile',
                'platforms' => ['mobile'],
                'is_enabled' => false,
                'metadata' => null,
            ],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['slug' => $feature['slug']],
                $feature
            );
        }
    }
}
