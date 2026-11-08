<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'category', 'description', 'price', 'image_path', 'is_available', 'stock_quantity', 'stock_enabled'])]
class MenuProduct extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'stock_enabled' => 'boolean',
            'stock_quantity' => 'integer',
        ];
    }
}
