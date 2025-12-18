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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'super_admin', 'admin', 'moderator'
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('level')->default(0); // Hierarchy level: 0 = super_admin, 1 = admin, 2 = moderator
            $table->json('permissions')->default('[]'); // Array of permission slugs
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
