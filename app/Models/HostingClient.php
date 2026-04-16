<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingClient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'hosting_type', // 'Interno' (Cobramos), 'Externo' (Solo registro)
        'service_provider',
        'support_credentials', // <-- Nuevo campo JSON para múltiples credenciales
        'start_date',
        'next_payment_date',
        'payment_amount',
        'billing_cycle',
        'hosted_urls',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'hosted_urls' => 'array',
        'support_credentials' => 'array', // Convertimos el JSON a arreglo automáticamente
    ];

    /**
     * Define la relación con el modelo Client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Define la relación con los pagos de hosting.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(HostingPayment::class);
    }
}