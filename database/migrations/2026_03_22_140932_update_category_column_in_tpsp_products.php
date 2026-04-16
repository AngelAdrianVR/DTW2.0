<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Cambiamos la columna de ENUM a VARCHAR(255) para que acepte cualquier texto
        DB::statement("ALTER TABLE tpsp_products MODIFY COLUMN category VARCHAR(255) NOT NULL");
    }

    public function down()
    {
        // Opcional: Revertir si es necesario
    }
};