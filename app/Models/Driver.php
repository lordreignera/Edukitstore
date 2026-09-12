<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'district',
        'vehicle_type',
        'vehicle_registration',
        'payment_phone',
        'is_approved',
        'is_available',
        'approved_at',
        'approved_by',
        'user_id',
        'notes',
        'verification_document_path',
        'verification_document_name',
        'source',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_available' => 'boolean',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedShoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class, 'assigned_driver_id');
    }
}
