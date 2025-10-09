<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'quote_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget',
        'total_invested_minutes', // Campo añadido para la nueva funcionalidad
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote from which the project was created.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * The members that belong to the project.
     */
    public function members(): BelongsToMany
    {
        // Se define la relación a través de la tabla 'project_members'
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }

    /**
     * Get all of the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Accessor para obtener el total de horas invertidas en un formato legible.
     * Esto convierte los minutos almacenados en la BD a un formato "HH:MM".
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function totalHoursInvested(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->total_invested_minutes <= 0) {
                    return '00:00';
                }
                $hours = floor($this->total_invested_minutes / 60);
                $minutes = $this->total_invested_minutes % 60;
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        );
    }
}
