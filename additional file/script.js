const cart = [];
const cartButton = document.querySelector('#cartButton');
const cartDrawer = document.querySelector('#cartDrawer');
const overlay = document.querySelector('#overlay');
const cartItems = document.querySelector('#cartItems');
const cartCount = document.querySelector('#cartCount');
const cartTotal = document.querySelector('#cartTotal');

function renderCart() {
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  cartCount.textContent = totalItems;
  cartTotal.textContent = `₱${total.toLocaleString()}`;
  if (!cart.length) {
    cartItems.innerHTML = '<div class="empty-cart"><span>✦</span><p>Your order is waiting<br>for something delicious.</p></div>';
    return;
  }
  cartItems.innerHTML = cart.map((item, index) => `<div class="cart-row"><div><strong>${item.name}</strong><small>Qty ${item.quantity}</small></div><strong>₱${(item.price * item.quantity).toLocaleString()} <button data-remove="${index}" aria-label="Remove ${item.name}">×</button></strong></div>`).join('');
  cartItems.querySelectorAll('[data-remove]').forEach(button => button.addEventListener('click', () => { cart.splice(Number(button.dataset.remove), 1); renderCart(); }));
}

function toggleCart(open) { cartDrawer.classList.toggle('open', open); overlay.classList.toggle('open', open); cartDrawer.setAttribute('aria-hidden', String(!open)); }
document.querySelectorAll('.add-button').forEach(button => button.addEventListener('click', () => {
  const existing = cart.find(item => item.name === button.dataset.name);
  existing ? existing.quantity++ : cart.push({ name: button.dataset.name, price: Number(button.dataset.price), quantity: 1 });
  renderCart();
  toggleCart(true);
}));
cartButton.addEventListener('click', () => toggleCart(true));
document.querySelector('#closeCart').addEventListener('click', () => toggleCart(false));
overlay.addEventListener('click', () => toggleCart(false));
document.querySelectorAll('.category-tabs button').forEach(tab => tab.addEventListener('click', () => {
  document.querySelector('.category-tabs .active').classList.remove('active'); tab.classList.add('active');
  document.querySelectorAll('.menu-card').forEach(card => { card.style.display = tab.dataset.category === 'all' || card.dataset.category === tab.dataset.category ? '' : 'none'; });
}));
document.querySelector('.checkout-button').addEventListener('click', () => { if (cart.length) alert('Thanks! Pickup ordering will be available soon.'); });
