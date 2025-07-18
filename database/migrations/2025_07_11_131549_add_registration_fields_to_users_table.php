<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('objetivo')->nullable(); // cliente
        $table->text('especialidades')->nullable(); // entrenador/nutri (JSON)
        $table->decimal('tarifa', 10, 2)->nullable(); // entrenador/nutri
        $table->string('moneda')->nullable(); // entrenador/nutri
        $table->string('periodo_facturacion')->nullable(); // entrenador/nutri
        $table->string('telefono')->nullable(); // gimnasio
        $table->string('direccion')->nullable(); // gimnasio
        $table->text('descripcion')->nullable(); // gimnasio
        $table->string('horario')->nullable(); // gimnasio
        $table->text('instalaciones')->nullable(); // gimnasio
        $table->decimal('lat', 10, 6)->nullable(); // ubicación
        $table->decimal('lng', 10, 6)->nullable();
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'objetivo', 'especialidades', 'tarifa', 'moneda', 'periodo_facturacion',
            'telefono', 'direccion', 'descripcion', 'horario', 'instalaciones', 'lat', 'lng'
        ]);
    });
}


};
