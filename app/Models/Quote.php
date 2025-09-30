<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'quote_code',
        'title',
        'description',
        'amount',
        'status',
        'origin',
        'valid_until',
    ];

    protected $casts = [
        'valid_until' => 'date',
    ];

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
}
