<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('country');
            $table->string('password');
            $table->enum('role', ['member', 'admin', 'super_admin'])->default('member');
            $table->enum('kyc_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('kyc_verified_at')->nullable();
            $table->boolean('two_fa_enabled')->default(false);
            $table->text('two_fa_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            
            // Firebase Integration Fields
            $table->string('firebase_uid')->unique()->nullable();
            $table->string('firebase_email')->nullable();
            $table->timestamp('firebase_synced_at')->nullable();
            $table->json('firebase_metadata')->nullable();
            
            // Additional Profile Fields
            $table->string('profile_photo_url')->nullable();
            $table->text('bio')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('id_type', ['passport', 'national_id', 'drivers_license'])->nullable();
            
            // Account Status
            $table->enum('account_status', ['active', 'suspended', 'deactivated'])->default('active');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('firebase_uid');
            $table->index('role');
            $table->index('kyc_status');
            $table->index('account_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};