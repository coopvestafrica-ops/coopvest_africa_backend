<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'requested_amount' => 'sometimes|numeric|min:1',
            'requested_tenure' => 'sometimes|integer|min:1|max:60',
            'loan_purpose' => 'sometimes|string|max:500',
            'employment_status' => 'sometimes|in:employed,self_employed,unemployed',
            'employer_name' => 'sometimes|nullable|string|max:255',
            'job_title' => 'sometimes|nullable|string|max:255',
            'employment_start_date' => 'sometimes|nullable|date|before_or_equal:today',
            'monthly_salary' => 'sometimes|nullable|numeric|min:0',
            'monthly_expenses' => 'sometimes|numeric|min:0',
            'existing_loans' => 'sometimes|integer|min:0',
            'existing_loan_balance' => 'sometimes|numeric|min:0',
            'savings_balance' => 'sometimes|numeric|min:0',
            'business_revenue' => 'sometimes|nullable|numeric|min:0'
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'requested_amount.numeric' => 'Requested amount must be a number',
            'requested_amount.min' => 'Requested amount must be greater than 0',
            'requested_tenure.integer' => 'Requested tenure must be a whole number',
            'requested_tenure.min' => 'Requested tenure must be at least 1 month',
            'requested_tenure.max' => 'Requested tenure cannot exceed 60 months',
            'employment_start_date.before_or_equal' => 'Employment start date cannot be in the future'
        ];
    }
}
