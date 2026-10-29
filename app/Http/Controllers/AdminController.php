<?php

namespace App\Http\Controllers;

use App\Models\CanteenProfile;
use App\Models\MenuProduct;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const ORDER_STATUSES = [
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'ready' => 'Ready for Pick-up',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function dashboard(): View
    {
        $orders = Order::query()->latest()->get();
        $totalOrders = $orders->count();
        $topItems = [];
        foreach ($orders as $order) {
            foreach ($order->items_json ?? [] as $item) {
                $name = $item['name'] ?? 'Item';
                $topItems[$name] = ($topItems[$name] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
        }
        arsort($topItems);

        return view('admin.dashboard', [
            'orders' => $orders->take(100),
            'totalOrders' => $totalOrders,
            'products' => MenuProduct::query()->orderBy('category')->orderBy('name')->get(),
            'accounts' => User::query()->orderBy('role')->orderBy('full_name')->get(),
            'customerCount' => User::query()->where('role', 'customer')->count(),
            'paidCount' => $orders->where('payment_status', 'paid')->count(),
            'revenue' => $orders->sum(fn (Order $order): float => (float) $order->total_amount),
            'dailyRevenue' => $orders->filter(fn (Order $order): bool => $order->created_at->isToday())->sum(fn (Order $order): float => (float) $order->total_amount),
            'needsAttention' => $orders->whereIn('order_status', ['pending', 'preparing'])->count(),
            'topItems' => array_slice($topItems, 0, 5, true),
            'orderStatuses' => self::ORDER_STATUSES,
        ]);
    }

    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::ORDER_STATUSES))],
        ]);

        $transitions = [
            'pending' => ['pending', 'preparing', 'cancelled'],
            'preparing' => ['preparing', 'ready', 'cancelled'],
            'ready' => ['ready', 'completed'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
        $updatedStatus = DB::transaction(function () use ($order, $validated, $transitions): string {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($validated['status'], $transitions[$lockedOrder->order_status] ?? [], true), 422, 'Orders must follow the required status sequence.');
            $lockedOrder->update(['order_status' => $validated['status']]);

            return $lockedOrder->order_status;
        });

        return response()->json(['success' => true, 'order_status' => $updatedStatus]);
    }

    public function verifyPayment(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless(
                $lockedOrder->payment_method === 'gcash' && $lockedOrder->payment_status === 'pending',
                422,
                'Only pending GCash payments can be verified.',
            );
            $lockedOrder->update(['payment_status' => 'paid']);
        });

        return response()->json(['success' => true, 'payment_status' => 'paid']);
    }

    public function products(Request $request): View
    {
        return view('admin.products', [
            'products' => MenuProduct::query()->orderBy('category')->orderBy('name')->get(),
            'categories' => ProductCategory::query()->orderBy('name')->pluck('name'),
            'editProduct' => $request->filled('edit') ? MenuProduct::query()->findOrFail($request->integer('edit')) : null,
        ]);
    }

    public function saveProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:menu_products,id'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'exists:product_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'stock_enabled' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $product = isset($validated['id'])
            ? MenuProduct::query()->findOrFail($validated['id'])
            : new MenuProduct;
        $product->fill([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'] ?? 0,
            'stock_enabled' => $request->boolean('stock_enabled'),
            'is_available' => $request->boolean('is_available'),
        ]);

        if ($request->hasFile('product_image')) {
            $product->image_path = $request->file('product_image')->store('products', 'public');
        }

        $product->save();

        return redirect()->route('admin.products')->with('status', 'Product saved.');
    }

    public function deleteProduct(MenuProduct $product): RedirectResponse
    {
        if (str_starts_with($product->image_path, 'products/')) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return back()->with('status', 'Product deleted.');
    }

    public function customers(): View
    {
        return view('admin.customers', [
            'customers' => User::query()->where('role', 'customer')->latest()->paginate(25),
        ]);
    }

    public function updateCustomer(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'institution_id' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $user->update($validated);

        return back()->with('status', 'Customer details updated.');
    }

    public function deleteCustomer(User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);
        $user->delete();

        return back()->with('status', 'Customer account deleted.');
    }

    public function staff(): View
    {
        return view('admin.staff', [
            'staff' => User::query()->whereIn('role', ['admin', 'customer'])->orderBy('role')->orderBy('full_name')->get(),
        ]);
    }

    public function createStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,username'],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'password' => ['required', Password::min(12)],
        ]);
        User::query()->create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'password_hash' => $validated['password'],
            'email_verified' => true,
        ]);

        return back()->with('status', ucfirst($validated['role']).' account created.');
    }

    public function resetStaffPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['admin', 'customer'], true), 404);
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        $user->update(['password_hash' => $validated['password']]);

        return back()->with('status', 'Password reset successfully.');
    }

    public function settings(): View
    {
        return view('admin.settings', [
            'profile' => CanteenProfile::query()->firstOrCreate(['id' => 1]),
            'categories' => ProductCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'canteen_name' => ['required', 'string', 'max:160'],
            'operating_hours' => ['required', 'string', 'max:160'],
            'contact_info' => ['nullable', 'string', 'max:255'],
            'pickup_location' => ['required', 'string', 'max:255'],
        ]);
        CanteenProfile::query()->updateOrCreate(['id' => 1], $validated);

        return back()->with('status', 'Canteen profile updated.');
    }

    public function createCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:product_categories,name']]);
        ProductCategory::query()->create($validated);

        return back()->with('status', 'Category added.');
    }

    public function deleteCategory(string $category): RedirectResponse
    {
        $categoryRow = ProductCategory::query()->where('name', $category)->firstOrFail();
        if (MenuProduct::query()->where('category', $categoryRow->name)->exists()) {
            return back()->withErrors(['category' => 'Move products to another category before deleting this category.']);
        }
        $categoryRow->delete();

        return back()->with('status', 'Category deleted.');
    }
}
