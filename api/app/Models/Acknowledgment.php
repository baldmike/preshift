<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Acknowledgment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'acknowledgable_type',
        'acknowledgable_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgable(): MorphTo
    {
        return $this->morphTo();
    }
}
