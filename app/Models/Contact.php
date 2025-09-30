<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contact extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'position',
    ];

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
