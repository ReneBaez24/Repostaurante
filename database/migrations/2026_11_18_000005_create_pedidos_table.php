<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id(); // se muestra como folio: #024
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // quién abrió la mesa
            $table->unsignedTinyInteger('comensales');
            $table->string('estado', 20)->default('abierto'); // abierto | pagado | cancelado
            $table->timestamp('abierto_at');
            $table->timestamp('cerrado_at')->nullable();
            $table->timestamps();

            $table->index(['mesa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
