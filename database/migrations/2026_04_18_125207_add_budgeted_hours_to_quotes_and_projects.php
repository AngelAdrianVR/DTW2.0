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
        // Añadir el campo a la tabla de cotizaciones (quotes)
        Schema::table('quotes', function (Blueprint $table) {
            $table->integer('budgeted_hours')->nullable()->after('work_days');
        });

        // Añadir el campo a la tabla de proyectos (projects)
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('budgeted_hours')->nullable()->after('budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios si se hace un rollback
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('budgeted_hours');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('budgeted_hours');
        });
    }
};