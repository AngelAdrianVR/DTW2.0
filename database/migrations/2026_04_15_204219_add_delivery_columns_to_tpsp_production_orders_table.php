<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::table('tpsp_production_orders', function (Blueprint $table) {
            // Se agrega quantity_produced si no existe (usada en addProgress)
            if (!Schema::hasColumn('tpsp_production_orders', 'quantity_produced')) {
                $table->integer('quantity_produced')->default(0)->after('quantity_requested');
            }
            
            // Se agrega quantity_delivered si no existe (la que está causando el error)
            if (!Schema::hasColumn('tpsp_production_orders', 'quantity_delivered')) {
                $table->integer('quantity_delivered')->default(0)->after('quantity_produced');
            }
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('tpsp_production_orders', function (Blueprint $table) {
            $table->dropColumn(['quantity_produced', 'quantity_delivered']);
        });
    }
};