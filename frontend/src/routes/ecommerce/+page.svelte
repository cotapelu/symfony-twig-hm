<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, fly, scale } from 'svelte/transition';

  // Types
  interface Product {
    id: number;
    name: string;
    price: number;
    sale_price?: number;
    category: string;
    description?: string;
    images: string[];
    stock: number;
    status: string;
    rating?: number;
    reviews?: number;
  }

  interface CartItem extends Product {
    quantity: number;
  }

  // State
  let products: Product[] = [];
  let loading = false;
  let error = '';
  let searchQuery = '';
  let selectedCategory = 'all';
  let priceRange = [0, 10000000];
  let sortBy = 'default';
  let showFilters = false;
  
  // Cart state
  let cart: CartItem[] = [];
  let showCart = false;
  let showProductModal = false;
  let selectedProduct: Product | null = null;
  
  // Categories derived from products
  let categories: string[] = ['all'];
  
  // Filtered products
  $: filteredProducts = products.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      product.description?.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || product.category === selectedCategory;
    const price = product.sale_price || product.price;
    const matchesPrice = price >= priceRange[0] && price <= priceRange[1];
    return matchesSearch && matchesCategory && matchesPrice;
  }).sort((a, b) => {
    switch(sortBy) {
      case 'price-asc': return (a.sale_price || a.price) - (b.sale_price || b.price);
      case 'price-desc': return (b.sale_price || b.price) - (a.sale_price || a.price);
      case 'name': return a.name.localeCompare(b.name);
      case 'rating': return (b.rating || 0) - (a.rating || 0);
      default: return 0;
    }
  });
  
  // Cart computed values
  $: cartTotal = cart.reduce((sum, item) => sum + (item.sale_price || item.price) * item.quantity, 0);
  $: cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  // Load products
  async function loadProducts() {
    loading = true;
    try {
      const res = await fetch('/api/ecommerce/products');
      const data = await res.json();
      products = data.data || [];
      // Extract unique categories
      const cats = [...new Set(products.map(p => p.category))];
      categories = ['all', ...cats];
    } catch (e) {
      error = 'Failed to load products';
    }
    loading = false;
  }

  // Load cart from localStorage
  function loadCart() {
    const saved = localStorage.getItem('ecommerce-cart');
    if (saved) {
      try {
        cart = JSON.parse(saved);
      } catch (e) {
        cart = [];
      }
    }
  }

  // Save cart to localStorage
  function saveCart() {
    localStorage.setItem('ecommerce-cart', JSON.stringify(cart));
  }

  // Add to cart
  function addToCart(product: Product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
      if (existing.quantity < product.stock) {
        existing.quantity++;
        cart = [...cart];
      }
    } else {
      cart = [...cart, { ...product, quantity: 1 }];
    }
    saveCart();
  }

  // Remove from cart
  function removeFromCart(productId: number) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
  }

  // Update quantity
  function updateQuantity(productId: number, delta: number) {
    const item = cart.find(i => i.id === productId);
    if (item) {
      const newQty = item.quantity + delta;
      if (newQty <= 0) {
        removeFromCart(productId);
      } else if (newQty <= item.stock) {
        item.quantity = newQty;
        cart = [...cart];
        saveCart();
      }
    }
  }

  // Open product details
  function openProductDetails(product: Product) {
    selectedProduct = product;
    showProductModal = true;
  }

  // Format price
  function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
  }

  // Clear cart
  function clearCart() {
    cart = [];
    saveCart();
  }

  // Checkout simulation
  function checkout() {
    if (cart.length === 0) return;
    alert(`Checkout complete! Total: ${formatPrice(cartTotal)}\nThank you for your purchase!`);
    clearCart();
    showCart = false;
  }

  onMount(() => {
    loadProducts();
    loadCart();
  });
</script>

