<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamp('contribution_date');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('status');
        });

        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('rate', 5, 2)->default(8.5);
            $table->decimal('total_interest_earned', 15, 2)->default(0);
            $table->timestamp('last_interest_date')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings');
        Schema::dropIfExists('contributions');
    }
};
