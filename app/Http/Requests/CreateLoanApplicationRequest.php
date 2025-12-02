<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLoanApplicationRequest extends FormRequest
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
            'loan_type_id' => 'required|exists:loan_types,id',
            'requested_amount' => 'required|numeric|min:1',
            'requested_tenure' => 'required|integer|min:1|max:60',
            'loan_purpose' => 'required|string|max:500',
            'employment_status' => 'required|in:employed,self_employed,unemployed',
            'employer_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'employment_start_date' => 'nullable|date|before_or_equal:today',
            'monthly_salary' => 'nullable|numeric|min:0',
            'monthly_expenses' => 'required|numeric|min:0',
            'existing_loans' => 'required|integer|min:0',
            'existing_loan_balance' => 'required|numeric|min:0',
            'savings_balance' => 'required|numeric|min:0',
            'business_revenue' => 'nullable|numeric|min:0'
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'loan_type_id.required' => 'Loan type is required',
            'loan_type_id.exists' => 'Selected loan type does not exist',
            'requested_amount.required' => 'Requested amount is required',
            'requested_amount.numeric' => 'Requested amount must be a number',
            'requested_amount.min' => 'Requested amount must be greater than 0',
            'requested_tenure.required' => 'Requested tenure is required',
            'requested_tenure.integer' => 'Requested tenure must be a whole number',
            'requested_tenure.min' => 'Requested tenure must be at least 1 month',
            'requested_tenure.max' => 'Requested tenure cannot exceed 60 months',
            'loan_purpose.required' => 'Loan purpose is required',
            'employment_status.required' => 'Employment status is required',
            'employment_start_date.before_or_equal' => 'Employment start date cannot be in the future'
        ];
    }
}
