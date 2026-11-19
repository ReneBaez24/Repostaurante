<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedSmallInteger('cantidad');
            // Precio copiado al momento de pedir: si el admin cambia el menú,
            // las cuentas abiertas o pasadas no se alteran.
            $table->decimal('precio_unitario', 10, 2);
            $table->string('notas')->nullable(); // p. ej. "Salsa BBQ aparte"
            $table->string('estado', 20)->default('sin_enviar'); // sin_enviar | en_preparacion | preparado
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('preparado_at')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
