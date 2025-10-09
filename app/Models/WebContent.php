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
     * Appends a custom attribute for the primary image URL.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Define the media collections.
     * A collection is created for each type of content.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('portfolio');

        $this->addMediaCollection('own_projects');

        $this->addMediaCollection('client_logos');

        $this->addMediaCollection('advertising');
    }
    
    /**
     * Accessor to get the URL of the first image in any collection.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl() ?: null;
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
