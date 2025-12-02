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
        // Create guarantors table
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('guarantor_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Relationship details
            $table->enum('relationship', ['friend', 'family', 'colleague', 'business_partner'])->default('friend');
            
            // Verification workflow
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->boolean('employment_verification_required')->default(false);
            $table->boolean('employment_verification_completed')->default(false);
            $table->text('employment_verification_url')->nullable();
            
            // Confirmation workflow
            $table->enum('confirmation_status', ['pending', 'accepted', 'declined', 'revoked'])->default('pending');
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('invitation_declined_at')->nullable();
            
            // QR Code details
            $table->longText('qr_code')->nullable(); // Base64 encoded
            $table->string('qr_code_token')->nullable()->unique();
            $table->timestamp('qr_code_expires_at')->nullable();
            
            // Liability and notes
            $table->decimal('liability_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('loan_id');
            $table->index('guarantor_user_id');
            $table->index('verification_status');
            $table->index('confirmation_status');
            $table->index('qr_code_token');
        });

        // Create guarantor invitations table
        Schema::create('guarantor_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->string('guarantor_email');
            $table->string('invitation_token')->unique();
            $table->text('invitation_link');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('loan_id');
            $table->index('guarantor_email');
            $table->index('invitation_token');
            $table->index('status');
            $table->index('expires_at');
        });

        // Create guarantor verification documents table
        Schema::create('guarantor_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guarantor_id')->constrained('guarantors')->onDelete('cascade');
            $table->enum('document_type', [
                'employment_letter',
                'id_document',
                'bank_statement',
                'payslip',
                'business_license',
                'registration_document'
            ]);
            $table->string('document_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('guarantor_id');
            $table->index('document_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantor_verification_documents');
        Schema::dropIfExists('guarantor_invitations');
        Schema::dropIfExists('guarantors');
    }
};
