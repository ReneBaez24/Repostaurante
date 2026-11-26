<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_barras', 14)->unique();   // EAN-8, UPC-A, EAN-13 o GTIN-14
            $table->string('nombre', 150);
            $table->string('marca', 100)->nullable();
            $table->string('presentacion', 60)->nullable();   // "400 g", "1 L"
            $table->text('ingredientes')->nullable();          // lista de ingredientes del empaque
            $table->json('alergenos')->nullable();             // ["Leche", "Soya"]
            $table->char('nutriscore', 1)->nullable();         // A–E
            $table->json('nutrimentos')->nullable();           // por 100 g: energia_kcal, grasas, azucares, sal
            $table->string('imagen_url', 500)->nullable();
            $table->decimal('existencias', 10, 2)->default(0);
            $table->string('unidad', 20)->default('pieza');
            $table->string('origen', 20)->default('openfoodfacts'); // openfoodfacts | manual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredientes');
    }
};
