<?php

namespace App\Http\Controllers;

use App\Models\MenuProduct;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:120'],
            'student_id' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', 'in:cash,gcash'],
            'fulfillment_type' => ['required', 'in:pickup,dine_in'],
            'preferred_time' => ['nullable', 'string', 'max:40'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $quantities = [];
        foreach ($validated['items'] as $item) {
            $id = (int) $item['id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $item['quantity'];
            if ($quantities[$id] > 50) {
                throw ValidationException::withMessages([
                    'items' => 'You can order up to 50 of each item.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($validated, $quantities): Order {
            $products = MenuProduct::query()
                ->whereIn('id', array_keys($quantities))
                ->where('is_available', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($quantities)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more items are unavailable. Refresh the menu and try again.',
                ]);
            }

            $items = [];
            $totalCents = 0;
            foreach ($quantities as $id => $quantity) {
                $product = $products->get($id);
                if ($product->stock_enabled && $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "{$product->name} does not have enough stock available.",
                    ]);
                }

                if ($product->stock_enabled) {
                    $updated = MenuProduct::query()
                        ->whereKey($product->id)
                        ->where('stock_quantity', '>=', $quantity)
                        ->decrement('stock_quantity', $quantity);

                    if ($updated !== 1) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} became unavailable. Refresh the menu and try again.",
                        ]);
                    }
                }

                $priceCents = (int) round((float) $product->price * 100);
                $items[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $priceCents / 100,
                    'quantity' => $quantity,
                ];
                $totalCents += $priceCents * $quantity;
            }

            if ($totalCents <= 0) {
                throw ValidationException::withMessages(['items' => 'Your cart is empty.']);
            }

            return Order::query()->create([
                'customer_id' => Auth::id(),
                'order_number' => 'AW-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                'student_name' => $validated['student_name'],
                'student_id' => $validated['student_id'] ?? '',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'cash' ? 'pay_on_pickup' : 'pending',
                'fulfillment_type' => $validated['fulfillment_type'],
                'preferred_time' => $validated['preferred_time'] ?? '',
                'special_instructions' => $validated['special_instructions'] ?? '',
                'order_status' => 'pending',
                'total_amount' => $totalCents / 100,
                'items_json' => $items,
            ]);
        });

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'total' => number_format((float) $order->total_amount, 2),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'created_at' => $order->created_at->format('F j, Y g:i A'),
        ]);
    }

    public function index(): View
    {
        return view('orders.index', [
            'orders' => Order::query()
                ->where('customer_id', Auth::id())
                ->latest()
                ->paginate(10),
        ]);
    }
}
