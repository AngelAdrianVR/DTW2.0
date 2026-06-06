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
        // 1. Agregar régimen fiscal a la tabla de clientes
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('regimen_fiscal', ['persona_fisica', 'persona_moral'])
                ->default('persona_fisica')
                ->after('source')
                ->comment('Régimen fiscal del cliente (RESICO)');
        });

        // 2. Agregar retención ISR a la tabla de cotizaciones
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('isr_retention', 12, 2)
                ->nullable()
                ->default(0.00)
                ->after('needs_invoice')
                ->comment('Retención ISR 1.25% para Persona Moral (RESICO)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('regimen_fiscal');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('isr_retention');
        });
    }
};
