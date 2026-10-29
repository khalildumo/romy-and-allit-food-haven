const cart = [];
const cartButton = document.querySelector('#cartButton');
const cartDrawer = document.querySelector('#cartDrawer');
const overlay = document.querySelector('#overlay');
const cartItems = document.querySelector('#cartItems');
const cartCount = document.querySelector('#cartCount');
const cartTotal = document.querySelector('#cartTotal');
const checkoutModal = document.querySelector('#checkoutModal');
const checkoutHeader = document.querySelector('#checkoutHeader');
const checkoutForm = document.querySelector('#checkoutForm');
const paymentQr = document.querySelector('#paymentQr');
const checkoutError = document.querySelector('#checkoutError');
const receipt = document.querySelector('#receipt');
const itemModal = document.querySelector('#itemModal');
const getTotal = () => cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
}

function renderCart() {
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartTotal.textContent = `₱${getTotal().toLocaleString()}`;
    if (!cart.length) {
        cartItems.innerHTML = '<div class="empty-cart"><span>✦</span><p>Your order is waiting<br>for something delicious.</p></div>';
        return;
    }

    cartItems.innerHTML = cart.map((item, index) => `
        <div class="cart-row">
            <div><strong>${escapeHtml(item.name)}</strong>
                <div class="quantity-controls" aria-label="Quantity for ${escapeHtml(item.name)}">
                    <button type="button" data-decrease="${index}" aria-label="Decrease quantity">−</button>
                    <output>${item.quantity}</output>
                    <button type="button" data-increase="${index}" aria-label="Increase quantity">+</button>
                </div>
            </div>
            <div class="cart-row-total"><strong>₱${(item.price * item.quantity).toLocaleString()}</strong>
                <button class="remove-item" type="button" data-remove="${index}" aria-label="Remove item">×</button>
            </div>
        </div>`).join('');

    cartItems.querySelectorAll('[data-decrease]').forEach((button) => button.addEventListener('click', () => {
        const index = Number(button.dataset.decrease);
        cart[index].quantity -= 1;
        if (cart[index].quantity < 1) cart.splice(index, 1);
        renderCart();
    }));
    cartItems.querySelectorAll('[data-increase]').forEach((button) => button.addEventListener('click', () => {
        const item = cart[Number(button.dataset.increase)];
        item.quantity = Math.min(50, item.quantity + 1);
        renderCart();
    }));
    cartItems.querySelectorAll('[data-remove]').forEach((button) => button.addEventListener('click', () => {
        cart.splice(Number(button.dataset.remove), 1);
        renderCart();
    }));
}

function toggleCart(open) {
    cartDrawer.classList.toggle('open', open);
    overlay.classList.toggle('open', open);
    cartDrawer.setAttribute('aria-hidden', String(!open));
}

function toggleCheckout(open) {
    checkoutModal.classList.toggle('open', open);
    checkoutModal.setAttribute('aria-hidden', String(!open));
    if (open) updatePaymentQr();
}

function updatePaymentQr() {
    const method = checkoutForm.querySelector('input[name="payment_method"]:checked').value;
    if (method === 'cash') {
        paymentQr.hidden = true;
        paymentQr.innerHTML = '';
        return;
    }

    paymentQr.hidden = false;
    paymentQr.innerHTML = `<img src="${escapeHtml(paymentQr.dataset.gcashQr)}" alt="GCash payment QR code">
        <strong>GCash payment</strong><small>Scan to pay ₱${getTotal().toLocaleString()}. The canteen will verify your payment.</small>`;
}

function resetCheckout() {
    toggleCheckout(false);
    toggleCart(false);
    cart.length = 0;
    renderCart();
    checkoutForm.reset();
    checkoutForm.hidden = false;
    checkoutHeader.hidden = false;
    checkoutModal.querySelector('.checkout-card:not(#receipt)').hidden = false;
    receipt.hidden = true;
}

