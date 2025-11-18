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
        Schema::create('client_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat', 5, 2)->nullable(); // porcentaje
            $table->decimal('muscle_mass', 5, 2)->nullable(); // kg
            $table->text('notes')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_measurements');
    }
};
