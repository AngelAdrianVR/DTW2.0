<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Quote extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'client_id',
        'user_id',
        'project_id',
        'quote_code',
        'title',
        'description',
        'amount',
        'status',
        'origin',
        'valid_until',
        'work_days',
        'percentage_discount',
        'payment_type',
        'show_process',
        'show_benefits',
        'show_bank_info',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'show_process' => 'boolean',
        'show_benefits' => 'boolean',
        'show_bank_info' => 'boolean',
    ];

    /**
     * Se añade un "booted method" para escuchar eventos del modelo.
     * Esta función se ejecuta automáticamente cuando el modelo es inicializado.
     */
    protected static function booted(): void
    {
        // Se define un listener para el evento 'updated' (se dispara después de actualizar una cotización).
        static::updated(function (Quote $quote) {
            // 1. Verificamos si el campo 'status' cambió y si el nuevo valor es 'Aceptado'.
            // El método wasChanged() comprueba si un atributo ha cambiado desde la última vez que se guardó.
            if ($quote->wasChanged('status') && $quote->status === 'Aceptado') {
                // 2. Obtenemos el cliente relacionado con esta cotización.
                $client = $quote->client;

                // 3. Si el cliente existe y su estado actual es 'Prospecto', lo actualizamos a 'Cliente'.
                if ($client && $client->status === 'Prospecto') {
                    $client->status = 'Cliente';
                    $client->save(); // Guardamos el cambio en la base de datos.
                }
            }
        });
    }

    /**
     * Se agrega el accesor 'final_amount' a la serialización del modelo.
     * Esto hace que el monto con descuento esté disponible automáticamente en el frontend.
     */
    protected $appends = ['final_amount'];

    /**
     * Accesor para obtener el monto final de la cotización aplicando el descuento.
     * Este valor se usará para todos los cálculos de ahora en adelante.
     *
     * @return float
     */
    public function getFinalAmountAttribute(): float
    {
        if ($this->percentage_discount > 0) {
            $discountAmount = $this->amount * ($this->percentage_discount / 100);
            return (float) ($this->amount - $discountAmount);
        }
        return (float) $this->amount;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

     public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }
    
    /**
     * MODIFICACIÓN: Se corrige el cálculo del saldo para usar el nuevo 'final_amount'.
     */
    public function getBalanceAttribute(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        // Se utiliza el accesor getFinalAmountAttribute() para el cálculo.
        return (float) $this->getFinalAmountAttribute() - $totalPaid;
    }

}
