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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            // Usuario asignado a la tarea.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->unsignedInteger('total_invested_minutes')->default(0);
            // Modificación: Se añade el estado 'Pausada' para un control de tiempo preciso.
            $table->enum('status', ['Pendiente', 'En proceso', 'Pausada', 'Completada'])->default('Pendiente');
            $table->enum('priority', ['Baja', 'Media', 'Alta'])->default('Media');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
