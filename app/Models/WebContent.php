<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WebContent extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'subtitle',
        'link_url',
        'link_text',
        'is_published',
        'publish_date',
        'end_date',
        'sort_order',
        'spanish_title',
        'english_title',
        'spanish_content',
        'english_content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'publish_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Appends the URLs the front-end needs: la portada del proyecto y la
     * imagen principal (portada si existe, si no la primera de la galería).
     *
     * @var array
     */
    protected $appends = ['image_url', 'cover_url'];

    /**
     * Define the media collections.
     * A collection is created for each type of content, más `cover`: la
     * portada opcional que el back-office usa como miniatura del proyecto.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('portfolio');

        $this->addMediaCollection('own_projects');

        $this->addMediaCollection('client_logos');

        $this->addMediaCollection('advertising');

        // Portada: una sola imagen por contenido (subir otra reemplaza la anterior).
        $this->addMediaCollection('cover')->singleFile();
    }

    /**
     * Accessor for the optional cover image chosen by the back-office.
     *
     * @return string|null
     */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover') ?: null;
    }

    /**
     * Accessor to get the primary image URL: la portada manda y, si no hay,
     * se usa la primera imagen de la galería del contenido.
     *
     * Ojo: `getFirstMediaUrl()` sin argumentos busca la colección `default`,
     * pero el back-office guarda la galería en la colección del propio tipo
     * (`portfolio`, `own_projects`, `client_logos`, `advertising`).
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($coverUrl = $this->getFirstMediaUrl('cover')) {
            return $coverUrl;
        }

        // Galería del tipo de contenido; si no hay, cualquier otra colección
        // que no sea la portada (contenidos antiguos guardados en `default`).
        $gallery = $this->getMedia($this->type);

        if ($gallery->isEmpty()) {
            $gallery = $this->getMedia()
                ->reject(fn ($media) => $media->collection_name === 'cover')
                ->values();
        }

        return $gallery->first()?->getUrl() ?: null;
    }

    /**
     * Register media conversions.
     *
     * @param Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(368)
              ->height(232)
              ->sharpen(10);
    }
}
