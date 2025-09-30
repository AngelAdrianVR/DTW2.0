<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebContent extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'web_content';

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'content',
        'image_url',
        'link_url',
        'link_text',
        'is_published',
        'publish_date',
        'end_date',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'publish_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
