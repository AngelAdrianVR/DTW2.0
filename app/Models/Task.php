<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'estimated_hours',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }
}
