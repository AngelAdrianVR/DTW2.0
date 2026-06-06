<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
// Asumo que tus modelos usan estas clases
use App\Models\User;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ClientPayment;

class Client extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name', // Nombre de la empresa o cliente
        'tax_id', // RFC o identificación fiscal
        'address',
        'status', // Estatus (Prospecto, Cliente)
        'source', // fuente del cliente
        'regimen_fiscal', // Régimen fiscal: persona_fisica | persona_moral
    ];

    /**
     * Define las colecciones de medios para el cliente.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

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

    /**
     * Obtiene todo el historial de pagos para el cliente.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }
}