function showReceipt(order, studentName) {
    const payment = order.payment_method === 'cash'
        ? 'Cash — pay on pickup'
        : 'GCash — awaiting verification';
    checkoutForm.hidden = true;
    checkoutHeader.hidden = true;
    checkoutModal.querySelector('.checkout-card:not(#receipt)').hidden = true;
    receipt.hidden = false;
    receipt.innerHTML = `<div class="receipt-icon">✓</div><p class="eyebrow">Order confirmed</p>
        <h3>Thank you, ${escapeHtml(studentName)}!</h3>
        <p>Show this order number when you pick up your meal.</p>
        <div class="receipt-details"><span>Order number<strong>${escapeHtml(order.order_number)}</strong></span>
        <span>Payment<strong>${payment}</strong></span><span>Total<strong>₱${escapeHtml(order.total)}</strong></span>
        <span>Placed<strong>${escapeHtml(order.created_at)}</strong></span></div>
        <button class="button dark" type="button" id="doneReceipt">Done</button>`;
    document.querySelector('#doneReceipt').addEventListener('click', resetCheckout);
}

document.querySelectorAll('.add-button').forEach((button) => button.addEventListener('click', () => {
    const id = Number(button.dataset.id);
    const existing = cart.find((item) => item.id === id);
    if (existing) existing.quantity = Math.min(50, existing.quantity + 1);
    else cart.push({ id, name: button.dataset.name, price: Number(button.dataset.price), quantity: 1 });
    renderCart();
    toggleCart(true);
}));

cartButton.addEventListener('click', () => toggleCart(true));
document.querySelector('#closeCart').addEventListener('click', () => toggleCart(false));
overlay.addEventListener('click', () => toggleCart(false));
document.querySelector('#closeCheckout').addEventListener('click', () => toggleCheckout(false));
document.querySelector('#closeItemModal').addEventListener('click', () => {
    itemModal.classList.remove('open');
    itemModal.setAttribute('aria-hidden', 'true');
});

document.querySelectorAll('.menu-card').forEach((card) => card.addEventListener('click', (event) => {
    if (event.target.closest('.add-button')) return;
    document.querySelector('#itemModalName').textContent = card.querySelector('h3')?.textContent || '';
    document.querySelector('#itemModalDescription').textContent = card.querySelector('.card-info p')?.textContent || '';
    document.querySelector('#itemModalPrice').textContent = card.querySelector('.card-info > strong')?.textContent || '';
    document.querySelector('#itemModalStock').textContent = card.querySelector('.stock-tag')?.textContent || '';
    itemModal.classList.add('open');
    itemModal.setAttribute('aria-hidden', 'false');
}));

document.querySelector('.checkout-button').addEventListener('click', () => {
    if (!cart.length) return;
    toggleCart(false);
    toggleCheckout(true);
});

document.querySelectorAll('input[name="payment_method"]').forEach((input) => input.addEventListener('change', updatePaymentQr));

checkoutForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    checkoutError.textContent = '';
    const button = document.querySelector('#confirmPayment');
    button.disabled = true;
    button.textContent = 'Saving order…';
    const formData = new FormData(checkoutForm);
    try {
        const response = await fetch(checkoutForm.dataset.orderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                student_name: formData.get('student_name'),
                student_id: formData.get('student_id'),
                payment_method: formData.get('payment_method'),
                fulfillment_type: formData.get('fulfillment_type'),
                preferred_time: formData.get('preferred_time'),
                special_instructions: formData.get('special_instructions'),
                items: cart.map(({ id, quantity }) => ({ id, quantity }))
            })
        });
        const order = await response.json();
        if (!response.ok || !order.success) throw new Error(order.message || Object.values(order.errors || {}).flat()[0] || 'Unable to place order.');
        showReceipt({ ...order, payment_method: formData.get('payment_method') }, formData.get('student_name'));
    } catch (error) {
        checkoutError.textContent = error.message || 'Unable to place order.';
    } finally {
        button.disabled = false;
        button.innerHTML = 'Place order <span>→</span>';
    }
});
