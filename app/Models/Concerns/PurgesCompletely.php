<?php

namespace App\Models\Concerns;

use App\Services\ContentPurger;

/** Suppression définitive : le contenu et tout ce qui s'y rattache disparaissent (pas de corbeille). */
trait PurgesCompletely
{
    public static function bootPurgesCompletely(): void
    {
        static::deleting(fn ($model) => app(ContentPurger::class)->purge($model));
    }
}
