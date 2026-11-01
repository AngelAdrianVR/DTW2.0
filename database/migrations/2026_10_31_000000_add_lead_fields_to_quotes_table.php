<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos adicionales para los leads que llegan desde la landing
     * (empresa, giro, tamaño y el resultado de la calculadora financiera).
     * Todas las columnas son nullable para no romper cotizaciones existentes.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('company')->nullable();
            $table->string('industry')->nullable();
            $table->string('employees')->nullable();
            $table->json('calculator_payload')->nullable();
            $table->string('locale', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['company', 'industry', 'employees', 'calculator_payload', 'locale']);
        });
    }
};
