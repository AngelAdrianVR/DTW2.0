<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_id',
        'address',
        'status',
        'source',
        'assigned_to',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
