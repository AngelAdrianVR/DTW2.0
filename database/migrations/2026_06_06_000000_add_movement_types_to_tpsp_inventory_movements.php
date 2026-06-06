<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega 'Compra' y 'Devolución de producto' al ENUM de la columna 'type'.
     */
    public function up(): void
    {
        // MySQL no permite alterar ENUM directamente con Schema, usamos raw SQL.
        DB::statement("ALTER TABLE tpsp_inventory_movements MODIFY COLUMN type ENUM(
            'Entrada de material',
            'Consumo_Produccion',
            'Entrada_Produccion',
            'Venta',
            'Ajuste',
            'Compra',
            'Devolución de producto'
        ) NOT NULL COMMENT 'Tipo de movimiento de inventario'");
    }

    /**
     * Revierte al ENUM original (sin Compra ni Devolución de producto).
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tpsp_inventory_movements MODIFY COLUMN type ENUM(
            'Entrada de material',
            'Consumo_Produccion',
            'Entrada_Produccion',
            'Venta',
            'Ajuste'
        ) NOT NULL COMMENT 'Tipo de movimiento de inventario'");
    }
};
