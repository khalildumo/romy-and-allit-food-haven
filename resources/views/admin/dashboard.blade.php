@extends('admin.layout')
@section('title', 'Admin dashboard | Romy and Allit Food Haven')
@section('admin-content')
<main class="dashboard-content">
    <div class="dashboard-title">
        <div><p class="eyebrow"><span></span> Admin dashboard</p><h1>Order overview.</h1><p class="dashboard-subtitle">A quick look at today's sales, orders, and canteen activity.</p></div>
        <div class="dashboard-actions"><a class="button dark" href="{{ route('home') }}">View customer site</a></div>
    </div>
    <nav class="dashboard-tools" aria-label="Dashboard tools">
        <a href="{{ route('admin.products') }}"><strong>Menu products</strong><small>{{ $products->count() }} products · Manage menu</small><b>→</b></a>
        <a href="{{ route('admin.customers') }}"><strong>Customers</strong><small>{{ $customerCount }} registered accounts · View customers</small><b>→</b></a>
        <a href="{{ route('admin.settings') }}"><strong>Canteen settings</strong><small>Hours, categories and details</small><b>→</b></a>
    </nav>
    <section class="stats-grid" aria-label="Business summary">
        <div><small>Total orders</small><strong>{{ $totalOrders }}</strong></div>
        <div><small>Paid orders</small><strong>{{ $paidCount }}</strong></div>
        <div><small>Total order value</small><strong>₱{{ number_format($revenue, 2) }}</strong></div>
        <div><small>Today's sales</small><strong>₱{{ number_format($dailyRevenue, 2) }}</strong></div>
        <div><small>Needs attention</small><strong>{{ $needsAttention }}</strong></div>
    </section>
    <section class="orders-panel dashboard-section-spaced">
        <div class="panel-heading"><h2>Top-selling items</h2><span>All time</span></div>
        @forelse ($topItems as $name => $quantity)
            <p>{{ $name }} — {{ $quantity }} sold</p>
        @empty
            <p>No sales data yet.</p>
        @endforelse
    </section>
    <section class="orders-panel dashboard-section-spaced">
        <div class="panel-heading"><h2>Recent orders</h2><span>{{ now()->format('F j, Y') }}</span></div>
        <div class="orders-table-wrap"><table>
            <thead><tr><th>Order</th><th>Student</th><th>Payment</th><th>Status</th><th>Total</th><th>Placed</th><th>Update status</th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>{{ $order->student_name }}<small>{{ $order->student_id }}</small></td>
                    <td data-payment-for="{{ $order->id }}">
                        {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                        <small class="payment-status">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</small>
                        @if ($order->payment_method === 'gcash' && $order->payment_status === 'pending')
                            <button class="verify-payment" type="button" data-url="{{ route('admin.orders.payment', $order) }}">Verify GCash payment</button>
                        @endif
                    </td>
                    <td><span class="status {{ $order->order_status }}" data-status-for="{{ $order->id }}">{{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</span></td>
                    <td>₱{{ number_format((float) $order->total_amount, 2) }}</td>
                    <td>{{ $order->created_at->format('M j, g:i A') }}</td>
                    <td><select class="order-status-select" data-order-id="{{ $order->id }}" data-url="{{ route('admin.orders.status', $order) }}">
                        @foreach ($orderStatuses as $value => $label)<option value="{{ $value }}" @selected($order->order_status === $value)>{{ $label }}</option>@endforeach
                    </select></td>
                </tr>
            @empty
                <tr><td colspan="7" class="no-orders">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</main>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.order-status-select').forEach((select) => {
    select.addEventListener('change', async () => {
        const previous = select.dataset.currentStatus;
        select.disabled = true;
        try {
            const response = await fetch(select.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: select.value })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Status update failed.');
            const badge = document.querySelector(`[data-status-for="${select.dataset.orderId}"]`);
            badge.textContent = select.options[select.selectedIndex].text;
            badge.className = `status ${select.value}`;
            select.dataset.currentStatus = select.value;
        } catch (error) {
            alert(error.message);
            if (previous) select.value = previous;
        } finally {
            select.disabled = false;
        }
    });
    select.dataset.currentStatus = select.value;
});

document.querySelectorAll('.verify-payment').forEach((button) => {
    button.addEventListener('click', async () => {
        if (!confirm('Verify that this GCash payment was received?')) return;
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Payment verification failed.');
            button.parentElement.querySelector('.payment-status').textContent = 'Paid';
            button.remove();
        } catch (error) {
            alert(error.message);
            button.disabled = false;
        }
    });
});
</script>
@endpush
