<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Model
{
    protected $guarded = ['id'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class, 'verification_request_id');
    }
}
