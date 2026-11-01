<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Puede venir de un registro del formulario o capturarse a mano
            // (por eso es nullable y el nombre se guarda como snapshot).
            $table->foreignId('event_registration_id')
                ->nullable()
                ->constrained('event_registrations')
                ->nullOnDelete();

            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('spanish_prize');
            $table->string('english_prize')->nullable();
            $table->string('spanish_note')->nullable();
            $table->string('english_note')->nullable();
            $table->date('awarded_at')->nullable();

            // Permite cargar al ganador sin revelarlo todavía en la landing.
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_winners');
    }
};
