<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('hosting_clients', function (Blueprint $table) {
        // Agregamos el nuevo campo JSON para soportar múltiples credenciales
        $table->json('support_credentials')->nullable()->after('service_provider');
        
        // Hacemos que la fecha no sea obligatoria a nivel BD
        $table->date('start_date')->nullable()->change();

        // Opcional: Eliminar las viejas (descomenta si no tienes nada importante ahí)
        // $table->dropColumn(['support_user', 'support_password']);
    });
}

public function down()
{
    Schema::table('hosting_clients', function (Blueprint $table) {
        $table->dropColumn('support_credentials');
    });
}
};
