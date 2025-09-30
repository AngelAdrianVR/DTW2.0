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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->morphs('contactable'); // Esto crea contactable_id y contactable_type
            $table->string('name')->comment('Nombre de la persona de contacto');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable()->comment('Puesto en la empresa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
