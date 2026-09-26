<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    protected $guarded = ['id'];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
