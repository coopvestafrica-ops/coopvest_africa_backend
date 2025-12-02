<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('loan_type_id');
            $table->decimal('requested_amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->integer('requested_tenure');
            $table->string('loan_purpose');
            
            // Employment info
            $table->enum('employment_status', ['employed', 'self_employed', 'unemployed']);
            $table->string('employer_name')->nullable();
            $table->string('job_title')->nullable();
            $table->date('employment_start_date')->nullable();
            $table->decimal('monthly_salary', 15, 2)->nullable();
            
            // Financial info
            $table->decimal('monthly_expenses', 15, 2);
            $table->integer('existing_loans')->default(0);
            $table->decimal('existing_loan_balance', 15, 2)->default(0);
            $table->decimal('savings_balance', 15, 2)->default(0);
            $table->decimal('business_revenue', 15, 2)->nullable();
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'withdrawn', 'completed'])->default('draft');
            $table->enum('stage', ['personal_info', 'employment', 'financial', 'guarantors', 'documents', 'review'])->default('personal_info');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->longText('notes')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('loan_type_id')->references('id')->on('loan_types')->onDelete('restrict');
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
