<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'name',
        'slug',
        'school_code',
        'location',
        'contact_person',
        'contact_phone',
        'contact_email',
        'distance_from_warehouse_km',
        'delivery_fee',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'distance_from_warehouse_km' => 'decimal:2',
            'delivery_fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->name.' - '.($this->district?->name ?? '')));
    }
}
