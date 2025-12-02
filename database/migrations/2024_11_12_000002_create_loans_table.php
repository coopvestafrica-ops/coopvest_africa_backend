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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('loan_type_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('USD');
            $table->integer('tenure');
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('total_interest', 15, 2);
            $table->decimal('monthly_payment', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed', 'defaulted', 'suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('application_date');
            $table->timestamp('due_date');
            $table->timestamp('next_payment_date')->nullable();
            
            // Rollover info
            $table->boolean('is_rolled_over')->default(false);
            $table->unsignedBigInteger('previous_loan_id')->nullable();
            $table->timestamp('rollover_date')->nullable();
            $table->integer('remaining_rollovers')->nullable();
            
            // Payment tracking
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2);
            $table->integer('payments_made')->default(0);
            $table->integer('missed_payments')->default(0);
            $table->timestamp('last_payment_date')->nullable();
            
            // Additional
            $table->string('loan_purpose')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('loan_type_id')->references('id')->on('loan_types')->onDelete('restrict');
            $table->foreign('previous_loan_id')->references('id')->on('loans')->onDelete('set null');
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
