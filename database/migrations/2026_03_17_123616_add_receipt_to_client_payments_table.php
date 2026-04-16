<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('client_payments', function (Blueprint $table) {
            // Agrega la columna receipt que puede ser nula
            $table->string('receipt')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->dropColumn('receipt');
        });
    }
};
