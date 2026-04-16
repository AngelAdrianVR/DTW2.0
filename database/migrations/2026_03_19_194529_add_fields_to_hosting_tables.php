<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('hosting_clients', function (Blueprint $table) {
        $table->string('hosting_type')->default('Interno')->after('client_id');
        $table->string('support_user')->nullable()->after('service_provider');
        $table->string('support_password')->nullable()->after('support_user');
        
        // Hacer nulos los de cobro para cuando es externo
        $table->date('next_payment_date')->nullable()->change();
        $table->decimal('payment_amount', 10, 2)->nullable()->change();
        $table->string('billing_cycle')->nullable()->change();
    });

    Schema::table('hosting_payments', function (Blueprint $table) {
        $table->string('receipt_path')->nullable()->after('notes');
    });
}

public function down()
{
    Schema::table('hosting_clients', function (Blueprint $table) {
        $table->dropColumn(['hosting_type', 'support_user', 'support_password']);
    });

    Schema::table('hosting_payments', function (Blueprint $table) {
        $table->dropColumn('receipt_path');
    });
}
};
