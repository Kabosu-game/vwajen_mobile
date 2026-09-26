<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use Interactable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function url(): string
    {
        return route('questions.show', $this->question_id).'#answer-'.$this->id;
    }
}
