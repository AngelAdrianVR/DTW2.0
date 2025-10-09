<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// Asumo que tus modelos usan estas clases
use App\Models\User;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ClientPayment;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // Nombre de la empresa o cliente
        'tax_id', // RFC o identificación fiscal
        'address',
        'status', // Estatus (Prospecto, Cliente)
        'source', // fuente del cliente
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

    /**
     * Obtiene todo el historial de pagos para el cliente.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }
}
