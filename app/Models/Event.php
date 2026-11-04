<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Edición de un evento de DTW (reto + premios + registro de participantes).
 *
 * El estado no se guarda en base de datos: se calcula a partir de
 * `is_published` y de la ventana de registro, para que el panel y la landing
 * nunca queden desincronizados.
 */
class Event extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /** Sólo existe en el back-office. */
    public const STATUS_DRAFT = 'draft';

    /** Publicado, pero el registro todavía no abre. */
    public const STATUS_UPCOMING = 'upcoming';

    /** Registro abierto ahora mismo. */
    public const STATUS_OPEN = 'open';

    /** Registro cerrado, pendiente de dar a conocer ganadores. */
    public const STATUS_CLOSED = 'closed';

    /** Registro cerrado y ganadores publicados. */
    public const STATUS_FINISHED = 'finished';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'spanish_title',
        'english_title',
        'spanish_description',
        'english_description',
        'spanish_challenge',
        'english_challenge',
        'spanish_prizes_summary',
        'english_prizes_summary',
        'event_date',
        'registration_starts_at',
        'registration_ends_at',
        'custom_field_label_es',
        'custom_field_label_en',
        'custom_field_placeholder_es',
        'custom_field_placeholder_en',
        'custom_field_required',
        'is_published',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'date',
        'registration_starts_at' => 'datetime',
        'registration_ends_at' => 'datetime',
        'custom_field_required' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Atributos calculados que viajan al frontend.
     *
     * @var array<int, string>
     */
    protected $appends = ['image_url', 'status', 'can_register', 'has_published_winners'];

    /* ---------------------------------------------------------------------
       Relaciones
       --------------------------------------------------------------------- */

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(EventWinner::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Ganadores visibles en la landing (el panel puede tenerlos cargados pero
     * ocultos hasta el anuncio oficial).
     */
    public function publishedWinners(): HasMany
    {
        return $this->winners()->where('is_published', true);
    }

    /* ---------------------------------------------------------------------
       Scopes
       --------------------------------------------------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Orden del historial: primero las ediciones más recientes.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('event_date')->orderByDesc('id');
    }

    /* ---------------------------------------------------------------------
       Medios
       --------------------------------------------------------------------- */

    public function registerMediaCollections(): void
    {
        // Una sola portada por evento: al subir otra se reemplaza.
        $this->addMediaCollection('events')->singleFile();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('events') ?: null;
    }

    /* ---------------------------------------------------------------------
       Atributos calculados
       --------------------------------------------------------------------- */

    public function getStatusAttribute(): string
    {
        if (! $this->is_published) {
            return self::STATUS_DRAFT;
        }

        $now = Carbon::now();

        if ($this->registration_starts_at && $now->lessThan($this->registration_starts_at)) {
            return self::STATUS_UPCOMING;
        }

        if ($this->registration_ends_at && $now->greaterThan($this->registration_ends_at)) {
            return $this->hasPublishedWinners() ? self::STATUS_FINISHED : self::STATUS_CLOSED;
        }

        return self::STATUS_OPEN;
    }

    public function getCanRegisterAttribute(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function getHasPublishedWinnersAttribute(): bool
    {
        return $this->hasPublishedWinners();
    }

    /**
     * ¿Ya hay ganadores publicados? Usa el contador cuando el controlador lo
     * precargó con `withCount(['publishedWinners as winners_count'])` para no
     * disparar una consulta por evento.
     */
    public function hasPublishedWinners(): bool
    {
        if (array_key_exists('winners_count', $this->attributes)) {
            return (int) $this->attributes['winners_count'] > 0;
        }

        return $this->publishedWinners()->exists();
    }

    /* ---------------------------------------------------------------------
       Utilidades bilingües
       --------------------------------------------------------------------- */

    public function titleFor(string $locale = 'es'): string
    {
        return $locale === 'en'
            ? (string) ($this->english_title ?: $this->spanish_title)
            : (string) ($this->spanish_title ?: $this->english_title);
    }

    /**
     * Etiqueta de la pregunta variable en el idioma pedido.
     */
    public function customFieldLabelFor(string $locale = 'es'): ?string
    {
        return $locale === 'en'
            ? ($this->custom_field_label_en ?: $this->custom_field_label_es)
            : ($this->custom_field_label_es ?: $this->custom_field_label_en);
    }

    public function customFieldPlaceholderFor(string $locale = 'es'): ?string
    {
        return $locale === 'en'
            ? ($this->custom_field_placeholder_en ?: $this->custom_field_placeholder_es)
            : ($this->custom_field_placeholder_es ?: $this->custom_field_placeholder_en);
    }

    /**
     * Slug único a partir del título (o del que escribió el admin).
     */
    public static function makeSlug(?string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $title) ?: 'evento';
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}


