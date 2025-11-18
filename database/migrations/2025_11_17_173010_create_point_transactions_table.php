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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points'); // Positivo = ganados, Negativo = gastados
            $table->string('type'); // earned, spent, expired, bonus
            $table->string('source'); // purchase, referral, achievement, admin, etc.
            $table->foreignId('reference_id')->nullable(); // ID de producto, orden, etc.
            $table->string('reference_type')->nullable(); // Product, Order, etc.
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
