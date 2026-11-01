<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Contenido bilingüe: el back-office guarda ambos idiomas y la
            // landing elige uno según el locale activo (igual que WebContent).
            $table->string('spanish_title');
            $table->string('english_title')->nullable();
            $table->text('spanish_description')->nullable();
            $table->text('english_description')->nullable();
            $table->text('spanish_challenge')->nullable();
            $table->text('english_challenge')->nullable();
            $table->text('spanish_prizes_summary')->nullable();
            $table->text('english_prizes_summary')->nullable();

            // Fecha del evento (la que se pinta en el historial) y ventana de
            // registro: si `registration_ends_at` es null el registro nunca
            // cierra por tiempo.
            $table->date('event_date')->nullable();
            $table->timestamp('registration_starts_at')->nullable();
            $table->timestamp('registration_ends_at')->nullable();

            // Campo variable del formulario: cada evento pide un dato distinto
            // (problemática, descripción del negocio, etc.).
            $table->string('custom_field_label_es')->nullable();
            $table->string('custom_field_label_en')->nullable();
            $table->string('custom_field_placeholder_es')->nullable();
            $table->string('custom_field_placeholder_en')->nullable();
            $table->boolean('custom_field_required')->default(false);

            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
