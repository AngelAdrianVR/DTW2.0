<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_client_id',
        'amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function hostingClient(): BelongsTo
    {
        return $this->belongsTo(HostingClient::class);
    }
}
