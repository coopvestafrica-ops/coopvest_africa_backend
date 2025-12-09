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
        Schema::table('users', function (Blueprint $table) {
            // Firebase authentication fields
            $table->string('firebase_uid')->nullable()->unique()->after('id');
            $table->string('name')->nullable()->after('firebase_uid');
            $table->string('phone_number')->nullable()->after('phone');
            
            // Firebase verification and status fields
            $table->boolean('firebase_email_verified')->default(false)->after('email');
            $table->boolean('firebase_disabled')->default(false)->after('firebase_email_verified');
            
            // Firebase metadata and custom claims
            $table->json('firebase_metadata')->nullable()->after('firebase_disabled');
            $table->json('firebase_custom_claims')->nullable()->after('firebase_metadata');
            
            // Add indexes for better query performance
            $table->index('firebase_uid');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['firebase_uid']);
            $table->dropIndex(['email']);
            $table->dropColumn([
                'firebase_uid',
                'name',
                'phone_number',
                'firebase_email_verified',
                'firebase_disabled',
                'firebase_metadata',
                'firebase_custom_claims',
            ]);
        });
    }
};
