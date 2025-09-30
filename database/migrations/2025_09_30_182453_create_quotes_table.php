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
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('user_id')->constrained('users')->comment('Usuario que creó la cotización');
            $table->string('quote_code')->unique()->comment('Código único (ej. COT-2024-001)');
            $table->string('title');
            $table->longText('description')->nullable()->comment('Descripción detallada del servicio y alcances');
            $table->decimal('amount', 15, 2)->default(0.00)->comment('Monto total del servicio cotizado');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'in_negotiation'])->default('draft');
            $table->enum('origin', ['internal', 'website_form'])->default('internal');
            $table->date('valid_until')->nullable();
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
