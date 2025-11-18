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
        Schema::create('available_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['individual', 'grupal'])->default('individual');
            $table->enum('modality', ['presencial', 'virtual'])->default('presencial');
            $table->integer('max_participants')->default(1); // 1 para individual, N para grupal
            $table->integer('current_participants')->default(0);
            $table->decimal('price', 10, 2); // Precio calculado desde hourly_rate
            $table->enum('status', ['available', 'full', 'completed', 'cancelled'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Índice para búsquedas por fecha y profesional
            $table->index(['professional_id', 'date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_slots');
    }
};
