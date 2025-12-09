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
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            
            // Foreign keys
            $table->foreignId('loan_id')
                ->constrained('loans')
                ->onDelete('cascade');
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('restrict');
            $table->foreignId('scanned_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // QR Token data
            $table->string('token', 255)->unique()->index();
            $table->longText('qr_data')->nullable(); // JSON encoded QR data
            $table->json('metadata')->nullable(); // Additional metadata

            // Timestamps
            $table->timestamp('expires_at')->index();
            $table->timestamp('scanned_at')->nullable();

            // Status tracking
            $table->enum('status', ['active', 'used', 'expired', 'revoked'])
                ->default('active')
                ->index();

            // Audit
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['loan_id', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index(['expires_at', 'status']);
        });

        // Create index for faster lookups
        Schema::table('qr_tokens', function (Blueprint $table) {
            $table->index(['token', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_tokens');
    }
};
