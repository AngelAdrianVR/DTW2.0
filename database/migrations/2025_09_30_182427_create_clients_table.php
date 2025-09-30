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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre de la empresa');
            $table->string('tax_id')->nullable()->comment('RFC, NIF, etc.');
            $table->text('address')->nullable();
            $table->enum('status', ['Cliente', 'Prospecto'])->default('Prospecto');
            $table->string('source')->nullable()->comment('Origen del contacto (ej. Website, Referencia)');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->comment('Usuario interno asignado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
