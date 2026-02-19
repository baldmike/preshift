<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'category_id',
        'name',
        'description',
        'price',
        'type',
        'is_new',
        'is_active',
        'allergens',
    ];

    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_active' => 'boolean',
            'allergens' => 'array',
            'price' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function eightySixed(): HasMany
    {
        return $this->hasMany(EightySixed::class);
    }

    public function specials(): HasMany
    {
        return $this->hasMany(Special::class);
    }

    public function pushItems(): HasMany
    {
        return $this->hasMany(PushItem::class);
    }
}
