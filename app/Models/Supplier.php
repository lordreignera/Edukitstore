<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'contact_person',
        'phone',
        'email',
        'district',
        'address',
        'product_categories',
        'supply_capacity',
        'notes',
        'verification_document_path',
        'verification_document_name',
        'source',
        'submitted_at',
        'is_approved',
        'is_active',
        'approved_at',
        'approved_by',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
