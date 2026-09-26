<?php

namespace App\Services;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;

/** Historique public des modifications importantes (profils candidats, programmes, élus). */
class ChangeLogger
{
    public static function diff(Model $model, array $fields, ?string $note = null): void
    {
        foreach ($fields as $field) {
            if (! $model->wasChanged($field)) {
                continue;
            }
            ChangeLog::create([
                'user_id' => auth()->id(),
                'subject_type' => $model->getMorphClass(),
                'subject_id' => $model->getKey(),
                'field' => $field,
                'old_value' => self::str($model->getOriginal($field)),
                'new_value' => self::str($model->getAttribute($field)),
                'note' => $note,
            ]);
        }
    }

    public static function record(Model $model, string $field, $old, $new, ?string $note = null): void
    {
        ChangeLog::create([
            'user_id' => auth()->id(),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'field' => $field,
            'old_value' => self::str($old),
            'new_value' => self::str($new),
            'note' => $note,
        ]);
    }

    private static function str($v): ?string
    {
        if ($v === null) {
            return null;
        }

        return is_scalar($v) ? mb_substr((string) $v, 0, 5000) : json_encode($v);
    }
}
