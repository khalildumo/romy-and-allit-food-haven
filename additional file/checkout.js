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

function renderCart() {
  cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
  cartTotal.textContent = `₱${getTotal().toLocaleString()}`;
  if (!cart.length) { cartItems.innerHTML = '<div class="empty-cart"><span>✦</span><p>Your order is waiting<br>for something delicious.</p></div>'; return; }
  cartItems.innerHTML = cart.map((item, index) => `<div class="cart-row"><div><strong>${escapeHtml(item.name)}</strong><div class="quantity-controls" aria-label="Quantity for ${escapeHtml(item.name)}"><button type="button" data-decrease="${index}" aria-label="Decrease quantity of ${escapeHtml(item.name)}">−</button><output>${item.quantity}</output><button type="button" data-increase="${index}" aria-label="Increase quantity of ${escapeHtml(item.name)}">+</button></div></div><div class="cart-row-total"><strong>₱${(item.price * item.quantity).toLocaleString()}</strong><button class="remove-item" type="button" data-remove="${index}" aria-label="Remove ${escapeHtml(item.name)}">×</button></div></div>`).join('');
  cartItems.querySelectorAll('[data-decrease]').forEach(button => button.addEventListener('click', () => { const item = cart[Number(button.dataset.decrease)]; item.quantity -= 1; if (item.quantity < 1) cart.splice(Number(button.dataset.decrease), 1); renderCart(); }));
  cartItems.querySelectorAll('[data-increase]').forEach(button => button.addEventListener('click', () => { const item = cart[Number(button.dataset.increase)]; item.quantity = Math.min(50, item.quantity + 1); renderCart(); }));
  cartItems.querySelectorAll('[data-remove]').forEach(button => button.addEventListener('click', () => { cart.splice(Number(button.dataset.remove), 1); renderCart(); }));
}
function escapeHtml(value) { return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character])); }
function toggleCart(open) { cartDrawer.classList.toggle('open', open); overlay.classList.toggle('open', open); cartDrawer.setAttribute('aria-hidden', String(!open)); }
function toggleCheckout(open) { checkoutModal.classList.toggle('open', open); checkoutModal.setAttribute('aria-hidden', String(!open)); if (open) updatePaymentQr(); }
function updatePaymentQr() {
  const method = checkoutForm.querySelector('input[name="payment_method"]:checked').value;
  if (method === 'cash') { paymentQr.hidden = true; paymentQr.innerHTML = ''; return; }
  const label = method === 'gcash' ? 'GCash' : 'Bank transfer';
  const account = checkoutForm.querySelector('input[name="payment_method"]:checked').closest('.payment-option').dataset.account;
  const text = `${label} | Romy and Allit Food Haven | Account ${account} | Amount PHP ${getTotal().toFixed(2)} | Food order`;
  const qrSource = method === 'gcash'
    ? 'QR%20Code/Qr%20Gcash.jpeg'
    : `https://api.qrserver.com/v1/create-qr-code/?size=190x190&data=${encodeURIComponent(text)}`;
  paymentQr.hidden = false;
  paymentQr.innerHTML = `<img src="${qrSource}" alt="${label} payment QR code"><strong>${label} payment</strong><small>Scan to pay ₱${getTotal().toLocaleString()} to ${account}.</small>`;
}
function showReceipt(order) {
  const method = order.payment_method === 'cash' ? 'Cash — pay on pickup' : order.payment_method === 'gcash' ? 'GCash — paid' : 'Bank transfer — paid';
  checkoutForm.hidden = true; checkoutHeader.hidden = true; checkoutModal.querySelector('.checkout-card:not(#receipt)').hidden = true; receipt.hidden = false;
  receipt.innerHTML = `<div class="receipt-icon">✓</div><p class="eyebrow">Order confirmed</p><h3>Thank you, ${order.student_name}!</h3><p>Your receipt is ready. Show this order number when you pick up your meal.</p><div class="receipt-details"><span>Order number<strong>${order.order_number}</strong></span><span>Payment<strong>${method}</strong></span><span>Total<strong>₱${order.total}</strong></span><span>Placed<strong>${order.created_at}</strong></span></div><button class="button dark" type="button" id="doneReceipt">Done</button>`;
  document.querySelector('#doneReceipt').addEventListener('click', () => { toggleCheckout(false); toggleCart(false); cart.length = 0; renderCart(); checkoutForm.reset(); checkoutForm.hidden = false; checkoutHeader.hidden = false; checkoutModal.querySelector('.checkout-card:not(#receipt)').hidden = false; receipt.hidden = true; });
}
document.querySelectorAll('.add-button').forEach(button => button.addEventListener('click', () => { const existing = cart.find(item => item.id === Number(button.dataset.id)); if (existing) existing.quantity = Math.min(50, existing.quantity + 1); else cart.push({ id: Number(button.dataset.id), name: button.dataset.name, price: Number(button.dataset.price), quantity: 1 }); renderCart(); toggleCart(true); }));
cartButton.addEventListener('click', () => toggleCart(true));
document.querySelector('#closeCart').addEventListener('click', () => toggleCart(false));
overlay.addEventListener('click', () => toggleCart(false));
document.querySelector('#closeCheckout').addEventListener('click', () => toggleCheckout(false));
document.querySelector('#closeItemModal').addEventListener('click', () => { itemModal.classList.remove('open'); itemModal.setAttribute('aria-hidden', 'true'); });
document.querySelectorAll('.menu-card').forEach(card => card.addEventListener('click', event => { if (event.target.closest('.add-button')) return; const name = card.querySelector('h3')?.textContent || ''; document.querySelector('#itemModalName').textContent = name; document.querySelector('#itemModalDescription').textContent = card.querySelector('.card-info p')?.textContent || ''; document.querySelector('#itemModalPrice').textContent = card.querySelector('.card-info > strong')?.textContent || ''; document.querySelector('#itemModalStock').textContent = card.querySelector('.stock-tag')?.textContent || ''; itemModal.classList.add('open'); itemModal.setAttribute('aria-hidden', 'false'); }));
document.querySelector('.checkout-button').addEventListener('click', () => { if (cart.length) { toggleCart(false); toggleCheckout(true); } });
document.querySelectorAll('input[name="payment_method"]').forEach(input => input.addEventListener('change', () => { updatePaymentQr(); document.querySelector('#confirmPayment').innerHTML = input.value === 'cash' ? 'Place order <span>→</span>' : 'I have paid <span>→</span>'; }));
checkoutForm.addEventListener('submit', async event => {
  event.preventDefault(); checkoutError.textContent = '';
  const button = document.querySelector('#confirmPayment'); button.disabled = true; button.textContent = 'Saving order…';
  const formData = new FormData(checkoutForm);
  try {
    const response = await fetch('checkout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ student_name: formData.get('student_name'), student_id: formData.get('student_id'), payment_method: formData.get('payment_method'), fulfillment_type: formData.get('fulfillment_type'), preferred_time: formData.get('preferred_time'), special_instructions: formData.get('special_instructions'), items: cart }) });
    const order = await response.json(); if (!response.ok || !order.success) throw new Error(order.message || 'Unable to place order.');
    order.student_name = formData.get('student_name'); showReceipt(order);
  } catch (error) { checkoutError.textContent = error.message; }
  button.disabled = false; button.innerHTML = 'Place order <span>→</span>';
});
document.querySelectorAll('.category-tabs button').forEach(tab => tab.addEventListener('click', () => { document.querySelector('.category-tabs .active').classList.remove('active'); tab.classList.add('active'); document.querySelectorAll('.menu-card').forEach(card => { card.style.display = tab.dataset.category === 'all' || card.dataset.category === tab.dataset.category ? '' : 'none'; }); }));
document.querySelector('#menuSearch')?.addEventListener('input', event => { const query = event.target.value.trim().toLowerCase(); document.querySelectorAll('.menu-card').forEach(card => { const name = card.querySelector('h3')?.textContent.toLowerCase() || ''; card.style.display = name.includes(query) ? '' : 'none'; }); });
renderCart();
