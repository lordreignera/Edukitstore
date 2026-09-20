<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupplierOffer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'supplier_id', 'product_id', 'product_category_id', 'submitted_name',
        'submitted_description', 'submitted_brand', 'submitted_unit', 'submitted_image_path',
        'supplier_price', 'customer_price', 'quantity_submitted', 'pending_quantity', 'quantity_available',
        'quantity_sold', 'reorder_level', 'direct_fulfilment', 'status', 'review_notes',
        'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'supplier_price' => 'decimal:2',
            'customer_price' => 'decimal:2',
            'quantity_submitted' => 'integer',
            'pending_quantity' => 'integer',
            'quantity_available' => 'integer',
            'quantity_sold' => 'integer',
            'reorder_level' => 'integer',
            'direct_fulfilment' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->submitted_image_path
            ? Storage::disk(Product::imageDisk())->url($this->submitted_image_path)
            : $this->product?->image_url);
    }

    public function isApprovedAndAvailable(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->direct_fulfilment
            && $this->quantity_available > 0
            && $this->supplier?->is_active;
    }
}
