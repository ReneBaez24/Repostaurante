<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->unsignedTinyInteger('capacidad')->default(4); // sillas
            $table->string('zona', 20)->default('interior');     // interior | terraza
            $table->boolean('activa')->default(true);             // el admin puede retirar una mesa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
