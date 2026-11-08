<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'category', 'description', 'price', 'image_path', 'is_available', 'stock_quantity', 'stock_enabled'])]
class MenuProduct extends Model
{
    public function imageUrl(): ?string
    {
        $imagePath = $this->image_path;

        if (! is_string($imagePath) || $imagePath === '') {
            return null;
        }

        if (str_starts_with($imagePath, 'products/')) {
            if (Storage::disk('public')->exists($imagePath)) {
                return Storage::disk('public')->url($imagePath);
            }

            $legacyImagePath = 'assets/uploads/'.$imagePath;

            return is_file(public_path($legacyImagePath)) ? asset($legacyImagePath) : null;
        }

        $assetPath = str_starts_with($imagePath, 'assets/') ? $imagePath : 'assets/'.$imagePath;

        return is_file(public_path($assetPath)) ? asset($assetPath) : null;
    }

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
