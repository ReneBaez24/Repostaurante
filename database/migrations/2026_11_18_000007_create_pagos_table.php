<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // quién cobró
            $table->string('metodo', 20); // tarjeta | efectivo
            $table->decimal('consumo', 10, 2);
            $table->decimal('impuesto', 10, 2);           // IVA incluido en el consumo (informativo)
            $table->unsignedTinyInteger('propina_porcentaje')->default(0);
            $table->decimal('propina', 10, 2)->default(0);
            $table->decimal('total', 10, 2);              // consumo + propina
            $table->boolean('imprimir_comprobante')->default(true);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
