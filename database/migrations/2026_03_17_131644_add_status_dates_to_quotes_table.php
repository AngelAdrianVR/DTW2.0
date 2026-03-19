<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('quotes', function (Blueprint $table) {
        $table->timestamp('sent_at')->nullable();
        $table->timestamp('accepted_at')->nullable();
        $table->timestamp('rejected_at')->nullable();
        $table->timestamp('paid_at')->nullable();
    });
}

public function down()
{
    Schema::table('quotes', function (Blueprint $table) {
        $table->dropColumn(['sent_at', 'accepted_at', 'rejected_at', 'paid_at']);
    });
}
};
