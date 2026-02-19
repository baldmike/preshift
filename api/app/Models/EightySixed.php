<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EightySixed extends Model
{
    use HasFactory;

    protected $table = 'eighty_sixed';

    protected $fillable = [
        'location_id',
        'menu_item_id',
        'item_name',
        'reason',
        'eighty_sixed_by',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'restored_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eighty_sixed_by');
    }

    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgment::class, 'acknowledgable');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->whereNull('restored_at');
    }
}
