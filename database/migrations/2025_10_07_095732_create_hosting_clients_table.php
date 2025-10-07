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
        Schema::create('hosting_clients', function (Blueprint $table) {
            $table->id(); // ID único para cada cliente
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // Relación con la tabla de clientes
            $table->string('service_provider'); // Proveedor del servicio de hosting

            $table->date('start_date'); // Fecha de inicio del servicio de hosting
            $table->date('next_payment_date'); // Próxima fecha de pago

            // Usamos decimal para manejar montos monetarios de forma precisa
            $table->decimal('payment_amount', 8, 2); // Monto a pagar

            // El ciclo de pago puede ser mensual, anual, etc.
            $table->enum('billing_cycle', ['Mensual', 'Anual'])->default('Mensual');

            // Usamos un tipo JSON para almacenar múltiples URLs de forma flexible
            $table->json('hosted_urls')->nullable(); // URLs de las páginas alojadas

            // Estado actual del cliente en el servicio
            $table->enum('status', ['Activo', 'Suspendido', 'Cancelado'])->default('Activo');

            $table->text('notes')->nullable(); // Campo para notas adicionales
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_clients');
    }
};
