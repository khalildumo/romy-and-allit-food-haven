@extends('layouts.app')
@section('title', 'My orders | Romy and Allit Food Haven')
@section('body-class', 'dashboard-page')
@section('content')
<header class="dashboard-header">
    <a class="brand" href="{{ route('home') }}">Romy and Allit Food Haven</a>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="button dark" type="submit">Log out</button></form>
</header>
<main class="orders">
    <a class="back-button" href="{{ route('home') }}">← Back to menu</a>
    <p class="eyebrow"><span></span> Customer portal</p>
    <h1>My orders</h1>
    @forelse ($orders as $order)
        <article class="order-card">
            <header><strong>{{ $order->order_number }}</strong><span class="status {{ $order->order_status }}">{{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</span></header>
            <ul>@foreach ($order->items_json ?? [] as $item)<li>{{ $item['name'] }} × {{ $item['quantity'] }} — ₱{{ number_format((float) $item['price'] * (int) $item['quantity'], 2) }}</li>@endforeach</ul>
            <div class="order-meta">
                Total: ₱{{ number_format((float) $order->total_amount, 2) }} · {{ ucfirst(str_replace('_', ' ', $order->fulfillment_type)) }} · {{ $order->preferred_time ?: 'No preferred time' }}<br>
                {{ $order->created_at->format('M j, Y g:i A') }}
                @if ($order->special_instructions)<br>Note: {{ $order->special_instructions }}@endif
            </div>
        </article>
    @empty
        <p>No orders yet. <a href="{{ route('home') }}#menu">Browse the menu</a></p>
    @endforelse
    {{ $orders->links() }}
</main>
@endsection
