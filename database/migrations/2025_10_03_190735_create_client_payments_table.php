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
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();

            // Relación con el cliente que realiza el pago.
            // Si el cliente se elimina, sus registros de pago también se eliminarán.
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');

            // Relación opcional con una cotización.
            // Si la cotización se elimina, el ID aquí se establecerá en nulo.
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null');

            $table->decimal('amount', 10, 2)->comment('Monto del pago');
            $table->date('payment_date')->comment('Fecha en que se realizó el pago');
            $table->text('notes')->nullable()->comment('Notas adicionales sobre el pago');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
