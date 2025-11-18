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
        Schema::create('session_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade'); // Cliente que compra
            $table->foreignId('professional_id')->constrained('users')->onDelete('cascade'); // Entrenador/Nutricionista
            $table->string('service_type'); // training, nutrition
            $table->string('package_name');
            $table->text('description')->nullable();
            $table->integer('total_sessions'); // Número de sesiones en el paquete
            $table->integer('completed_sessions')->default(0);
            $table->decimal('price_per_session', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['pending_payment', 'active', 'completed', 'cancelled'])->default('pending_payment');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_packages');
    }
};
