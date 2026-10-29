@extends('layouts.app')

@section('title', 'Romy and Allit Food Haven — Good food, good mood.')

@section('content')
<div class="announcement">
    <span>✦</span> Lunchtime made easy — order ahead and skip the queue
    <a href="#menu">Explore today's menu <span>→</span></a>
</div>
<header class="site-header">
    <a class="brand" href="#top">{{ $profile?->canteen_name ?? 'Romy and Allit Food Haven' }}</a>
    <nav class="desktop-nav" aria-label="Main navigation">
        <a class="active" href="#top">Home</a>
        <a href="#menu">Our menu</a>
        <a href="#story">Our story</a>
        <a href="#contact">Find us</a>
        @auth
            <span>Hi, {{ Auth::user()->full_name }}</span>
            <a href="{{ route('orders.index') }}">My orders</a>
            @if (Auth::user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}">Admin</a>
            @endif
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-link" type="submit">Log out</button></form>
        @else
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('register') }}">Create account</a>
        @endauth
    </nav>
    <div class="header-actions">
        <button class="cart-button" id="cartButton" type="button" aria-label="Open cart">
            <span class="cart-icon">♧</span> Cart <b id="cartCount">0</b>
        </button>
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu">☰</button>
    </div>
</header>

<main id="top">
    @if (session('status'))
        <p class="account-success">{{ session('status') }}</p>
    @endif
    <section class="hero section-wrap">
        <div class="hero-copy">
            <p class="eyebrow"><span></span> Your neighborhood favorite</p>
            <h1>Romy and Allit<br><em>Food Haven</em></h1>
            <p class="hero-text">Familiar Filipino comfort food and Miki Abra made with the kind of care that feels like home. Fresh every day, always worth the little detour.</p>
            <div class="hero-actions">
                <a class="button primary" href="#menu">See what's cooking <span>→</span></a>
                <a class="text-link" href="#story">Our story <span>↗</span></a>
            </div>
            <div class="hero-meta">
                <div><strong>4.9/5</strong><span>from our regulars</span></div>
                <div class="meta-divider"></div>
                <div><strong>6:00–17:00</strong><span>Mon – Sat</span></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-image">
                <img src="{{ asset('assets/Dishes/Pork Adobo.jpeg') }}" alt="Pork adobo">
                <span class="image-sticker">Made with<br>love <i>♥</i></span>
            </div>
            <div class="floating-note"><span>Today's special</span><strong>Chicken Adobo</strong><b>₱40</b></div>
        </div>
    </section>
    <section class="marquee"><div>FRESHLY COOKED <span>✳</span> LOCALLY LOVED <span>✳</span> ALWAYS COMFORTING <span>✳</span> FRESHLY COOKED <span>✳</span> LOCALLY LOVED <span>✳</span></div></section>
    <section class="menu-section section-wrap" id="menu">
        <div class="section-heading">
            <div><p class="eyebrow"><span></span> What's on the table</p><h2>A little bit of <em>everything good.</em></h2></div>
            <a class="text-link" href="#menu">View all items <span>→</span></a>
        </div>
        <div class="category-tabs" role="tablist">
            <button class="active" data-category="all" type="button">All day favorites</button>
            @foreach ($categories as $category)
                <button data-category="{{ $category }}" type="button">{{ ['mains' => 'Dishes', 'breakfast' => 'Breakfast', 'snacks' => 'Snacks', 'drinks' => 'Drinks'][$category] ?? ucfirst($category) }}</button>
            @endforeach
        </div>
        <input class="menu-search" id="menuSearch" type="search" placeholder="Search menu items..." aria-label="Search menu items">
        <div class="menu-grid" id="menuGrid">
            @forelse ($products as $product)
                @php($inStock = ! $product->stock_enabled || $product->stock_quantity > 0)
                @php($imageUrl = $product->image_path === '' ? '' : (str_starts_with($product->image_path, 'products/') ? asset('storage/'.$product->image_path) : asset(str_starts_with($product->image_path, 'assets/') ? $product->image_path : 'assets/'.$product->image_path)))
                <article class="menu-card" data-category="{{ $product->category }}">
                    <div class="card-image">
                        @if ($imageUrl !== '')
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy">
                        @endif
                        <button class="add-button" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" type="button" @disabled(! $inStock) aria-label="{{ $inStock ? 'Add '.$product->name.' to cart' : $product->name.' is out of stock' }}">+</button>
                    </div>
                    <div class="card-info">
                        <div>
                            <h3>{{ $product->name }}</h3>
                            <p>{{ $product->description }}</p>
                            <span class="stock-tag {{ $inStock ? '' : 'out' }}">{{ $inStock ? ($product->stock_enabled ? $product->stock_quantity.' available' : 'In stock') : 'Out of stock' }}</span>
                        </div>
                        <strong>₱{{ number_format((float) $product->price, 2) }}</strong>
                    </div>
                </article>
            @empty
                <p>Our menu is being updated. Please check back soon.</p>
            @endforelse
        </div>
    </section>
    <section class="story section-wrap" id="story">
        <div class="story-image"><img src="{{ asset('assets/Dishes/Chicken Adobo.jpeg') }}" alt="Freshly prepared Filipino comfort food"></div>
        <div class="story-copy">
            <p class="eyebrow"><span></span> A seat at our table</p>
            <h2>From our family<br>to <em>your everyday.</em></h2>
            <p>Romy and Allit Food Haven started with a simple idea: the best meals don't need to be complicated. Just honest ingredients, generous portions, and a place where everybody knows your usual.</p>
            <a class="button dark" href="#contact">Come say hello <span>↗</span></a>
        </div>
    </section>
    <section class="quote-section">
        <p class="quote-mark">“</p>
        <blockquote>It tastes like the kind of meal you didn't know you needed.</blockquote>
        <p class="quote-by">— MARIA S. <span>·</span> happy regular since 2021</p>
    </section>
    <section class="contact section-wrap" id="contact">
        <div><p class="eyebrow"><span></span> Find your way to us</p><h2>See you <em>soon?</em></h2></div>
        <div class="contact-details">
            <p><strong>{{ $profile?->canteen_name ?? 'Romy and Allit Food Haven' }}</strong><br>ISPSC Tagudin Campus</p>
            <p><strong>{{ $profile?->operating_hours ?? 'Monday – Saturday, 6:00 AM – 5:00 PM' }}</strong><br>{{ $profile?->pickup_location ?? 'Main pickup counter' }}<br>{{ $profile?->contact_info }}</p>
        </div>
        <a class="button primary" href="https://maps.google.com/?q=ISPSC+Tagudin+Campus" target="_blank" rel="noopener noreferrer">Get directions <span>↗</span></a>
    </section>
