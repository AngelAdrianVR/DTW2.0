<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'estimated_hours',
        'status',
        'priority',
        'due_date',
        'total_invested_minutes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     * Esto hace que 'total_hours_invested' esté disponible automáticamente en el frontend.
     *
     * @var array
     */
    protected $appends = ['total_hours_invested'];

    /**
     * Get the project that the task belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all of the time logs for the task.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }



    /**
     * Accessor para obtener el total de horas invertidas en la tarea en formato legible.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function totalHoursInvested(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->total_invested_minutes || $this->total_invested_minutes <= 0) {
                    return '00:00';
                }
                $hours = floor($this->total_invested_minutes / 60);
                $minutes = $this->total_invested_minutes % 60;
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        );
    }
}
