<?php

namespace App\Models;

use App\Models\tpspInventoryMovement;
use App\Models\tpspKitComponent;
use App\Models\tpspProductionOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class tpspProduct extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tpsp_products';

    /**
     * Evita la asignación masiva.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Casts para los campos enum.
     */
    protected $casts = [
        'is_kit' => 'boolean',
        'category' => 'string', // Puedes crear un Enum Cast en Laravel si lo prefieres
        'unit_of_measure' => 'string',
    ];

    /**
     * Obtiene los componentes si este producto es un Kit.
     */
    public function components()
    {
        return $this->hasMany(tpspKitComponent::class, 'kit_product_id');
    }

    /**
     * Obtiene los kits en los que este producto es un componente.
     */
    public function componentIn()
    {
        return $this->hasMany(tpspKitComponent::class, 'component_product_id');
    }

    /**
     * Obtiene las órdenes de producción para este producto (Kit).
     */
    public function productionOrders()
    {
        return $this->hasMany(tpspProductionOrder::class, 'product_id');
    }

    /**
     * Obtiene todos los movimientos de inventario del producto.
     */
    public function inventoryMovements()
    {
        return $this->hasMany(tpspInventoryMovement::class, 'product_id');
    }
}