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
        // NUEVOS CAMPOS:
        'sent_at',
        'accepted_at',
        'rejected_at',
        'paid_at',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'show_process' => 'boolean',
        'show_benefits' => 'boolean',
        'show_bank_info' => 'boolean',
        'amount' => 'float',
        'amount_usd' => 'float',
        // NUEVOS CASTS DE FECHAS:
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
    
    public function getBalanceAttribute(): float
    {
        $totalPaid = $this->payments()->sum('amount');
        return (float) $this->getFinalAmountAttribute() - $totalPaid;
    }
}