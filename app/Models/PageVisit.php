<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $fillable = [
        'path',
        'route_name',
        'referrer',
        'referrer_host',
        'user_agent',
        'visitor_id',
        'session_id',
        'ip_hash',
        'device',
        'browser',
        'platform',
        'is_bot',
        'visited_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'visited_at' => 'datetime',
    ];

    /**
     * Solo visitas humanas (excluye bots y crawlers).
     */
    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function scopeBetweenDates(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('visited_at', [$start, $end]);
    }
}
