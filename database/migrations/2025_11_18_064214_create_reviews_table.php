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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quien recibe la review
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade'); // Quien hace la review
            $table->integer('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();
            
            // Un cliente solo puede dejar una review por profesional
            $table->unique(['user_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
