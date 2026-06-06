<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\tpspProduct;
use App\Models\tpspProductionOrder;
use App\Models\tpspKitComponent;
use App\Models\tpspInventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TpspProductionOrderTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Helper para inicializar un entorno base con un Kit y un Insumo relacionado.
     */
    private function createBaseKitEnvironment($insumoStock = 100, $qtyRequired = 3)
    {
        $insumo = tpspProduct::create([
            'name' => 'Insumo Base',
            'sku' => 'INS-01',
            'category' => 'Insumo',
            'unit_of_measure' => 'Pieza',
            'stock' => $insumoStock,
            'is_kit' => false,
            'is_public' => true
        ]);

        $kit = tpspProduct::create([
            'name' => 'Kit Terminado',
            'sku' => 'KIT-01',
            'category' => 'Kit Terminado',
            'unit_of_measure' => 'Kit',
            'stock' => 0,
            'is_kit' => true,
            'is_public' => true
        ]);

        tpspKitComponent::create([
            'kit_product_id' => $kit->id,
            'component_product_id' => $insumo->id,
            'quantity_required' => $qtyRequired
        ]);

        return [$insumo, $kit];
    }

    /** @test */
    public function can_store_production_order_and_generates_correct_folio()
    {
        list($insumo, $kit) = $this->createBaseKitEnvironment();

        $response = $this->postJson('/tpsp/production-orders', [
            'product_id' => $kit->id,
            'quantity_requested' => 10,
            'due_date' => now()->addDays(5)->format('Y-m-d')
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'order_number', 'status']);
        
        $this->assertDatabaseHas('tpsp_production_orders', [
            'product_id' => $kit->id,
            'order_number' => 'TPSP-0001',
            'status' => 'Pendiente',
            'quantity_requested' => 10
        ]);
    }

    /** @test */
    public function cannot_update_order_meta_below_already_produced_quantity()
    {
        list($insumo, $kit) = $this->createBaseKitEnvironment(100, 1);

        $order = tpspProductionOrder::create([
            'product_id' => $kit->id,
            'quantity_requested' => 10,
            'quantity_produced' => 5,
            'order_number' => 'TPSP-0001',
            'status' => 'En Progreso'
        ]);

        $response = $this->putJson("/tpsp/production-orders/{$order->id}", [
            'quantity_requested' => 4,
            'due_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'No puedes reducir la cantidad solicitada por debajo de lo ya producido (5 unidades).']);
    }

    /** @test */
    public function add_progress_consumes_insumos_and_increments_kit_stock_correctly()
    {
        list($insumo, $kit) = $this->createBaseKitEnvironment(100, 3);

        $order = tpspProductionOrder::create([
            'product_id' => $kit->id,
            'quantity_requested' => 10,
            'quantity_produced' => 0,
            'order_number' => 'TPSP-0001',
            'status' => 'Pendiente'
        ]);

        $response = $this->postJson("/tpsp/production-orders/{$order->id}/add-progress", [
            'quantity' => 2
        ]);

        $response->assertStatus(200);

        $this->assertEquals(94, $insumo->fresh()->stock);
        $this->assertEquals(2, $kit->fresh()->stock);

        $this->assertDatabaseHas('tpsp_production_orders', [
            'id' => $order->id,
            'quantity_produced' => 2,
            'status' => 'En Progreso'
        ]);

        $this->assertDatabaseHas('tpsp_inventory_movements', [
            'product_id' => $insumo->id,
            'quantity' => -6,
            'type' => 'Consumo_Produccion'
        ]);

        $this->assertDatabaseHas('tpsp_inventory_movements', [
            'product_id' => $kit->id,
            'quantity' => 2,
            'type' => 'Entrada_Produccion'
        ]);
    }

    /** @test */
    public function add_progress_fails_if_insumo_stock_is_insufficient()
    {
        list($insumo, $kit) = $this->createBaseKitEnvironment(2, 3);

        $order = tpspProductionOrder::create([
            'product_id' => $kit->id,
            'quantity_requested' => 5,
            'quantity_produced' => 0,
            'order_number' => 'TPSP-0001',
            'status' => 'Pendiente'
        ]);

        $response = $this->postJson("/tpsp/production-orders/{$order->id}/add-progress", [
            'quantity' => 1
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function delivering_order_discounts_kit_stock_and_can_complete_the_order()
    {
        list($insumo, $kit) = $this->createBaseKitEnvironment();
        $kit->update(['stock' => 5]);

        $order = tpspProductionOrder::create([
            'product_id' => $kit->id,
            'quantity_requested' => 5,
            'quantity_produced' => 5,
            'quantity_delivered' => 0,
            'order_number' => 'TPSP-0001',
            'status' => 'En Progreso'
        ]);

        $response = $this->postJson("/tpsp/production-orders/{$order->id}/deliver", [
            'quantity' => 5,
            'delivery_date' => now()->format('Y-m-d'),
            'unit_price' => 150.00
        ]);

        $response->assertStatus(200);

        $this->assertEquals(0, $kit->fresh()->stock);

        $this->assertDatabaseHas('tpsp_production_orders', [
            'id' => $order->id,
            'quantity_delivered' => 5,
            'status' => 'Completado'
        ]);

        $this->assertDatabaseHas('tpsp_inventory_movements', [
            'product_id' => $kit->id,
            'quantity' => -5,
            'type' => 'Venta',
            'total_price' => 750.00,
            'amount_paid' => 0.00
        ]);
    }
}
