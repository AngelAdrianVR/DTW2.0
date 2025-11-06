<?php

namespace App\Models;

use App\Models\tpspProduct;
use Illuminate\Database\Eloquent\Model;

class tpspKitComponent extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tpsp_kit_components';

    /**
     * Evita la asignación masiva.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * El timestamp no es necesario para esta tabla pivote (opcional).
     *
     * @var bool
     */
    // public $timestamps = false;

    /**
     * Obtiene el producto Kit (padre).
     */
    public function kit()
    {
        return $this->belongsTo(tpspProduct::class, 'kit_product_id');
    }

    /**
     * Obtiene el producto componente (hijo).
     */
    public function component()
    {
        return $this->belongsTo(tpspProduct::class, 'component_product_id');
    }
}
