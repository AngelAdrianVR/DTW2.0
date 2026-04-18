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
        'budgeted_hours',
        'needs_invoice',
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
        'needs_invoice' => 'boolean',
        'amount' => 'float',
        'amount_usd' => 'float',
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

    protected $appends = ['final_amount'];

    public function getFinalAmountAttribute(): float
    {
        // Se previene error fatal en PHP si los datos llegan a estar null
        $amount = (float) ($this->amount ?? 0);
        $discount = (float) ($this->percentage_discount ?? 0);

        if ($discount > 0) {
            $discountAmount = $amount * ($discount / 100);
            return (float) ($amount - $discountAmount);
        }

        // <-- LÓGICA DE IVA INTERNO: Multiplica por 1.16 si requiere factura
        if ($this->needs_invoice) {
            $amount = $amount * 1.16;
        }
        return $amount;
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