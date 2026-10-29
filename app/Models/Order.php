<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'order_number',
    'student_name',
    'student_id',
    'payment_method',
    'payment_status',
    'fulfillment_type',
    'preferred_time',
    'special_instructions',
    'order_status',
    'total_amount',
    'items_json',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'items_json' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
