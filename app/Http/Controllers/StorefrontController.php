<?php

namespace App\Http\Controllers;

use App\Models\CanteenProfile;
use App\Models\MenuProduct;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        return view('storefront.index', [
            'products' => MenuProduct::query()
                ->where('is_available', true)
                ->orderByRaw("CASE category WHEN 'mains' THEN 0 WHEN 'breakfast' THEN 1 WHEN 'snacks' THEN 2 WHEN 'drinks' THEN 3 ELSE 4 END")
                ->orderBy('name')
                ->get(),
            'categories' => ProductCategory::query()->orderBy('name')->pluck('name'),
            'profile' => CanteenProfile::query()->find(1),
        ]);
    }
}
