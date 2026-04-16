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
        Schema::table('quotes', function (Blueprint $table) {
            // Agregamos la columna 'amount_usd' después de 'amount'
            // Es 'nullable' porque es un dato opcional
            $table->decimal('amount_usd', 10, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Eliminamos la columna si revertimos la migración
            $table->dropColumn('amount_usd');
        });
    }
};