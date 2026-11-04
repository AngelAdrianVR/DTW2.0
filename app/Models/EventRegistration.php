<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Registro de un participante a un evento.
 *
 * Guarda un snapshot de la pregunta variable del evento (`custom_field_label`)
 * para que el histórico no cambie si después se edita la etiqueta.
 */
class EventRegistration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'business_name',
        'phone',
        'custom_field_label',
        'custom_answer',
        'locale',
        'ip_address',
        'user_agent',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function winner(): HasOne
    {
        return $this->hasOne(EventWinner::class);
    }

    /**
     * Teléfono listo para wa.me (sólo dígitos).
     */
    public function getWhatsappAttribute(): string
    {
        return preg_replace('/\D+/', '', (string) $this->phone) ?? '';
    }
}
