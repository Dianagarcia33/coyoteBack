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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('available_slots')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('status', [
                'pending_confirmation', // Esperando confirmación del profesional
                'confirmed',            // Profesional confirmó
                'rejected',             // Profesional rechazó
                'expired',              // No respondió a tiempo
                'completed',            // Sesión realizada
                'cancelled_by_client',  // Cliente canceló
                'cancelled_by_professional', // Profesional canceló
                'no_show'              // Cliente no asistió
            ])->default('pending_confirmation');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('professional_notes')->nullable();
            $table->timestamps();
            
            // Un cliente solo puede reservar una vez el mismo slot
            $table->unique(['slot_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
