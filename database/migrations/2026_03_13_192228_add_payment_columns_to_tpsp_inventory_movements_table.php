<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tpsp_inventory_movements', function (Blueprint $table) {
            // Agregamos las dos columnas nuevas
            $table->decimal('amount_paid', 10, 2)->nullable()->default(0)->after('total_price');
            $table->date('payment_date')->nullable()->after('amount_paid');
        });
    }

    public function down()
    {
        Schema::table('tpsp_inventory_movements', function (Blueprint $table) {
            // Permite revertir la migración si es necesario
            $table->dropColumn(['amount_paid', 'payment_date']);
        });
    }
};