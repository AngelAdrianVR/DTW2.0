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
        Schema::create('tpsp_inventory_movements', function (Blueprint $table) {
            $table->id();

            // Clave foránea apuntando a la tabla con prefijo 'tpsp_products'
            $table->foreignId('product_id')->constrained('tpsp_products');
            
            $table->integer('quantity'); // Positivo para entradas, Negativo para salidas

            // Campo 'type' como ENUM en español (usando _ para seguridad)
            $table->enum('type', [
                'Entrada de material', 
                'Consumo_Produccion', 
                'Entrada_Produccion', 
                'Venta', 
                'Ajuste'
            ])->comment('Tipo de movimiento de inventario');

            // --- CAMPOS NUEVOS ---
            // Guardar el precio unitario al momento de la venta
            $table->decimal('unit_price', 10, 2)->nullable()->comment('Precio unitario al momento de la venta');
            // Guardar el precio total (cantidad * unit_price) para reportes
            $table->decimal('total_price', 10, 2)->nullable()->comment('Monto total de la venta');
            // --- FIN DE CAMPOS NUEVOS ---

            $table->morphs('reference'); // Ej: ProductionOrder, PurchaseOrder
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpsp_inventory_movements');
    }
};
