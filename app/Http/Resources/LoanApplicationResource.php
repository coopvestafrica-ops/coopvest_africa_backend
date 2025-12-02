<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'loan_type_id' => $this->loan_type_id,
            'loan_type' => [
                'id' => $this->loanType?->id,
                'name' => $this->loanType?->name,
                'description' => $this->loanType?->description,
                'interest_rate' => $this->loanType?->interest_rate,
            ],
            'requested_amount' => (float) $this->requested_amount,
            'currency' => $this->currency,
            'requested_tenure' => $this->requested_tenure,
            'loan_purpose' => $this->loan_purpose,
            'employment_status' => $this->employment_status,
            'employer_name' => $this->employer_name,
            'job_title' => $this->job_title,
            'employment_start_date' => $this->employment_start_date,
            'monthly_salary' => $this->monthly_salary ? (float) $this->monthly_salary : null,
            'monthly_expenses' => (float) $this->monthly_expenses,
            'existing_loans' => $this->existing_loans,
            'existing_loan_balance' => (float) $this->existing_loan_balance,
            'savings_balance' => (float) $this->savings_balance,
            'business_revenue' => $this->business_revenue ? (float) $this->business_revenue : null,
            'status' => $this->status,
            'stage' => $this->stage,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
