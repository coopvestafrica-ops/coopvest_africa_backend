<?php

namespace Database\Seeders;

use App\Models\LoanType;
use Illuminate\Database\Seeder;

class LoanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds the 6 loan types from the Flutter app
     */
    public function run(): void
    {
        // Clear existing loan types (optional - comment out if you want to keep existing)
        // LoanType::query()->delete();

        $loanTypes = [
            [
                'name' => 'Quick Loan',
                'description' => 'Fast approval for short-term urgent needs',
                'minimum_amount' => 1000,
                'maximum_amount' => 10000,
                'interest_rate' => 7.5,
                'duration_months' => 4,
                'processing_fee_percentage' => 2.5,
                'requires_guarantor' => false,
                'minimum_employment_months' => null,
                'minimum_salary' => 1000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 1 month employment history',
                    'Minimum monthly income of 1,000',
                    'No pending loan defaults'
                ],
                'max_rollover_times' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Flexi Loan',
                'description' => 'Flexible loan with moderate terms and rates',
                'minimum_amount' => 2000,
                'maximum_amount' => 25000,
                'interest_rate' => 7.0,
                'duration_months' => 6,
                'processing_fee_percentage' => 2.0,
                'requires_guarantor' => false,
                'minimum_employment_months' => 3,
                'minimum_salary' => 2000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 3 months employment history',
                    'Minimum monthly income of 2,000',
                    'Debt-to-income ratio must be below 50%',
                    'No pending loan defaults'
                ],
                'max_rollover_times' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Stable Loan (12 months)',
                'description' => 'Standard 12-month loan with low interest rates',
                'minimum_amount' => 5000,
                'maximum_amount' => 50000,
                'interest_rate' => 5.0,
                'duration_months' => 12,
                'processing_fee_percentage' => 1.5,
                'requires_guarantor' => false,
                'minimum_employment_months' => 6,
                'minimum_salary' => 3000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 6 months employment history',
                    'Minimum monthly income of 3,000',
                    'Debt-to-income ratio must be below 40%',
                    'No pending loan defaults',
                    'Good payment history on previous loans'
                ],
                'max_rollover_times' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Stable Loan (18 months)',
                'description' => 'Extended 18-month loan for larger amounts',
                'minimum_amount' => 10000,
                'maximum_amount' => 75000,
                'interest_rate' => 7.0,
                'duration_months' => 18,
                'processing_fee_percentage' => 2.0,
                'requires_guarantor' => true,
                'minimum_employment_months' => 6,
                'minimum_salary' => 4000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 6 months employment history',
                    'Minimum monthly income of 4,000',
                    'Debt-to-income ratio must be below 45%',
                    'No pending loan defaults',
                    'Good payment history on previous loans',
                    'Must provide at least one guarantor'
                ],
                'max_rollover_times' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Loan',
                'description' => 'Premium loan for established members with good credit',
                'minimum_amount' => 20000,
                'maximum_amount' => 100000,
                'interest_rate' => 14.0,
                'duration_months' => 24,
                'processing_fee_percentage' => 3.0,
                'requires_guarantor' => true,
                'minimum_employment_months' => 12,
                'minimum_salary' => 5000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 12 months employment history',
                    'Minimum monthly income of 5,000',
                    'Debt-to-income ratio must be below 40%',
                    'No pending loan defaults',
                    'Excellent payment history (min 2 previous loans)',
                    'Must provide two guarantors',
                    'Employment verification required'
                ],
                'max_rollover_times' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Maxi Loan',
                'description' => 'Maximum loan amount for long-term financial needs',
                'minimum_amount' => 30000,
                'maximum_amount' => 150000,
                'interest_rate' => 19.0,
                'duration_months' => 36,
                'processing_fee_percentage' => 3.5,
                'requires_guarantor' => true,
                'minimum_employment_months' => 12,
                'minimum_salary' => 6000,
                'eligibility_requirements' => [
                    'Must have completed KYC verification',
                    'Minimum 12 months employment history',
                    'Minimum monthly income of 6,000',
                    'Debt-to-income ratio must be below 35%',
                    'No pending loan defaults',
                    'Excellent payment history (min 3 previous loans)',
                    'Must provide two or more guarantors',
                    'Employment verification required',
                    'Bank statements review required'
                ],
                'max_rollover_times' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($loanTypes as $loanType) {
            LoanType::updateOrCreate(
                ['name' => $loanType['name']],
                $loanType
            );
        }

        $this->command->info('✅ ' . count($loanTypes) . ' loan types seeded successfully!');
    }
}
