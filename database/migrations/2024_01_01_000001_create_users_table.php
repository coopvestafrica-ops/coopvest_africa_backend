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
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
            $table->index('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
