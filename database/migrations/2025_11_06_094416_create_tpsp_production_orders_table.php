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
        Schema::create('tpsp_production_orders', function (Blueprint $table) {
            $table->id();

            // Clave foránea apuntando a la tabla con prefijo 'tpsp_products'
            $table->foreignId('product_id')->constrained('tpsp_products');

            $table->string('order_number')->unique()->nullable();
            
            $table->integer('quantity_requested');
            $table->integer('quantity_produced')->default(0);

            // Campo 'status' como ENUM en español
            $table->enum('status', ['Pendiente', 'En Progreso', 'Completado', 'Cancelado'])
                  ->default('Pendiente');
            
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpsp_production_orders');
    }
};
