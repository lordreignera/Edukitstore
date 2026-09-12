<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShoppingList extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_CART = 'cart';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'parent_name',
        'phone',
        'email',
        'school_name',
        'learner_name',
        'class_level',
        'delivery_location',
        'delivery_preference',
        'notes',
        'source',
        'reference',
        'file_path',
        'original_filename',
        'cart_items',
        'items_subtotal',
        'delivery_fee',
        'assigned_driver_id',
        'status',
        'estimated_total',
        'payment_status',
        'payment_provider',
        'payment_reference',
        'paid_at',
        'delivery_confirmed_at',
        'delivery_confirmed_by',
        'delivery_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'cart_items' => 'array',
            'items_subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'estimated_total' => 'integer',
            'paid_at' => 'datetime',
            'delivery_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function deliveryConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_confirmed_by');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWING => 'Reviewing',
            self::STATUS_QUOTED => 'Quoted',
            self::STATUS_FULFILLED => 'Fulfilled',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ShoppingList $shoppingList): void {
            if (! $shoppingList->reference) {
                do {
                    $reference = 'EDK-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
                } while (self::where('reference', $reference)->exists());

                $shoppingList->reference = $reference;
            }
        });
    }
}
