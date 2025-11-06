<?php

namespace App\Models;

use App\Models\tpspProduct;
use Illuminate\Database\Eloquent\Model;

class tpspProductionOrder extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tpsp_production_orders';

    /**
     * Evita la asignación masiva.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Casts para los campos enum y fechas.
     */
    protected $casts = [
        'status' => 'string',
        'due_date' => 'date',
    ];

    /**
     * Obtiene el producto (Kit) que se está produciendo.
     */
    public function product()
    {
        return $this->belongsTo(tpspProduct::class, 'product_id');
    }

    /**
     * Obtiene los movimientos de inventario asociados a esta orden de producción.
     */
    public function inventoryMovements()
    {
        return $this->morphMany(tpspInventoryMovement::class, 'reference');
    }
}
