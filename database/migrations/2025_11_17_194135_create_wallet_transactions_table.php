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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['payment_received', 'session_completed', 'withdrawal', 'refund', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->enum('balance_type', ['frozen', 'available']); // A qué balance afecta
            $table->string('description');
            $table->foreignId('reference_id')->nullable(); // ID de SessionPackage, Withdrawal, etc.
            $table->string('reference_type')->nullable(); // SessionPackage, Withdrawal, etc.
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
