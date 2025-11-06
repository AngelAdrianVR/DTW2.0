<?php

namespace App\Models;

use App\Models\Tpsp\tpspProduct;
use Illuminate\Database\Eloquent\Model;

class tpspInventoryMovement extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tpsp_inventory_movements';

    /**
     * Evita la asignación masiva.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Casts para el campo enum.
     */
    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Obtiene el producto asociado al movimiento.
     */
    public function product()
    {
        return $this->belongsTo(tpspProduct::class, 'product_id');
    }

    /**
     * Obtiene el modelo (Orden de Producción, Compra, etc.) que originó el movimiento.
     */
    public function reference()
    {
        return $this->morphTo();
    }
}
