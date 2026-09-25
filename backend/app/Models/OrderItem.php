<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_name',
        'category',
        'unit_price',
        'quantity',
        'is_final_sale',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'is_final_sale' => 'boolean',
            'refunded_at' => 'datetime',
        ];
    }

    public function lineTotal(): float
    {
        return round((float) $this->unit_price * $this->quantity, 2);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
