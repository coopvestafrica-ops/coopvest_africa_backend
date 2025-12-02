<?php

/**
 * Loan Types Configuration
 * 
 * Centralized configuration for all loan types available in the Coopvest platform.
 * These are the exact loan types from the Flutter mobile app, ported to the web app.
 * 
 * Key Features:
 * - Define all loan products in one place
 * - Easily manage terms, rates, and requirements
 * - Reference in code without database queries when needed
 * - Validates against database seeder data
 */

return [
    /**
     * Active Loan Types
     * Maps loan type keys to their configuration
     */
    'types' => [
        'quick_loan' => [
            'id' => null, // Set by database
            'name' => 'Quick Loan',
            'description' => 'Fast approval for short-term urgent needs',
            'key' => 'quick_loan',
            'minimum_amount' => 1000,
            'maximum_amount' => 10000,
            'interest_rate' => 7.5,
            'duration_months' => 4,
            'processing_fee_percentage' => 2.5,
            'requires_guarantor' => false,
            'max_rollover_times' => 1,
        ],
        'flexi_loan' => [
            'id' => null,
            'name' => 'Flexi Loan',
            'description' => 'Flexible loan with moderate terms and rates',
            'key' => 'flexi_loan',
            'minimum_amount' => 2000,
            'maximum_amount' => 25000,
            'interest_rate' => 7.0,
            'duration_months' => 6,
            'processing_fee_percentage' => 2.0,
            'requires_guarantor' => false,
            'max_rollover_times' => 2,
        ],
        'stable_loan_12' => [
            'id' => null,
            'name' => 'Stable Loan (12 months)',
            'description' => 'Standard 12-month loan with low interest rates',
            'key' => 'stable_loan_12',
            'minimum_amount' => 5000,
            'maximum_amount' => 50000,
            'interest_rate' => 5.0,
            'duration_months' => 12,
            'processing_fee_percentage' => 1.5,
            'requires_guarantor' => false,
            'max_rollover_times' => 1,
        ],
        'stable_loan_18' => [
            'id' => null,
            'name' => 'Stable Loan (18 months)',
            'description' => 'Extended 18-month loan for larger amounts',
            'key' => 'stable_loan_18',
            'minimum_amount' => 10000,
            'maximum_amount' => 75000,
            'interest_rate' => 7.0,
            'duration_months' => 18,
            'processing_fee_percentage' => 2.0,
            'requires_guarantor' => true,
            'max_rollover_times' => 1,
        ],
        'premium_loan' => [
            'id' => null,
            'name' => 'Premium Loan',
            'description' => 'Premium loan for established members with good credit',
            'key' => 'premium_loan',
            'minimum_amount' => 20000,
            'maximum_amount' => 100000,
            'interest_rate' => 14.0,
            'duration_months' => 24,
            'processing_fee_percentage' => 3.0,
            'requires_guarantor' => true,
            'max_rollover_times' => 2,
        ],
        'maxi_loan' => [
            'id' => null,
            'name' => 'Maxi Loan',
            'description' => 'Maximum loan amount for long-term financial needs',
            'key' => 'maxi_loan',
            'minimum_amount' => 30000,
            'maximum_amount' => 150000,
            'interest_rate' => 19.0,
            'duration_months' => 36,
            'processing_fee_percentage' => 3.5,
            'requires_guarantor' => true,
            'max_rollover_times' => 1,
        ],
    ],

    /**
     * Interest Rate Ranges (for validation)
     */
    'interest_rates' => [
        'minimum' => 5.0,
        'maximum' => 19.0,
    ],

    /**
     * Amount Ranges (for validation)
     */
    'amounts' => [
        'minimum_global' => 1000,
        'maximum_global' => 150000,
    ],

    /**
     * Duration Options (months)
     */
    'durations' => [4, 6, 12, 18, 24, 36],

    /**
     * Processing Fee Range
     */
    'processing_fees' => [
        'minimum' => 1.5,
        'maximum' => 3.5,
    ],

    /**
     * Rollover Configuration
     */
    'rollover' => [
        'max_allowed' => 2,
        'enabled' => true,
    ],

    /**
     * Employment Duration Requirements (months)
     */
    'employment_requirements' => [
        'quick_loan' => null,
        'flexi_loan' => 3,
        'stable_loan_12' => 6,
        'stable_loan_18' => 6,
        'premium_loan' => 12,
        'maxi_loan' => 12,
    ],

    /**
     * Salary Requirements
     */
    'salary_requirements' => [
        'quick_loan' => 1000,
        'flexi_loan' => 2000,
        'stable_loan_12' => 3000,
        'stable_loan_18' => 4000,
        'premium_loan' => 5000,
        'maxi_loan' => 6000,
    ],

    /**
     * Guarantor Requirements
     */
    'guarantor_requirements' => [
        'quick_loan' => 0,
        'flexi_loan' => 0,
        'stable_loan_12' => 0,
        'stable_loan_18' => 1,
        'premium_loan' => 2,
        'maxi_loan' => 2,
    ],

    /**
     * Default Settings
     */
    'defaults' => [
        'default_loan_type' => 'quick_loan',
        'display_order' => ['quick_loan', 'flexi_loan', 'stable_loan_12', 'stable_loan_18', 'premium_loan', 'maxi_loan'],
    ],

    /**
     * Feature Flags
     */
    'features' => [
        'enable_rollover' => true,
        'enable_guarantors' => true,
        'enable_payment_schedule' => true,
        'enable_early_repayment' => true,
    ],

    /**
     * API Configuration
     */
    'api' => [
        'paginate_per_page' => 10,
        'default_sort' => 'name',
    ],
];
