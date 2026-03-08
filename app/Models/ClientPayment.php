<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClientPayment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'quote_id',
        'amount',
        'payment_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Obtiene el cliente al que pertenece el pago.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Obtiene la cotización (opcional) asociada al pago.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        // 1. AQUÍ ES DONDE DEBES PEGAR LA LÍNEA
        // Asegúrate de ponerlo DESPUÉS de que $client se recibe en la función, 
        // y ANTES del return Inertia::render
        $client->load('contacts', 'quotes', 'payments.quote', 'payments.media');

        // 2. Probablemente ya tienes lógica aquí para calcular totales
        $total_billed = $client->quotes()->whereIn('status', ['Aceptado', 'Pagado'])->sum('final_amount');
        $total_paid = $client->payments()->sum('amount');

        // 3. Finalmente se envía a la vista Vue
        return Inertia::render('Clientes/ShowClient', [
            'client' => $client,
            'total_billed' => $total_billed,
            'total_paid' => $total_paid,
        ]);
    }
    
}