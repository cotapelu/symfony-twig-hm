<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';

  interface CartItem {
    id: string;
    type: string;
    name: string;
    location: string;
    image: string;
    price: number;
    quantity: number;
    date?: string;
  }

  let cart: CartItem[] = [];
  let selectedPaymentMethod: 'card' | 'paypal' | 'bank' = 'card';
  let processing = false;
  let promoCode = '';
  let discount = 0;

  // Form fields
  let cardNumber = '';
  let cardExpiry = '';
  let cardCvc = '';
  let cardName = '';
  let billingAddress = '';
  let billingCity = '';
  let billingZip = '';
  let billingCountry = 'US';
  let saveCard = false;

  onMount(() => {
    loadCart();
  });

  function loadCart() {
    try {
      const saved = localStorage.getItem('checkout_cart');
      if (saved) {
        cart = JSON.parse(saved);
      }
    } catch (err) {
      console.error('Failed to load cart:', err);
      cart = [];
    }
  }

  function getSubtotal(): number {
    return cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  }

  function getTax(): number {
    return getSubtotal() * 0.1; // 10% tax
  }

  function getTotal(): number {
    return getSubtotal() + getTax() - discount;
  }

  function applyPromoCode() {
    // Demo promo codes
    const promoCodes: Record<string, number> = {
      'SAVE10': 0.1,
      'SAVE20': 0.2,
      'WELCOME': 0.15
    };
    
    const code = promoCode.toUpperCase().trim();
    if (promoCodes[code]) {
      discount = getSubtotal() * promoCodes[code];
      alert(`Promo code applied! ${promoCodes[code] * 100}% off`);
    } else {
      alert('Invalid promo code');
    }
  }

  function removeFromCart(itemId: string) {
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('checkout_cart', JSON.stringify(cart));
  }

  function formatCardNumber(value: string): string {
    return value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
  }

  function formatExpiry(value: string): string {
    return value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2');
  }

  async function processPayment() {
    if (!validateForm()) {
      return;
    }

    processing = true;

    try {
      // Simulate payment processing
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Create payment intent via API
      const response = await fetch('http://localhost:9030/api/payments/intent', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          amount: getTotal(),
          currency: 'USD',
          description: cart.map(item => item.name).join(', ')
        })
      });

      if (!response.ok) {
        throw new Error('Payment failed');
      }

      // Clear cart
      cart = [];
      localStorage.removeItem('checkout_cart');

      // Redirect to confirmation
      goto('/booking/confirmation?id=NEW&type=payment');
    } catch (err) {
      alert('Payment failed: ' + (err as Error).message);
    } finally {
      processing = false;
    }
  }

  function validateForm(): boolean {
    if (selectedPaymentMethod === 'card') {
      if (!cardNumber || cardNumber.replace(/\s/g, '').length < 16) {
        alert('Please enter a valid card number');
        return false;
      }
      if (!cardExpiry || !cardExpiry.includes('/')) {
        alert('Please enter a valid expiry date');
        return false;
      }
      if (!cardCvc || cardCvc.length < 3) {
        alert('Please enter a valid CVC');
        return false;
      }
      if (!cardName) {
        alert('Please enter the cardholder name');
        return false;
      }
    }
    return true;
  }

  function getPriceDisplay(price: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(price);
  }
</script>

<svelte:head>
  <title>Checkout - Travel App</title>
</svelte:head>

