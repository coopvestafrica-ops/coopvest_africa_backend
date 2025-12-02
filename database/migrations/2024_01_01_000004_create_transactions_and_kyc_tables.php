<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('loan_id')->nullable()->constrained('loans')->onDelete('set null');
            $table->enum('type', [
                'loan_disbursement',
                'loan_payment',
                'contribution',
                'savings_deposit',
                'savings_withdrawal',
                'interest',
                'fee',
                'other'
            ]);
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->string('reference_number')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('loan_id');
            $table->index('type');
            $table->index('status');
        });

        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('document_type', ['passport', 'national_id', 'drivers_license', 'voter_id']);
            $table->string('document_number');
            $table->date('date_of_birth');
            $table->string('document_image_path');
            $table->string('proof_of_address_path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamp('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamps();
            
            $table->index('loan_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('action');
            $table->index('model_type');
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
            
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('kyc_verifications');
        Schema::dropIfExists('transactions');
    }
};
