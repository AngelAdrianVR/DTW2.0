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
        'amount_usd',
        'status',
        'origin',
        'valid_until',
        'work_days',
        'percentage_discount',
        'payment_type',
        'show_process',
        'show_benefits',
        'show_bank_info',
        'show_tax_breakdown',
        'budgeted_hours',
        'needs_invoice',
        'isr_retention',
        'aplica_retencion',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'paid_at',
        'client_name',
        'client_email',
        'client_phone',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'budgeted_hours' => 'integer',
        'show_process' => 'boolean',
        'show_benefits' => 'boolean',
        'show_bank_info' => 'boolean',
        'show_tax_breakdown' => 'boolean',
        'needs_invoice' => 'boolean',
        'amount' => 'float',
        'amount_usd' => 'float',
        'isr_retention' => 'float',
        'aplica_retencion' => 'boolean',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (Quote $quote) {
            if ($quote->wasChanged('status') && $quote->status === 'Aceptado') {
                $client = $quote->client;
                if ($client && $client->status === 'Prospecto') {
                    $client->status = 'Cliente';
                    $client->save();
                }

            }
        });
    }

    protected $appends = ['subtotal', 'final_amount'];

    /**
     * Subtotal: Monto base después de aplicar descuento, antes de IVA e ISR.
     */
    public function getSubtotalAttribute(): float
    {
        $amount = (float) ($this->amount ?? 0);
        $discount = (float) ($this->percentage_discount ?? 0);

        if ($discount > 0) {
            $amount = $amount - ($amount * ($discount / 100));
        }

        return round($amount, 2);
    }

    public function getFinalAmountAttribute(): float
    {
        $amount = $this->getSubtotalAttribute();

        // IVA interno (16%) si requiere factura
        if ($this->needs_invoice) {
            $amount = $amount * 1.16;
        }

        // Retención ISR 1.25% (RESICO) — solo si la bandera aplica_retencion está activa
        // (protege el historial financiero de cotizaciones anteriores al cambio fiscal)
        $isrRetention = (float) ($this->isr_retention ?? 0);
        if ($this->aplica_retencion && $isrRetention > 0) {
            $amount = $amount - $isrRetention;
        }

        return round($amount, 2);
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
    
    public function getBalanceAttribute(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        return (float) $this->getFinalAmountAttribute() - (float) $totalPaid;
    }
}