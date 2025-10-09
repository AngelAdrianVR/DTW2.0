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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('user_id')->nullable()->constrained('users')->comment('Usuario que creó la cotización');
            $table->unsignedInteger('project_id')->nullable();

            $table->string('client_name')->nullable(); // En caso de que el origen sea 'Web'
            $table->string('client_email')->nullable(); // En caso de que el origen sea 'Web'
            $table->string('client_phone')->nullable(); // En caso de que el origen sea 'Web'

            $table->string('quote_code')->nullable()->unique()->comment('Código único (ej. COT-2024-001)');
            $table->unsignedTinyInteger('work_days')->nullable(); // Total de días de trabajo estimados
            $table->unsignedTinyInteger('percentage_discount')->nullable(); // Porcentage de descuento
            $table->string('payment_type')->nullable(); // Forma de pago (ej. 50% anticipo, 50% contra entrega)
            $table->string('title')->nullable();
            $table->longText('description')->nullable()->comment('Descripción detallada del servicio y alcances');
            $table->decimal('amount', 15, 2)->default(0.00)->comment('Monto total del servicio cotizado');
            $table->enum('status', ['Pendiente', 'Enviado', 'Aceptado', 'Rechazado', "Pagado"])->default('Pendiente');
            $table->enum('origin', ['Interno', 'Web'])->default('Interno');
            $table->date('valid_until')->nullable();
            $table->boolean('show_process')->default(false);
            $table->boolean('show_benefits')->default(false);
            $table->boolean('show_bank_info')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
