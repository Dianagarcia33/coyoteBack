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
        Schema::create('gym_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->string('gym_type')->nullable(); // Funcional, CrossFit, Boutique, etc.
            $table->json('specialties')->nullable(); // ["Musculación", "Cardio", "Yoga"]
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('opening_hours')->nullable();
            $table->json('machines')->nullable(); // [{"name": "Caminadora", "quantity": 5}]
            $table->json('classes')->nullable(); // [{"name": "Spinning", "schedule": "Lun-Vie 6am"}]
            $table->text('description')->nullable();
            $table->json('photos')->nullable(); // ["url1", "url2"]
            $table->json('social_media')->nullable(); // {"instagram": "@gym", "facebook": "..."}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_profiles');
    }
};