<div class="checkout-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>💳 Checkout</h1>
      <div class="secure-badge">🔒 Secure</div>
    </div>
  </header>

  <main class="main-content">
    {#if cart.length === 0}
      <div class="empty-cart">
        <div class="empty-icon">🛒</div>
        <h2>Your cart is empty</h2>
        <p>Add items to your cart to proceed with checkout</p>
        <button class="primary-btn" onclick={() => goto('/dashboard')}>
          Browse Services
        </button>
      </div>
    {:else}
      <div class="checkout-layout">
        <!-- Payment Form -->
        <div class="payment-section">
          <!-- Payment Method -->
          <div class="section-card">
            <h2>Payment Method</h2>
            <div class="payment-methods">
              <label class="method-option" class:selected={selectedPaymentMethod === 'card'}>
                <input 
                  type="radio" 
                  name="payment" 
                  value="card"
                  bind:group={selectedPaymentMethod}
                />
                <span class="method-icon">💳</span>
                <span class="method-name">Credit Card</span>
              </label>
              <label class="method-option" class:selected={selectedPaymentMethod === 'paypal'}>
                <input 
                  type="radio" 
                  name="payment" 
                  value="paypal"
                  bind:group={selectedPaymentMethod}
                />
                <span class="method-icon">🅿️</span>
                <span class="method-name">PayPal</span>
              </label>
              <label class="method-option" class:selected={selectedPaymentMethod === 'bank'}>
                <input 
                  type="radio" 
                  name="payment" 
                  value="bank"
                  bind:group={selectedPaymentMethod}
                />
                <span class="method-icon">🏦</span>
                <span class="method-name">Bank Transfer</span>
              </label>
            </div>
          </div>

          <!-- Card Details -->
          {#if selectedPaymentMethod === 'card'}
            <div class="section-card">
              <h2>Card Details</h2>
              <div class="form-grid">
                <div class="form-group full">
                  <label for="cardName">Cardholder Name</label>
                  <input 
                    type="text" 
                    id="cardName"
                    placeholder="John Doe"
                    bind:value={cardName}
                  />
                </div>
                <div class="form-group full">
                  <label for="cardNumber">Card Number</label>
                  <input 
                    type="text" 
                    id="cardNumber"
                    placeholder="1234 5678 9012 3456"
                    maxlength="19"
                    value={formatCardNumber(cardNumber)}
                    oninput={(e) => cardNumber = (e.target as HTMLInputElement).value.replace(/\D/g, '')}
                  />
                </div>
                <div class="form-group">
                  <label for="cardExpiry">Expiry Date</label>
                  <input 
                    type="text" 
                    id="cardExpiry"
                    placeholder="MM/YY"
                    maxlength="5"
                    value={formatExpiry(cardExpiry)}
                    oninput={(e) => cardExpiry = (e.target as HTMLInputElement).value.replace(/\D/g, '')}
                  />
                </div>
                <div class="form-group">
                  <label for="cardCvc">CVC</label>
                  <input 
                    type="text" 
                    id="cardCvc"
                    placeholder="123"
                    maxlength="4"
                    bind:value={cardCvc}
                  />
                </div>
              </div>
              
              <label class="checkbox-label">
                <input type="checkbox" bind:checked={saveCard} />
                <span>Save card for future payments</span>
              </label>
            </div>
          {:else if selectedPaymentMethod === 'paypal'}
            <div class="section-card">
              <h2>PayPal</h2>
              <p class="info-text">You will be redirected to PayPal to complete your payment securely.</p>
            </div>
          {:else}
            <div class="section-card">
              <h2>Bank Transfer</h2>
              <p class="info-text">We will provide you with bank details to complete the transfer. Your booking will be confirmed once payment is received.</p>
            </div>
          {/if}

          <!-- Billing Address -->
          <div class="section-card">
            <h2>Billing Address</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="billingAddress">Street Address</label>
                <input 
                  type="text" 
                  id="billingAddress"
                  placeholder="123 Main Street"
                  bind:value={billingAddress}
                />
              </div>
              <div class="form-group">
                <label for="billingCity">City</label>
                <input 
                  type="text" 
                  id="billingCity"
                  placeholder="New York"
                  bind:value={billingCity}
                />
              </div>
              <div class="form-group">
                <label for="billingZip">ZIP Code</label>
                <input 
                  type="text" 
                  id="billingZip"
                  placeholder="10001"
                  bind:value={billingZip}
                />
              </div>
              <div class="form-group full">
                <label for="billingCountry">Country</label>
                <select id="billingCountry" bind:value={billingCountry}>
                  <option value="US">United States</option>
                  <option value="CA">Canada</option>
                  <option value="UK">United Kingdom</option>
                  <option value="AU">Australia</option>
                  <option value="DE">Germany</option>
                  <option value="FR">France</option>
                  <option value="JP">Japan</option>
                  <option value="VN">Vietnam</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Promo Code -->
          <div class="section-card">
            <h2>Promo Code</h2>
            <div class="promo-input">
              <input 
                type="text" 
                placeholder="Enter promo code"
                bind:value={promoCode}
              />
              <button onclick={applyPromoCode}>Apply</button>
            </div>
            <p class="hint">Try: SAVE10, SAVE20, WELCOME</p>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="summary-section">
          <div class="summary-card sticky">
            <h2>Order Summary</h2>
            
            <!-- Cart Items -->
            <div class="cart-items">
              {#each cart as item (item.id)}
                <div class="cart-item">
                  <div class="item-image" style="background-image: url({item.image})"></div>
                  <div class="item-details">
                    <h4>{item.name}</h4>
                    <p>{item.location}</p>
                    {#if item.date}
                      <p class="item-date">📅 {item.date}</p>
                    {/if}
                    <p class="item-qty">Qty: {item.quantity}</p>
                  </div>
                  <div class="item-price">
                    {getPriceDisplay(item.price * item.quantity)}
                  </div>
                  <button class="remove-btn" onclick={() => removeFromCart(item.id)}>✕</button>
                </div>
              {/each}
            </div>

            <!-- Price Breakdown -->
            <div class="price-breakdown">
              <div class="price-row">
                <span>Subtotal</span>
                <span>{getPriceDisplay(getSubtotal())}</span>
              </div>
              <div class="price-row">
                <span>Taxes & Fees</span>
                <span>{getPriceDisplay(getTax())}</span>
              </div>
              {#if discount > 0}
                <div class="price-row discount">
                  <span>Discount</span>
                  <span>-{getPriceDisplay(discount)}</span>
                </div>
              {/if}
              <div class="price-row total">
                <span>Total</span>
                <span>{getPriceDisplay(getTotal())}</span>
              </div>
            </div>

            <!-- Pay Button -->
            <button 
              class="pay-btn" 
              onclick={processPayment}
              disabled={processing}
            >
              {#if processing}
                <span class="spinner"></span>
                Processing...
              {:else}
                Pay {getPriceDisplay(getTotal())}
              {/if}
            </button>

            <!-- Security Info -->
            <div class="security-info">
              <p>🔒 Your payment is secured with 256-bit SSL encryption</p>
              <div class="payment-icons">
                <span>💳</span>
                <span>🅿️</span>
                <span>🏦</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    {/if}
  </main>
</div>

<style>
  .checkout-page {
    min-height: 100vh;
    background: #f8f9fa;
  }

  .header {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .back-btn {
    background: none;
    border: none;
    font-size: 1rem;
    color: #666;
    cursor: pointer;
  }

  h1 {
    font-size: 1.25rem;
    margin: 0;
  }

  .secure-badge {
    font-size: 0.875rem;
    color: #28a745;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-cart h2 {
    color: #1a1a2e;
    margin-bottom: 0.5rem;
  }

  .empty-cart p {
    color: #666;
    margin-bottom: 2rem;
  }

  .primary-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
  }

  .checkout-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
  }

  .section-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  .section-card h2 {
    font-size: 1.125rem;
    color: #1a1a2e;
    margin: 0 0 1rem;
  }

  .payment-methods {
    display: flex;
    gap: 1rem;
  }

  .method-option {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .method-option:hover {
    border-color: #667eea;
  }

  .method-option.selected {
    border-color: #667eea;
    background: #f0f4ff;
  }

  .method-option input {
    display: none;
  }

  .method-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .method-name {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .form-group {
    margin-bottom: 0;
  }

  .form-group.full {
    grid-column: span 2;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
  }

  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: #667eea;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    cursor: pointer;
  }

  .info-text {
    color: #666;
    font-size: 0.875rem;
    margin: 0;
  }

  .promo-input {
    display: flex;
    gap: 0.5rem;
  }

  .promo-input input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
  }

  .promo-input button {
    padding: 0.75rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }

  .hint {
    font-size: 0.75rem;
    color: #999;
    margin: 0.5rem 0 0;
  }

  .summary-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  .summary-card.sticky {
    position: sticky;
    top: 80px;
  }

  .summary-card h2 {
    font-size: 1.125rem;
    color: #1a1a2e;
    margin: 0 0 1rem;
  }

  .cart-items {
    margin-bottom: 1.5rem;
    max-height: 300px;
    overflow-y: auto;
  }

  .cart-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
    position: relative;
  }

  .item-image {
    width: 60px;
    height: 60px;
    background-size: cover;
    background-position: center;
    border-radius: 6px;
    flex-shrink: 0;
  }

  .item-details {
    flex: 1;
  }

  .item-details h4 {
    font-size: 0.875rem;
    margin: 0 0 0.25rem;
    color: #1a1a2e;
  }

  .item-details p {
    font-size: 0.75rem;
    color: #666;
    margin: 0;
  }

  .item-date {
    margin-top: 0.25rem !important;
  }

  .item-qty {
    margin-top: 0.25rem !important;
  }

  .item-price {
    font-weight: 600;
    color: #667eea;
  }

  .remove-btn {
    position: absolute;
    top: 0.5rem;
    right: 0;
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
  }

  .remove-btn:hover {
    color: #ff4757;
  }

  .price-breakdown {
    border-top: 1px solid #eee;
    padding-top: 1rem;
    margin-bottom: 1rem;
  }

  .price-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: #666;
  }

  .price-row.discount {
    color: #28a745;
  }

  .price-row.total {
    border-top: 2px solid #eee;
    padding-top: 1rem;
    margin-top: 0.5rem;
    font-weight: 600;
    font-size: 1.125rem;
    color: #1a1a2e;
  }

  .pay-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .pay-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .security-info {
    margin-top: 1.5rem;
    text-align: center;
  }

  .security-info p {
    font-size: 0.75rem;
    color: #999;
    margin: 0 0 0.5rem;
  }

  .payment-icons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    font-size: 1.5rem;
  }

  @media (max-width: 900px) {
    .checkout-layout {
      grid-template-columns: 1fr;
    }
    
    .summary-card.sticky {
      position: static;
    }
  }

  @media (max-width: 600px) {
    .payment-methods {
      flex-direction: column;
    }
    
    .form-grid {
      grid-template-columns: 1fr;
    }
    
    .form-group.full {
      grid-column: span 1;
    }
  }
</style>
