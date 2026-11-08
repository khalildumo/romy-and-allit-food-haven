<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashierController extends Controller
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

        return view('cashier.dashboard', [
            'orders' => $orders->take(100),
            'totalOrders' => $orders->count(),
            'openOrders' => $orders->whereIn('order_status', ['pending', 'preparing', 'ready'])->count(),
            'paidCount' => $orders->where('payment_status', 'paid')->count(),
            'dailyRevenue' => $orders->filter(fn (Order $order): bool => $order->created_at->isToday())->sum(fn (Order $order): float => (float) $order->total_amount),
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
}
