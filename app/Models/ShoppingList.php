<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingList extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_CART = 'cart';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'parent_name',
        'phone',
        'email',
        'district_id',
        'school_id',
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
            'district_id' => 'integer',
            'school_id' => 'integer',
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
            self::STATUS_QUOTED => 'Ready for Payment',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_FULFILLED => 'Delivered / Complete',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PENDING => 'Payment Pending',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded',
        ];
    }

    public static function deliveryStatuses(): array
    {
        return [
            'unassigned' => 'Unassigned',
            'awaiting_payment' => 'Awaiting Payment',
            'ready_for_delivery' => 'Ready for Delivery',
            'delivered' => 'Delivered',
        ];
    }

    public function deliveryStatus(): string
    {
        if ($this->delivery_confirmed_at) {
            return 'delivered';
        }

        if (! $this->assigned_driver_id) {
            return 'unassigned';
        }

        if ($this->payment_status !== self::PAYMENT_PAID) {
            return 'awaiting_payment';
        }

        return 'ready_for_delivery';
    }

    public static function nextReference(): string
    {
        $prefix = 'EDK-'.now()->format('ymd');
        $latestSequence = self::where('reference', 'like', "{$prefix}-%")
            ->pluck('reference')
            ->map(function (?string $reference) use ($prefix): ?int {
                if (! $reference || ! preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $reference, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->max();

        return $prefix.'-'.($latestSequence ? $latestSequence + 1 : 1000);
    }

    protected static function booted(): void
    {
        static::creating(function (ShoppingList $shoppingList): void {
            if (! $shoppingList->reference) {
                $shoppingList->reference = self::nextReference();
            }
        });
    }
}
