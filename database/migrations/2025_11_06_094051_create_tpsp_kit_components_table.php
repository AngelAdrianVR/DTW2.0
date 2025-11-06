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
        Schema::create('tpsp_kit_components', function (Blueprint $table) {
            $table->id();

            // Claves foráneas apuntando a la tabla con prefijo 'tpsp_products'
            $table->foreignId('kit_product_id')->constrained('tpsp_products')->onDelete('cascade');
            $table->foreignId('component_product_id')->constrained('tpsp_products')->onDelete('cascade');

            $table->decimal('quantity_required', 10, 2);
            $table->timestamps();
            
            $table->unique(['kit_product_id', 'component_product_id'], 'kit_component_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpsp_kit_components');
    }
};