</main>
<footer class="site-footer"><a class="brand" href="#top">Romy and Allit Food Haven</a><p>Good food, good mood.</p></footer>

<aside class="cart-drawer" id="cartDrawer" aria-hidden="true">
    <div class="drawer-head"><h2>Your order</h2><button id="closeCart" type="button" aria-label="Close cart">×</button></div>
    <div id="cartItems" class="cart-items"><div class="empty-cart"><span>✦</span><p>Your order is waiting<br>for something delicious.</p></div></div>
    <div class="cart-footer"><div><span>Total</span><strong id="cartTotal">₱0</strong></div><button class="button dark checkout-button" type="button">Checkout <span>→</span></button></div>
</aside>
<div class="overlay" id="overlay"></div>
<div class="item-modal" id="itemModal" aria-hidden="true">
    <article class="item-card"><header><h2 id="itemModalName"></h2><button id="closeItemModal" type="button" aria-label="Close item details">×</button></header><p id="itemModalDescription"></p><p class="item-price" id="itemModalPrice"></p><p id="itemModalStock"></p></article>
</div>
<div class="checkout-modal" id="checkoutModal" aria-hidden="true">
    <div class="checkout-card">
        <div class="drawer-head" id="checkoutHeader"><h2>Complete your order</h2><button id="closeCheckout" type="button" aria-label="Close checkout">×</button></div>
        <form id="checkoutForm" data-order-url="{{ route('orders.store') }}">
            <label>Student name<input name="student_name" required maxlength="120" autocomplete="name" placeholder="Enter your full name" value="{{ Auth::user()?->full_name }}"></label>
            <label>Student / institution ID <small>(optional)</small><input name="student_id" maxlength="60" value="{{ Auth::user()?->institution_id }}"></label>
            <fieldset><legend>Fulfillment</legend>
                <label class="payment-option"><input type="radio" name="fulfillment_type" value="pickup" checked><span><strong>Pick-up at counter</strong><small>Collect your order from the canteen counter</small></span></label>
                <label class="payment-option"><input type="radio" name="fulfillment_type" value="dine_in"><span><strong>Dine-in</strong><small>Enjoy your order at the canteen</small></span></label>
            </fieldset>
            <label>Preferred pickup / preparation time<input name="preferred_time" type="time"></label>
            <label>Special instructions <small>(optional)</small><textarea name="special_instructions" maxlength="500" placeholder="No mayonnaise, extra sauce, etc."></textarea></label>
            <fieldset><legend>Payment method</legend>
                <label class="payment-option" data-account=""><input type="radio" name="payment_method" value="cash" checked><span><strong>Cash</strong><small>Pay when you pick up your order</small></span></label>
                <label class="payment-option" data-account="09955155092"><input type="radio" name="payment_method" value="gcash"><span><strong>GCash</strong><small>Scan the QR code. Payment will be verified by the canteen.</small></span></label>
            </fieldset>
            <div class="payment-qr" id="paymentQr" data-gcash-qr="{{ asset('assets/QR Code/Qr Gcash.jpeg') }}" hidden></div>
            <p class="checkout-error" id="checkoutError" role="alert" aria-live="polite"></p>
            <button class="button dark" type="submit" id="confirmPayment">Place order <span>→</span></button>
        </form>
    </div>
    <div class="checkout-card receipt" id="receipt" hidden></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/checkout.js') }}" defer></script>
<script src="{{ asset('assets/site.js') }}" defer></script>
@endpush
