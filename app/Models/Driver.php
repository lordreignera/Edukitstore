<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_available' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }
}
