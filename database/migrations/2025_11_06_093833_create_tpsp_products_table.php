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
        Schema::create('tpsp_products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Bálsamo Pedico", "Kit Pro Salud"
            $table->string('sku')->unique()->nullable(); // Código de producto
            $table->text('description')->nullable();
            
            $table->boolean('is_kit')->default(false); 
            
            // Campo 'category' como ENUM en español
            $table->enum('category', ['Material', 'Insumo', 'Empaque', 'Kit Terminado'])
                  ->nullable()
                  ->comment('Clasificación del producto');

            $table->integer('stock')->default(0); 
            
            // Campo 'unit_of_measure' como ENUM en español
            $table->enum('unit_of_measure', ['Pieza', 'Mililitro', 'Gramo', 'Kit'])
                  ->default('Pieza');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpsp_products');
    }
};
