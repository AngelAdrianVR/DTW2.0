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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // Clave foránea para el cliente. Nulable para proyectos internos.
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            // Clave foránea para la cotización. Nulable si el proyecto no se origina de una.
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['Pendiente', 'En proceso', 'Completado', 'Pausado', 'Cancelado'])->default('Pendiente');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            // Nuevo campo: Almacena el total de minutos invertidos en el proyecto.
            // Se actualiza cada vez que un registro de tiempo se completa. Mejora el rendimiento.
            $table->unsignedInteger('total_invested_minutes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
