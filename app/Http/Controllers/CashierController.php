<?php
namespace App\Http\Controllers;

use App\Models\Order;
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
        $orders = Order::query()
            ->latest()
            ->limit(100)
            ->get();

        return view('cashier.dashboard', [
            'orders' => $orders,
            'orderStatuses' => self::ORDER_STATUSES,
        ]);
    }
}
