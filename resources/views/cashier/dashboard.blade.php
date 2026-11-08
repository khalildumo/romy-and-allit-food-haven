@extends('layouts.app')

@section('title', 'Cashier Dashboard | Romy and Allit Food Haven')

@section('content')
<main class="dashboard-content">
    <div class="dashboard-title">
        <div>
            <p class="eyebrow"><span></span> Cashier dashboard</p>
            <h1>Order overview.</h1>
            <p class="dashboard-subtitle">
                Manage customer orders and verify payments.
            </p>
        </div>

        <div class="dashboard-actions">
            <a class="button dark" href="{{ route('home') }}">
                View customer site
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="button dark" type="submit">Log out</button>
            </form>
        </div>
    </div>

    <section class="stats-grid" aria-label="Order summary">
        <div>
            <small>Total orders shown</small>
            <strong>{{ $orders->count() }}</strong>
        </div>

        <div>
            <small>Pending orders</small>
            <strong>
                {{ $orders->where('order_status', 'pending')->count() }}
            </strong>
        </div>

        <div>
            <small>Pending GCash payments</small>
            <strong>
                {{ $orders->where('payment_method', 'gcash')->where('payment_status', 'pending')->count() }}
            </strong>
        </div>
    </section>

    <section class="orders-panel dashboard-section-spaced">
        <div class="panel-heading">
            <h2>Customer orders</h2>
            <span>{{ now()->format('F j, Y') }}</span>
        </div>

        <div class="orders-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Student</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Placed</th>
                        <th>Update status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->order_number }}</strong>
                            </td>

                            <td>
                                {{ $order->student_name }}
                                <small>{{ $order->student_id }}</small>
                            </td>

                            <td data-payment-for="{{ $order->id }}">
                                {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}

                                <small class="payment-status">
                                    {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                                </small>

                                @if ($order->payment_method === 'gcash' && $order->payment_status === 'pending')
                                    <button
                                        class="verify-payment"
                                        type="button"
                                        data-url="{{ route('cashier.orders.payment', $order) }}">
                                        Verify GCash payment
                                    </button>
                                @endif
                            </td>

                            <td>
                                <span
                                    class="status {{ $order->order_status }}"
                                    data-status-for="{{ $order->id }}">
                                    {{ $orderStatuses[$order->order_status] ?? ucfirst($order->order_status) }}
                                </span>
                            </td>

                            <td>
                                ₱{{ number_format((float) $order->total_amount, 2) }}
                            </td>

                            <td>
                                {{ $order->created_at?->format('M j, g:i A') ?? 'N/A' }}
                            </td>

                            <td>
                                <select
                                    class="order-status-select"
                                    data-order-id="{{ $order->id }}"
                                    data-url="{{ route('cashier.orders.status', $order) }}"
                                    aria-label="Update order status">
                                    @foreach ($orderStatuses as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @selected($order->order_status === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="no-orders">
                                No orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector(
    'meta[name="csrf-token"]'
)?.content;

async function sendPatch(url, body = null) {
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    };

    const options = {
        method: 'PATCH',
        headers
    };

    if (body !== null) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    let result = {};

    try {
        result = await response.json();
    } catch {
        throw new Error('The server returned an invalid response.');
    }

    if (!response.ok) {
        throw new Error(
            result.message || 'The requested update could not be completed.'
        );
    }

    return result;
}

// Update order status.
document.querySelectorAll('.order-status-select').forEach((select) => {
    select.dataset.currentStatus = select.value;

    select.addEventListener('change', async () => {
        const previous = select.dataset.currentStatus;
        const newStatus = select.value;

        select.disabled = true;

        try {
            await sendPatch(select.dataset.url, {
                status: newStatus
            });

            const badge = document.querySelector(
                `[data-status-for="${select.dataset.orderId}"]`
            );

            if (badge) {
                badge.textContent =
                    select.options[select.selectedIndex].text;

                badge.className = `status ${newStatus}`;
            }

            select.dataset.currentStatus = newStatus;

        } catch (error) {
            alert(error.message);
            select.value = previous;

        } finally {
            select.disabled = false;
        }
    });
});

// Verify GCash payments.
document.querySelectorAll('.verify-payment').forEach((button) => {
    button.addEventListener('click', async () => {
        const confirmed = confirm(
            'Verify that this GCash payment was received?'
        );

        if (!confirmed) {
            return;
        }

        button.disabled = true;

        try {
            await sendPatch(button.dataset.url);

            const paymentCell = button.closest('[data-payment-for]');

            const paymentStatus = paymentCell?.querySelector(
                '.payment-status'
            );

            if (paymentStatus) {
                paymentStatus.textContent = 'Paid';
            }

            button.remove();

        } catch (error) {
            alert(error.message);
            button.disabled = false;
        }
    });
});
</script>
@endpush
