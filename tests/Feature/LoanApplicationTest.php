<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoanType;
use App\Models\LoanApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LoanType $loanType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'kyc_verified' => true
        ]);

        // Create test loan type
        $this->loanType = LoanType::create([
            'name' => 'Personal Loan',
            'description' => 'Test personal loan',
            'minimum_amount' => 1000,
            'maximum_amount' => 50000,
            'interest_rate' => 12.5,
            'duration_months' => 12,
            'processing_fee_percentage' => 2,
            'requires_guarantor' => false,
            'minimum_employment_months' => 6,
            'minimum_salary' => 2000,
            'is_active' => true
        ]);
    }

    /**
     * Test retrieving user's loan applications
     */
    public function test_get_user_applications()
    {
        // Create applications for user
        LoanApplication::factory(3)->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/loan-applications/my-applications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test getting available loan types
     */
    public function test_get_available_loan_types()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/loan-applications/available-types');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount(1, 'data');

        $this->assertTrue($response->json('data.0.is_eligible'));
    }

    /**
     * Test creating a loan application
     */
    public function test_create_loan_application()
    {
        $data = [
            'loan_type_id' => $this->loanType->id,
            'requested_amount' => 15000,
            'requested_tenure' => 12,
            'loan_purpose' => 'Business expansion',
            'employment_status' => 'employed',
            'employer_name' => 'Tech Corp',
            'job_title' => 'Software Engineer',
            'employment_start_date' => now()->subYears(2)->toDateString(),
            'monthly_salary' => 5000,
            'monthly_expenses' => 2000,
            'existing_loans' => 1,
            'existing_loan_balance' => 3000,
            'savings_balance' => 5000
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-applications/create', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Loan application created successfully'
            ]);

        $this->assertDatabaseHas('loan_applications', [
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id,
            'requested_amount' => 15000,
            'status' => 'draft'
        ]);
    }

    /**
     * Test creating application without KYC verification
     */
    public function test_create_application_without_kyc()
    {
        $unverifiedUser = User::factory()->create([
            'kyc_verified' => false
        ]);

        $data = [
            'loan_type_id' => $this->loanType->id,
            'requested_amount' => 15000,
            'requested_tenure' => 12,
            'loan_purpose' => 'Business expansion',
            'employment_status' => 'employed',
            'employer_name' => 'Tech Corp',
            'job_title' => 'Software Engineer',
            'employment_start_date' => now()->subYears(2)->toDateString(),
            'monthly_salary' => 5000,
            'monthly_expenses' => 2000,
            'existing_loans' => 0,
            'existing_loan_balance' => 0,
            'savings_balance' => 0
        ];

        $response = $this->actingAs($unverifiedUser)
            ->postJson('/api/loan-applications/create', $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'KYC verification is required before applying for a loan'
            ]);
    }

    /**
     * Test validation of required fields
     */
    public function test_create_application_validation()
    {
        $data = [
            'requested_amount' => 15000,
            'requested_tenure' => 12,
            'loan_purpose' => 'Business expansion'
            // Missing required fields
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-applications/create', $data);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed');

        $this->assertArrayHasKey('loan_type_id', $response->json('errors'));
        $this->assertArrayHasKey('employment_status', $response->json('errors'));
    }

    /**
     * Test getting application details
     */
    public function test_get_application_details()
    {
        $application = LoanApplication::factory()->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/loan-applications/' . $application->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $application->id,
                    'user_id' => $this->user->id
                ]
            ]);
    }

    /**
     * Test authorization when viewing other user's application
     */
    public function test_cannot_view_other_user_application()
    {
        $otherUser = User::factory()->create();
        $application = LoanApplication::factory()->create([
            'user_id' => $otherUser->id,
            'loan_type_id' => $this->loanType->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/loan-applications/' . $application->id);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /**
     * Test updating a draft application
     */
    public function test_update_draft_application()
    {
        $application = LoanApplication::factory()->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id,
            'status' => 'draft',
            'requested_amount' => 10000
        ]);

        $updateData = [
            'requested_amount' => 20000,
            'loan_purpose' => 'Updated purpose'
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/loan-applications/' . $application->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Loan application updated successfully'
            ]);

        $this->assertDatabaseHas('loan_applications', [
            'id' => $application->id,
            'requested_amount' => 20000,
            'loan_purpose' => 'Updated purpose'
        ]);
    }

    /**
     * Test cannot update submitted application
     */
    public function test_cannot_update_submitted_application()
    {
        $application = LoanApplication::factory()->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id,
            'status' => 'submitted',
            'submitted_at' => now()
        ]);

        $updateData = [
            'requested_amount' => 20000
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/loan-applications/' . $application->id, $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot update applications that have been submitted'
            ]);
    }

    /**
     * Test submitting a draft application
     */
    public function test_submit_application()
    {
        $application = LoanApplication::factory()->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id,
            'status' => 'draft',
            'monthly_salary' => 5000,
            'existing_loan_balance' => 1000
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-applications/' . $application->id . '/submit');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Application submitted successfully'
            ]);

        $this->assertDatabaseHas('loan_applications', [
            'id' => $application->id,
            'status' => 'submitted'
        ]);

        $this->assertNotNull($application->fresh()->submitted_at);
    }

    /**
     * Test admin can view applications for review
     */
    public function test_admin_view_applications_for_review()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create multiple applications with different statuses
        LoanApplication::factory(2)->create(['status' => 'submitted']);
        LoanApplication::factory(1)->create(['status' => 'under_review']);

        $response = $this->actingAs($admin)
            ->getJson('/api/loan-applications/admin/review?status=submitted');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test non-admin cannot view applications for review
     */
    public function test_non_admin_cannot_view_review_list()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/loan-applications/admin/review');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
    }

    /**
     * Test application eligibility check
     */
    public function test_application_eligibility_check()
    {
        // Create application with low salary (below minimum)
        $application = LoanApplication::factory()->create([
            'user_id' => $this->user->id,
            'loan_type_id' => $this->loanType->id,
            'monthly_salary' => 1000, // Below minimum of 2000
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-applications/' . $application->id . '/submit');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Application does not meet eligibility requirements'
            ]);
    }
}
