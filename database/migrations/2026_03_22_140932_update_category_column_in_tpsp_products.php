<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // SQLite no soporta MODIFY COLUMN, y los ENUM se almacenan como VARCHAR de todos modos
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tpsp_products MODIFY COLUMN category VARCHAR(255) NOT NULL");
        }
    }

    public function down()
    {
        // Opcional: Revertir si es necesario
    }
};