<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'shopping_list_id', 'tx_ref', 'provider_transaction_id', 'amount', 'currency',
        'status', 'checkout_url', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'verified_at' => 'datetime'];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }
}
