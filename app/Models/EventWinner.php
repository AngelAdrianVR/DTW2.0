<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ganador de un evento.
 *
 * Puede estar vinculado a un registro del formulario o capturarse a mano
 * (por ejemplo, cuando el ganador llegó por redes sociales). El nombre y el
 * negocio se guardan como snapshot para que el historial no cambie.
 */
class EventWinner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'event_registration_id',
        'name',
        'business_name',
        'spanish_prize',
        'english_prize',
        'spanish_note',
        'english_note',
        'awarded_at',
        'is_published',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'awarded_at' => 'date',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
