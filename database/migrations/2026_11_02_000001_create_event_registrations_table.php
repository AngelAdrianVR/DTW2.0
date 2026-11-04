<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('phone', 50);

            // Snapshot de la pregunta variable del evento: si mañana se cambia
            // la etiqueta, cada registro conserva la que respondió.
            $table->string('custom_field_label')->nullable();
            $table->text('custom_answer')->nullable();

            $table->string('locale', 5)->default('es');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            // Un teléfono = un boleto por evento: mantiene el sorteo limpio.
            $table->unique(['event_id', 'phone']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
