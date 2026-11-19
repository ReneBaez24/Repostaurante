<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Momento en que el mesero pulsó "Liberar mesa" después de cobrar.
            // Mientras sea NULL y el pedido esté abierto o pagado, la mesa sigue ocupada.
            $table->timestamp('liberada_at')->nullable()->after('cerrado_at');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('liberada_at');
        });
    }
};
