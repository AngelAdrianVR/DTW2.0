<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PomodoroSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'work_minutes',
        'break_minutes',
        'long_break_minutes',
        'sessions_before_long_break',
    ];

    /**
     * Get the user that owns the pomodoro settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
