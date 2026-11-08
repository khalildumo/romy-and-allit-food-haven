@extends('admin.layout')
@section('title', 'Manage products | Romy and Allit Food Haven')
@section('admin-content')
<main class="product-manager">
    <a class="back-button" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
    <p class="eyebrow"><span></span> Menu catalog</p>
    <h1>Manage products</h1>
    <form class="product-form" method="post" action="{{ route('admin.products.save') }}" enctype="multipart/form-data">
        @csrf
        <h2>{{ $editProduct ? 'Edit product' : 'Add a product' }}</h2>
        @if ($editProduct)<input type="hidden" name="id" value="{{ $editProduct->id }}">@endif
        <label>Product name<input name="name" required maxlength="120" value="{{ old('name', $editProduct?->name) }}"></label>
        <label>Category<select name="category" required>
            @foreach ($categories as $category)<option value="{{ $category }}" @selected(old('category', $editProduct?->category) === $category)>{{ ucfirst($category) }}</option>@endforeach
        </select></label>
        <label>Description<textarea name="description" maxlength="255">{{ old('description', $editProduct?->description) }}</textarea></label>
        <label>Price (₱)<input name="price" type="number" min="0" step="0.01" required value="{{ old('price', $editProduct?->price ?? '40.00') }}"></label>
        <label>Available servings<input name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $editProduct?->stock_quantity ?? 0) }}"></label>
        <label class="checkbox-label"><input type="checkbox" name="stock_enabled" value="1" @checked(old('stock_enabled', $editProduct?->stock_enabled ?? false))> Enforce stock limit</label>
        <label class="wide">Product image (JPG, PNG, or WebP, up to 5 MB)<input name="product_image" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <label class="checkbox-label wide"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $editProduct?->is_available ?? true))> Available on menu</label>
        <div class="wide">
            <button class="button dark" type="submit">{{ $editProduct ? 'Save changes' : 'Add product' }}</button>
            @if ($editProduct)<a class="text-link" href="{{ route('admin.products') }}">Cancel edit</a>@endif
        </div>
    </form>
    <section class="product-list">
        <h2>Products ({{ $products->count() }})</h2>
        <div class="orders-table-wrap"><table class="product-table">
            <thead><tr><th>Image</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Availability</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                @php($imageUrl = $product->image_path === '' ? '' : (str_starts_with($product->image_path, 'products/') ? asset('storage/'.$product->image_path) : asset(str_starts_with($product->image_path, 'assets/') ? $product->image_path : 'assets/'.$product->image_path)))
                <tr>
                    <td>@if ($imageUrl)<img src="{{ $imageUrl }}" alt="" width="56" height="56" style="object-fit:cover;border-radius:5px">@else — @endif</td>
                    <td>{{ $product->name }}</td><td>{{ ucfirst($product->category) }}</td><td>₱{{ number_format((float) $product->price, 2) }}</td>
                    <td>{{ $product->stock_enabled ? $product->stock_quantity.' left' : 'Unlimited' }}</td><td>{{ $product->is_available ? 'Available' : 'Hidden' }}</td>
                    <td class="actions">
                        <a class="action-button edit-button" href="{{ route('admin.products', ['edit' => $product->id]) }}">Edit</a>
                        <form method="post" action="{{ route('admin.products.delete', $product) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('delete')<button class="action-button delete-button" type="submit">Delete</button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="7">No products added yet.</td></tr>@endforelse
            </tbody>
        </table></div>
    </section>
</main>
@endsection
