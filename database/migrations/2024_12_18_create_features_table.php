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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'loan_application', 'guarantor_system'
            $table->string('slug')->unique(); // e.g., 'loan-application', 'guarantor-system'
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('category')->default('general'); // e.g., 'web', 'mobile', 'both'
            $table->json('platforms')->default('["web", "mobile"]'); // Platforms where feature is available
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