<div class="ecommerce-page">
  <!-- Header -->
  <header class="header">
    <div class="header-left">
      <h1>🛒 Shop</h1>
      <span class="product-count">{filteredProducts.length} products</span>
    </div>
    <div class="header-right">
      <button class="cart-btn" on:click={() => showCart = !showCart}>
        <span class="cart-icon">🛒</span>
        {#if cartCount > 0}
          <span class="cart-badge" transition:scale>{cartCount}</span>
        {/if}
      </button>
    </div>
  </header>

  <!-- Search & Filter Bar -->
  <div class="filter-bar">
    <div class="search-box">
      <input 
        type="text" 
        placeholder="Search products..." 
        bind:value={searchQuery}
      />
      <span class="search-icon">🔍</span>
    </div>
    
    <button class="filter-toggle" on:click={() => showFilters = !showFilters}>
      <span>⚙️ Filters</span>
    </button>
    
    <select bind:value={selectedCategory} class="category-select">
      {#each categories as cat}
        <option value={cat}>{cat === 'all' ? 'All Categories' : cat}</option>
      {/each}
    </select>
    
    <select bind:value={sortBy} class="sort-select">
      <option value="default">Sort by: Default</option>
      <option value="price-asc">Price: Low to High</option>
      <option value="price-desc">Price: High to Low</option>
      <option value="name">Name: A-Z</option>
      <option value="rating">Rating: High to Low</option>
    </select>
  </div>

  <!-- Expanded Filters Panel -->
  {#if showFilters}
    <div class="filters-panel" transition:fly={{ y: -20 }}>
      <div class="filter-group">
        <label>Price Range</label>
        <div class="price-inputs">
          <input 
            type="number" 
            bind:value={priceRange[0]} 
            placeholder="Min"
          />
          <span>-</span>
          <input 
            type="number" 
            bind:value={priceRange[1]} 
            placeholder="Max"
          />
        </div>
      </div>
      <button class="clear-filters" on:click={() => {
        searchQuery = '';
        selectedCategory = 'all';
        priceRange = [0, 10000000];
        sortBy = 'default';
      }}>
        Clear All Filters
      </button>
    </div>
  {/if}

  <!-- Stats Row -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-icon">📦</span>
      <div class="stat-content">
        <span class="label">Total Products</span>
        <span class="value">{products.length}</span>
      </div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">✅</span>
      <div class="stat-content">
        <span class="label">In Stock</span>
        <span class="value">{products.filter(p => p.status === 'active' && p.stock > 0).length}</span>
      </div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">🔥</span>
      <div class="stat-content">
        <span class="label">On Sale</span>
        <span class="value">{products.filter(p => p.sale_price).length}</span>
      </div>
    </div>
    <div class="stat-card">
      <span class="stat-icon">💰</span>
      <div class="stat-content">
        <span class="label">Cart Total</span>
        <span class="value">{formatPrice(cartTotal)}</span>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  {#if loading}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Loading products...</p>
    </div>
  {:else if error}
    <div class="error-state">
      <span class="error-icon">⚠️</span>
      <p>{error}</p>
      <button class="retry-btn" on:click={loadProducts}>Retry</button>
    </div>
  {:else if filteredProducts.length === 0}
    <div class="empty-state">
      <span class="empty-icon">🔍</span>
      <h3>No products found</h3>
      <p>Try adjusting your search or filters</p>
    </div>
  {:else}
    <!-- Product Grid -->
    <div class="product-grid">
      {#each filteredProducts as product (product.id)}
        <div 
          class="product-card" 
          on:click={() => openProductDetails(product)}
          on:keypress={(e) => e.key === 'Enter' && openProductDetails(product)}
          role="button"
          tabindex="0"
          transition:fade={{ duration: 200 }}
        >
          <div class="image-container">
            {#if product.images[0]}
              <img src={product.images[0]} alt={product.name} loading="lazy" />
            {:else}
              <div class="no-image">📷</div>
            {/if}
            {#if product.sale_price}
              <span class="sale-badge">SALE</span>
            {/if}
            {#if product.stock === 0}
              <span class="out-of-stock">Out of Stock</span>
            {/if}
            <div class="quick-actions">
              <button 
                class="quick-add" 
                on:click|stopPropagation={() => addToCart(product)}
                disabled={product.stock === 0}
                title="Add to cart"
              >
                🛒
              </button>
            </div>
          </div>
          <div class="product-info">
            <span class="category-tag">{product.category}</span>
            <h3 class="product-name">{product.name}</h3>
            {#if product.rating}
              <div class="rating">
                <span class="stars">{'⭐'.repeat(Math.floor(product.rating))}</span>
                <span class="review-count">({product.reviews || 0})</span>
              </div>
            {/if}
            <div class="price-row">
              <span class="current-price">
                {formatPrice(product.sale_price || product.price)}
              </span>
              {#if product.sale_price}
                <span class="original-price">{formatPrice(product.price)}</span>
                <span class="discount">
                  -{Math.round((1 - product.sale_price / product.price) * 100)}%
                </span>
              {/if}
            </div>
            <div class="stock-info" class:low={product.stock < 10 && product.stock > 0}>
              {#if product.stock > 0}
                <span class="stock-status">✓ In Stock ({product.stock})</span>
              {:else}
                <span class="stock-status out">✕ Out of Stock</span>
              {/if}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<!-- Cart Sidebar -->
{#if showCart}
  <div class="cart-overlay" on:click={() => showCart = false} on:keypress={() => {}} role="button" tabindex="-1" transition:fade></div>
  <div class="cart-sidebar" transition:fly={{ x: 300 }}>
    <div class="cart-header">
      <h2>🛒 Your Cart</h2>
      <button class="close-cart" on:click={() => showCart = false}>✕</button>
    </div>
    
    {#if cart.length === 0}
      <div class="cart-empty">
        <span class="empty-cart-icon">🛒</span>
        <p>Your cart is empty</p>
      </div>
    {:else}
      <div class="cart-items">
        {#each cart as item (item.id)}
          <div class="cart-item" transition:fade>
            <div class="cart-item-image">
              {#if item.images[0]}
                <img src={item.images[0]} alt={item.name} />
              {:else}
                <div class="no-img">📷</div>
              {/if}
            </div>
            <div class="cart-item-details">
              <h4>{item.name}</h4>
              <span class="item-price">{formatPrice(item.sale_price || item.price)}</span>
              <div class="quantity-controls">
                <button on:click={() => updateQuantity(item.id, -1)}>−</button>
                <span>{item.quantity}</span>
                <button on:click={() => updateQuantity(item.id, 1)}>+</button>
              </div>
            </div>
            <button class="remove-item" on:click={() => removeFromCart(item.id)}>🗑️</button>
          </div>
        {/each}
      </div>
      
      <div class="cart-footer">
        <div class="cart-total">
          <span>Total:</span>
          <span class="total-amount">{formatPrice(cartTotal)}</span>
        </div>
        <div class="cart-actions">
          <button class="clear-cart-btn" on:click={clearCart}>Clear</button>
          <button class="checkout-btn" on:click={checkout}>Checkout</button>
        </div>
      </div>
    {/if}
  </div>
{/if}

<!-- Product Detail Modal -->
{#if showProductModal && selectedProduct}
  <div class="modal-overlay" on:click={() => showProductModal = false} on:keypress={() => {}} role="button" tabindex="-1" transition:fade></div>
  <div class="product-modal" transition:scale>
    <button class="modal-close" on:click={() => showProductModal = false}>✕</button>
    
    <div class="modal-content">
      <div class="modal-image">
        {#if selectedProduct.images[0]}
          <img src={selectedProduct.images[0]} alt={selectedProduct.name} />
        {:else}
          <div class="no-image-modal">📷</div>
        {/if}
      </div>
      
      <div class="modal-details">
        <span class="modal-category">{selectedProduct.category}</span>
        <h2>{selectedProduct.name}</h2>
        
        {#if selectedProduct.rating}
          <div class="modal-rating">
            <span class="stars">{'⭐'.repeat(Math.floor(selectedProduct.rating))}</span>
            <span>{selectedProduct.rating}/5 ({selectedProduct.reviews || 0} reviews)</span>
          </div>
        {/if}
        
        <p class="modal-description">{selectedProduct.description || 'No description available.'}</p>
        
        <div class="modal-price">
          <span class="current">{formatPrice(selectedProduct.sale_price || selectedProduct.price)}</span>
          {#if selectedProduct.sale_price}
            <span class="original">{formatPrice(selectedProduct.price)}</span>
            <span class="discount-badge">
              -{Math.round((1 - selectedProduct.sale_price / selectedProduct.price) * 100)}%
            </span>
          {/if}
        </div>
        
        <div class="modal-stock">
          {#if selectedProduct.stock > 0}
            <span class="in-stock">✓ In Stock ({selectedProduct.stock} available)</span>
          {:else}
            <span class="out-stock">✕ Out of Stock</span>
          {/if}
        </div>
        
        <div class="modal-actions">
          <button 
            class="add-to-cart-btn"
            on:click={() => { addToCart(selectedProduct); showProductModal = false; }}
            disabled={selectedProduct.stock === 0}
          >
            🛒 Add to Cart
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .ecommerce-page {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
    min-height: 100vh;
  }

  /* Header */
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
  }

  .header-left {
    display: flex;
    align-items: baseline;
    gap: 12px;
  }

  .header h1 {
    font-size: 2rem;
    margin: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .product-count {
    color: #666;
    font-size: 0.9rem;
  }

  .cart-btn {
    position: relative;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.2s;
  }

  .cart-btn:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
  }

  .cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
  }

  /* Filter Bar */
  .filter-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    align-items: center;
  }

  .search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
  }

  .search-box input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
  }

  .search-box input:focus {
    outline: none;
    border-color: #667eea;
  }

  .search-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
  }

  .filter-toggle, .category-select, .sort-select {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    background: white;
    font-size: 0.9rem;
    cursor: pointer;
    transition: border-color 0.2s;
  }

  .filter-toggle:hover, .category-select:hover, .sort-select:hover {
    border-color: #667eea;
  }

  /* Filters Panel */
  .filters-panel {
    display: flex;
    gap: 20px;
    align-items: flex-end;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 20px;
  }

  .filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
  }

  .price-inputs {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .price-inputs input {
    width: 120px;
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.9rem;
  }

  .clear-filters {
    padding: 10px 20px;
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .clear-filters:hover {
    background: #ee5a5a;
  }

  /* Stats Row */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
  }

  .stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  }

  .stat-icon {
    font-size: 1.8rem;
  }

  .stat-content {
    display: flex;
    flex-direction: column;
  }

  .stat-content .label {
    color: #666;
    font-size: 0.85rem;
  }

  .stat-content .value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
  }

  /* Loading & Error States */
  .loading-state, .error-state, .empty-state {
    text-align: center;
    padding: 60px 20px;
  }

  .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .error-icon, .empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
  }

  .retry-btn {
    padding: 12px 24px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
  }

  /* Product Grid */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 24px;
  }

  .product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
  }

  .product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
  }

  .image-container {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    overflow: hidden;
  }

  .image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
  }

  .product-card:hover .image-container img {
    transform: scale(1.05);
  }

  .no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #aaa;
  }

  .sale-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
  }

  .out-of-stock {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
  }

  .quick-actions {
    position: absolute;
    bottom: 12px;
    right: 12px;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .product-card:hover .quick-actions {
    opacity: 1;
  }

  .quick-add {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    cursor: pointer;
    font-size: 1.1rem;
    transition: transform 0.2s, background 0.2s;
  }

  .quick-add:hover:not(:disabled) {
    transform: scale(1.1);
    background: #667eea;
  }

  .quick-add:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .product-info {
    padding: 16px;
  }

  .category-tag {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-bottom: 8px;
  }

  .product-name {
    margin: 0 0 8px;
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
  }

  .stars {
    font-size: 0.9rem;
  }

  .review-count {
    font-size: 0.8rem;
    color: #888;
  }

  .price-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    flex-wrap: wrap;
  }

  .current-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: #ff4757;
  }

  .original-price {
    text-decoration: line-through;
    color: #aaa;
    font-size: 0.9rem;
  }

  .discount {
    background: #ffeaa7;
    color: #d63031;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
  }

  .stock-info {
    font-size: 0.85rem;
  }

  .stock-status {
    color: #27ae60;
  }

  .stock-status.out {
    color: #e74c3c;
  }

  .stock-info.low .stock-status {
    color: #f39c12;
  }

  /* Cart Overlay & Sidebar */
  .cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
  }

  .cart-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    max-width: 90vw;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 20px rgba(0,0,0,0.15);
    z-index: 1001;
    display: flex;
    flex-direction: column;
  }

  .cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 2px solid #eee;
  }

  .cart-header h2 {
    margin: 0;
    font-size: 1.3rem;
  }

  .close-cart {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s;
  }

  .close-cart:hover {
    background: #e0e0e0;
  }

  .cart-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #888;
  }

  .empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    opacity: 0.5;
  }

  .cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
  }

  .cart-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 12px;
  }

  .cart-item-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    background: #e0e0e0;
  }

  .cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .no-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .cart-item-details {
    flex: 1;
  }

  .cart-item-details h4 {
    margin: 0 0 4px;
    font-size: 0.95rem;
  }

  .item-price {
    color: #ff4757;
    font-weight: 600;
  }

  .quantity-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
  }

  .quantity-controls button {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 2px solid #e0e0e0;
    background: white;
    cursor: pointer;
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  .quantity-controls button:hover {
    border-color: #667eea;
  }

  .remove-item {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    opacity: 0.6;
    transition: opacity 0.2s;
  }

  .remove-item:hover {
    opacity: 1;
  }

  .cart-footer {
    padding: 20px;
    border-top: 2px solid #eee;
    background: #f8f9fa;
  }

  .cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 1.1rem;
  }

  .total-amount {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ff4757;
  }

  .cart-actions {
    display: flex;
    gap: 12px;
  }

  .clear-cart-btn {
    flex: 1;
    padding: 14px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    transition: border-color 0.2s;
  }

  .clear-cart-btn:hover {
    border-color: #ff6b6b;
    color: #ff6b6b;
  }

  .checkout-btn {
    flex: 2;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .product-modal {
    background: white;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  }

  .modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    font-size: 1.2rem;
    z-index: 10;
    transition: background 0.2s;
  }

  .modal-close:hover {
    background: #e0e0e0;
  }

  .modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  @media (max-width: 600px) {
    .modal-content {
      grid-template-columns: 1fr;
    }
  }

  .modal-image {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 300px;
  }

  .modal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .no-image-modal {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: #aaa;
  }

  .modal-details {
    padding: 32px;
  }

  .modal-category {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 12px;
  }

  .modal-details h2 {
    margin: 0 0 12px;
    font-size: 1.8rem;
    color: #333;
  }

  .modal-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    color: #666;
  }

  .modal-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
  }

  .modal-price {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }

  .modal-price .current {
    font-size: 2rem;
    font-weight: bold;
    color: #ff4757;
  }

  .modal-price .original {
    text-decoration: line-through;
    color: #aaa;
    font-size: 1.2rem;
  }

  .discount-badge {
    background: #ffeaa7;
    color: #d63031;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: bold;
  }

  .modal-stock {
    margin-bottom: 24px;
  }

  .in-stock {
    color: #27ae60;
    font-weight: 500;
  }

  .out-stock {
    color: #e74c3c;
    font-weight: 500;
  }

  .add-to-cart-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
  }

  .add-to-cart-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  /* Responsive adjustments */
  @media (max-width: 600px) {
    .ecommerce-page {
      padding: 12px;
    }

    .header h1 {
      font-size: 1.5rem;
    }

    .filter-bar {
      flex-direction: column;
    }

    .search-box {
      width: 100%;
    }

    .stats-row {
      grid-template-columns: repeat(2, 1fr);
    }

    .product-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
  }
</style>
