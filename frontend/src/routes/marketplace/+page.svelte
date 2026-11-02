<script lang="ts">
  import { onMount } from 'svelte';
  import { fly, scale } from 'svelte/transition';

  // Types
  interface Product {
    id: string;
    name: string;
    description: string;
    price: number;
    salePrice?: number;
    category: string;
    subcategory: string;
    images: string[];
    stock: number;
    rating: number;
    reviews: number;
    seller: string;
    sellerRating: number;
    tags: string[];
    createdAt: string;
  }

  interface CartItem {
    product: Product;
    quantity: number;
  }

  interface WishlistItem {
    id: string;
    product: Product;
    savedAt: string;
  }

  // Sample data
  const categories = [
    { id: 'all', name: 'All Products', icon: '🛍️' },
    { id: 'electronics', name: 'Electronics', icon: '📱' },
    { id: 'fashion', name: 'Fashion', icon: '👗' },
    { id: 'home', name: 'Home & Living', icon: '🏠' },
    { id: 'food', name: 'Food & Drinks', icon: '🍎' },
    { id: 'beauty', name: 'Beauty', icon: '💄' },
    { id: 'sports', name: 'Sports', icon: '⚽' },
    { id: 'books', name: 'Books', icon: '📚' }
  ];

  const products: Product[] = [
    {
      id: '1',
      name: 'Premium Wireless Headphones',
      description: 'High-quality Bluetooth headphones with noise cancellation and 30-hour battery life. Perfect for music lovers.',
      price: 2990000,
      salePrice: 2490000,
      category: 'electronics',
      subcategory: 'Audio',
      images: ['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400'],
      stock: 45,
      rating: 4.8,
      reviews: 234,
      seller: 'TechStore VN',
      sellerRating: 4.9,
      tags: ['wireless', 'bluetooth', 'noise-cancelling'],
      createdAt: '2024-01-15'
    },
    {
      id: '2',
      name: 'Organic Vietnamese Coffee',
      description: 'Premium Robusta beans from the Central Highlands. Rich, aromatic, and sustainably sourced.',
      price: 450000,
      category: 'food',
      subcategory: 'Beverages',
      images: ['https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400'],
      stock: 120,
      rating: 4.6,
      reviews: 89,
      seller: 'Highland Coffee',
      sellerRating: 4.7,
      tags: ['organic', 'vietnamese', 'coffee'],
      createdAt: '2024-02-01'
    },
    {
      id: '3',
      name: 'Designer Silk Scarf',
      description: 'Handcrafted silk scarf with traditional Vietnamese patterns. Elegant and timeless.',
      price: 1800000,
      category: 'fashion',
      subcategory: 'Accessories',
      images: ['https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=400'],
      stock: 28,
      rating: 4.9,
      reviews: 56,
      seller: 'Hanoi Silk House',
      sellerRating: 4.8,
      tags: ['silk', 'handmade', 'vietnamese'],
      createdAt: '2024-01-20'
    },
    {
      id: '4',
      name: 'Smart Watch Pro',
      description: 'Advanced fitness tracking, heart rate monitoring, and smartphone notifications. Water-resistant.',
      price: 5500000,
      salePrice: 4990000,
      category: 'electronics',
      subcategory: 'Wearables',
      images: ['https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400'],
      stock: 67,
      rating: 4.7,
      reviews: 312,
      seller: 'TechStore VN',
      sellerRating: 4.9,
      tags: ['smartwatch', 'fitness', 'water-resistant'],
      createdAt: '2024-02-10'
    },
    {
      id: '5',
      name: 'Natural Skincare Set',
      description: 'Complete skincare routine with organic ingredients. Suitable for all skin types.',
      price: 890000,
      category: 'beauty',
      subcategory: 'Skincare',
      images: ['https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400'],
      stock: 83,
      rating: 4.5,
      reviews: 178,
      seller: 'Natural Beauty',
      sellerRating: 4.6,
      tags: ['organic', 'skincare', 'natural'],
      createdAt: '2024-01-25'
    },
    {
      id: '6',
      name: 'Bamboo Furniture Set',
      description: 'Eco-friendly bamboo dining table and chairs. Modern design with natural finish.',
      price: 8500000,
      category: 'home',
      subcategory: 'Furniture',
      images: ['https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400'],
      stock: 12,
      rating: 4.4,
      reviews: 45,
      seller: 'Green Home',
      sellerRating: 4.5,
      tags: ['bamboo', 'eco-friendly', 'modern'],
      createdAt: '2024-02-05'
    },
    {
      id: '7',
      name: 'Running Shoes Elite',
      description: 'Professional running shoes with advanced cushioning and breathable mesh upper.',
      price: 3200000,
      salePrice: 2790000,
      category: 'sports',
      subcategory: 'Footwear',
      images: ['https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'],
      stock: 94,
      rating: 4.8,
      reviews: 267,
      seller: 'Sports World',
      sellerRating: 4.8,
      tags: ['running', 'professional', 'comfort'],
      createdAt: '2024-01-30'
    },
    {
      id: '8',
      name: 'Vietnamese Cookbook',
      description: 'Authentic recipes from renowned Vietnamese chefs. Includes photos and step-by-step instructions.',
      price: 350000,
      category: 'books',
      subcategory: 'Cookbooks',
      images: ['https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=400'],
      stock: 156,
      rating: 4.7,
      reviews: 89,
      seller: 'Book Corner',
      sellerRating: 4.9,
      tags: ['cooking', 'vietnamese', 'recipes'],
      createdAt: '2024-02-08'
    },
    {
      id: '9',
      name: 'Portable Bluetooth Speaker',
      description: 'Waterproof speaker with 360° sound. Perfect for outdoor adventures.',
      price: 1200000,
      category: 'electronics',
      subcategory: 'Audio',
      images: ['https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400'],
      stock: 78,
      rating: 4.6,
      reviews: 198,
      seller: 'TechStore VN',
      sellerRating: 4.9,
      tags: ['bluetooth', 'portable', 'waterproof'],
      createdAt: '2024-02-12'
    },
    {
      id: '10',
      name: 'Handwoven Basket Set',
      description: 'Set of 3 handwoven storage baskets. Traditional craftsmanship meets modern design.',
      price: 650000,
      category: 'home',
      subcategory: 'Decor',
      images: ['https://images.unsplash.com/photo-1519710164239-da123dc03ef4?w=400'],
      stock: 34,
      rating: 4.5,
      reviews: 67,
      seller: 'Artisan Craft',
      sellerRating: 4.7,
      tags: ['handwoven', 'storage', 'traditional'],
      createdAt: '2024-01-18'
    },
    {
      id: '11',
      name: 'Yoga Mat Premium',
      description: 'Extra thick, non-slip yoga mat with alignment lines. Eco-friendly materials.',
      price: 580000,
      category: 'sports',
      subcategory: 'Fitness',
      images: ['https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=400'],
      stock: 112,
      rating: 4.7,
      reviews: 145,
      seller: 'Sports World',
      sellerRating: 4.8,
      tags: ['yoga', 'fitness', 'eco-friendly'],
      createdAt: '2024-02-03'
    },
    {
      id: '12',
      name: 'Traditional Ao Dai',
      description: 'Hand-embroidered authentic Vietnamese Ao Dai. Perfect for special occasions.',
      price: 2500000,
      category: 'fashion',
      subcategory: 'Traditional',
      images: ['https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400'],
      stock: 19,
      rating: 4.9,
      reviews: 78,
      seller: 'Vietnam Heritage',
      sellerRating: 4.9,
      tags: ['aodai', 'traditional', 'handmade'],
      createdAt: '2024-01-22'
    }
  ];

  // State
  let searchQuery = '';
  let selectedCategory = 'all';
  let priceRange = [0, 10000000];
  let minRating = 0;
  let sortBy = 'newest';
  let viewMode: 'grid' | 'list' = 'grid';
  
  // Cart & Wishlist
  let cart: CartItem[] = [];
  let wishlist: WishlistItem[] = [];
  let showCart = false;
  let showProductDetail = false;
  let selectedProduct: Product | null = null;
  let showCheckout = false;

  // Load from localStorage
  onMount(() => {
    const savedCart = localStorage.getItem('marketplace_cart');
    const savedWishlist = localStorage.getItem('marketplace_wishlist');
    if (savedCart) cart = JSON.parse(savedCart);
    if (savedWishlist) wishlist = JSON.parse(savedWishlist);
  });

  // Reactive filtered products
  $: filteredProducts = products
    .filter(p => {
      const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        p.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
        p.tags.some(t => t.toLowerCase().includes(searchQuery.toLowerCase()));
      const matchesCategory = selectedCategory === 'all' || p.category === selectedCategory;
      const matchesPrice = (p.salePrice || p.price) >= priceRange[0] && (p.salePrice || p.price) <= priceRange[1];
      const matchesRating = p.rating >= minRating;
      return matchesSearch && matchesCategory && matchesPrice && matchesRating;
    })
    .sort((a, b) => {
      switch (sortBy) {
        case 'price-low': return (a.salePrice || a.price) - (b.salePrice || b.price);
        case 'price-high': return (b.salePrice || b.price) - (a.salePrice || a.price);
        case 'rating': return b.rating - a.rating;
        case 'newest': return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
        default: return 0;
      }
    });

  // Functions
  function addToCart(product: Product) {
    const existing = cart.find(c => c.product.id === product.id);
    if (existing) {
      if (existing.quantity < product.stock) {
        existing.quantity++;
        cart = [...cart];
      }
    } else {
      cart = [...cart, { product, quantity: 1 }];
    }
    saveCart();
  }

  function removeFromCart(productId: string) {
    cart = cart.filter(c => c.product.id !== productId);
    saveCart();
  }

  function updateQuantity(productId: string, qty: number) {
    const item = cart.find(c => c.product.id === productId);
    if (item && qty > 0 && qty <= item.product.stock) {
      item.quantity = qty;
      cart = [...cart];
      saveCart();
    }
  }

  function saveCart() {
    localStorage.setItem('marketplace_cart', JSON.stringify(cart));
  }

  function toggleWishlist(product: Product) {
    const existing = wishlist.find(w => w.product.id === product.id);
    if (existing) {
      wishlist = wishlist.filter(w => w.product.id !== product.id);
    } else {
      wishlist = [...wishlist, { id: Date.now().toString(), product, savedAt: new Date().toISOString() }];
    }
    localStorage.setItem('marketplace_wishlist', JSON.stringify(wishlist));
  }

  function isInWishlist(productId: string): boolean {
    return wishlist.some(w => w.product.id === productId);
  }

  function isInCart(productId: string): boolean {
    return cart.some(c => c.product.id === productId);
  }

  function getCartTotal(): number {
    return cart.reduce((sum, item) => sum + (item.product.salePrice || item.product.price) * item.quantity, 0);
  }

  function getCartCount(): number {
    return cart.reduce((sum, item) => sum + item.quantity, 0);
  }

  function getDiscountPercent(price: number, salePrice: number): number {
    return Math.round((1 - salePrice / price) * 100);
  }

  function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
  }

  function openProductDetail(product: Product) {
    selectedProduct = product;
    showProductDetail = true;
  }

  function closeProductDetail() {
    showProductDetail = false;
    selectedProduct = null;
  }
</script>

<svelte:head>
  <title>🛒 Marketplace - Shop Local Products</title>
</svelte:head>

<div class="marketplace-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <button class="back-btn" onclick={() => history.back()}>←</button>
        <h1>🛒 Marketplace</h1>
      </div>
      
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input 
          type="text" 
          placeholder="Search products..." 
          bind:value={searchQuery}
        />
        {#if searchQuery}
          <button class="clear-search" onclick={() => searchQuery = ''}>✕</button>
        {/if}
      </div>
      
      <div class="header-actions">
        <button class="action-btn wishlist-btn" onclick={() => window.location.href = '/wishlist'}>
          ❤️ <span class="badge">{wishlist.length}</span>
        </button>
        <button class="action-btn cart-btn" onclick={() => showCart = !showCart}>
          🛒 <span class="badge">{getCartCount()}</span>
        </button>
      </div>
    </div>
  </header>

  <main class="main-content">
    <!-- Categories -->
    <aside class="categories-sidebar">
      <h3>Categories</h3>
      <div class="category-list">
        {#each categories as category}
          <button 
            class="category-btn"
            class:active={selectedCategory === category.id}
            onclick={() => selectedCategory = category.id}
          >
            <span class="cat-icon">{category.icon}</span>
            <span class="cat-name">{category.name}</span>
          </button>
        {/each}
      </div>

      <div class="filters-section">
        <h3>Filters</h3>
        
        <div class="filter-group">
          <label>Price Range</label>
          <div class="price-inputs">
            <input 
              type="number" 
              placeholder="Min" 
              bind:value={priceRange[0]}
            />
            <span>-</span>
            <input 
              type="number" 
              placeholder="Max" 
              bind:value={priceRange[1]}
            />
          </div>
        </div>

        <div class="filter-group">
          <label>Minimum Rating</label>
          <div class="rating-filter">
            {#each [0, 3, 3.5, 4, 4.5] as rating}
              <button 
                class="rating-btn"
                class:active={minRating === rating}
                onclick={() => minRating = rating}
              >
                {rating === 0 ? 'All' : `⭐ ${rating}+`}
              </button>
            {/each}
          </div>
        </div>
      </div>
    </aside>

    <!-- Products Area -->
    <section class="products-area">
      <!-- Toolbar -->
      <div class="toolbar">
        <div class="results-info">
          <span class="results-count">{filteredProducts.length} products</span>
          {#if selectedCategory !== 'all'}
            <span class="current-filter">
              in {categories.find(c => c.id === selectedCategory)?.name}
            </span>
          {/if}
        </div>
        
        <div class="toolbar-actions">
          <select bind:value={sortBy} class="sort-select">
            <option value="newest">Newest First</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
            <option value="rating">Highest Rated</option>
          </select>
          
          <div class="view-toggle">
            <button 
              class="view-btn"
              class:active={viewMode === 'grid'}
              onclick={() => viewMode = 'grid'}
            >▦</button>
            <button 
              class="view-btn"
              class:active={viewMode === 'list'}
              onclick={() => viewMode = 'list'}
            >☰</button>
          </div>
        </div>
      </div>

      <!-- Products Grid/List -->
      {#if filteredProducts.length === 0}
        <div class="empty-state">
          <div class="empty-icon">🔍</div>
          <h3>No products found</h3>
          <p>Try adjusting your filters or search terms</p>
          <button class="reset-btn" onclick={() => {
            searchQuery = '';
            selectedCategory = 'all';
            priceRange = [0, 10000000];
            minRating = 0;
          }}>Reset Filters</button>
        </div>
      {:else}
        <div class="products-grid" class:list-view={viewMode === 'list'}>
          {#each filteredProducts as product (product.id)}
            <div class="product-card" in:fly={{ y: 20, duration: 300 }}>
              <div class="card-image" onclick={() => openProductDetail(product)}>
                <img src={product.images[0]} alt={product.name} />
                {#if product.salePrice}
                  <span class="sale-badge">-{getDiscountPercent(product.price, product.salePrice)}%</span>
                {/if}
                <button 
                  class="wishlist-toggle"
                  class:active={isInWishlist(product.id)}
                  onclick={(e) => { e.stopPropagation(); toggleWishlist(product); }}
                >
                  {isInWishlist(product.id) ? '❤️' : '🤍'}
                </button>
              </div>
              
              <div class="card-content">
                <div class="category-tag">{categories.find(c => c.id === product.category)?.icon} {product.category}</div>
                
                <h3 class="product-name" onclick={() => openProductDetail(product)}>
                  {product.name}
                </h3>
                
                <div class="seller-info">
                  <span class="seller-name">{product.seller}</span>
                  <span class="seller-rating">★ {product.sellerRating}</span>
                </div>
                
                <div class="product-rating">
                  <span class="stars">{'★'.repeat(Math.floor(product.rating))}{product.rating % 1 >= 0.5 ? '½' : ''}</span>
                  <span class="rating-value">{product.rating}</span>
                  <span class="reviews">({product.reviews})</span>
                </div>
                
                <div class="price-section">
                  <span class="current-price">
                    {formatPrice(product.salePrice || product.price)}
                  </span>
                  {#if product.salePrice}
                    <span class="original-price">{formatPrice(product.price)}</span>
                  {/if}
                </div>
                
                <div class="stock-info" class:low={product.stock < 20}>
                  {#if product.stock > 0}
                    {product.stock < 20 ? `Only ${product.stock} left!` : 'In stock'}
                  {:else}
                    Out of stock
                  {/if}
                </div>
                
                <div class="card-actions">
                  {#if isInCart(product.id)}
                    <button class="btn-added" disabled>✓ Added to Cart</button>
                  {:else}
                    <button 
                      class="btn-add"
                      disabled={product.stock === 0}
                      onclick={() => addToCart(product)}
                    >
                      Add to Cart
                    </button>
                  {/if}
                </div>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </section>
  </main>

  <!-- Cart Sidebar -->
  {#if showCart}
    <div class="cart-overlay" onclick={() => showCart = false}></div>
    <aside class="cart-sidebar" in:fly={{ x: 300, duration: 300 }}>
      <div class="cart-header">
        <h2>🛒 Your Cart</h2>
        <button class="close-btn" onclick={() => showCart = false}>✕</button>
      </div>
      
      {#if cart.length === 0}
        <div class="cart-empty">
          <div class="empty-icon">🛒</div>
          <p>Your cart is empty</p>
          <button class="shop-btn" onclick={() => showCart = false}>Start Shopping</button>
        </div>
      {:else}
        <div class="cart-items">
          {#each cart as item (item.product.id)}
            <div class="cart-item">
              <img src={item.product.images[0]} alt={item.product.name} />
              <div class="item-details">
                <h4>{item.product.name}</h4>
                <div class="item-price">{formatPrice(item.product.salePrice || item.product.price)}</div>
                <div class="quantity-controls">
                  <button onclick={() => updateQuantity(item.product.id, item.quantity - 1)}>−</button>
                  <span>{item.quantity}</span>
                  <button onclick={() => updateQuantity(item.product.id, item.quantity + 1)}>+</button>
                </div>
              </div>
              <button class="remove-btn" onclick={() => removeFromCart(item.product.id)}>🗑️</button>
            </div>
          {/each}
        </div>
        
        <div class="cart-footer">
          <div class="cart-total">
            <span>Total:</span>
            <span class="total-price">{formatPrice(getCartTotal())}</span>
          </div>
          <button class="checkout-btn" onclick={() => showCheckout = true}>
            Proceed to Checkout
          </button>
        </div>
      {/if}
    </aside>
  {/if}

  <!-- Product Detail Modal -->
  {#if showProductDetail && selectedProduct}
    <div class="modal-overlay" onclick={closeProductDetail}>
      <div class="product-modal" onclick={(e) => e.stopPropagation()} in:scale={{ duration: 300 }}>
        <button class="modal-close" onclick={closeProductDetail}>✕</button>
        
        <div class="modal-content">
          <div class="modal-image">
            <img src={selectedProduct.images[0]} alt={selectedProduct.name} />
            {#if selectedProduct.salePrice}
              <span class="modal-sale-badge">-{getDiscountPercent(selectedProduct.price, selectedProduct.salePrice)}% OFF</span>
            {/if}
          </div>
          
          <div class="modal-details">
            <div class="modal-category">
              {categories.find(c => c.id === selectedProduct.category)?.icon} {selectedProduct.category}
            </div>
            
            <h2>{selectedProduct.name}</h2>
            
            <div class="modal-rating">
              <span class="stars">{'★'.repeat(Math.floor(selectedProduct.rating))}</span>
              <span>{selectedProduct.rating}</span>
              <span class="reviews">({selectedProduct.reviews} reviews)</span>
            </div>
            
            <div class="modal-seller">
              Sold by <strong>{selectedProduct.seller}</strong>
              <span class="seller-badge">★ {selectedProduct.sellerRating}</span>
            </div>
            
            <p class="modal-description">{selectedProduct.description}</p>
            
            <div class="modal-price">
              <span class="sale-price">{formatPrice(selectedProduct.salePrice || selectedProduct.price)}</span>
              {#if selectedProduct.salePrice}
                <span class="original-price">{formatPrice(selectedProduct.price)}</span>
              {/if}
            </div>
            
            <div class="modal-stock">
              {#if selectedProduct.stock > 0}
                ✓ In Stock ({selectedProduct.stock} available)
              {:else}
                ✕ Out of Stock
              {/if}
            </div>
            
            <div class="modal-tags">
              {#each selectedProduct.tags as tag}
                <span class="tag">#{tag}</span>
              {/each}
            </div>
            
            <div class="modal-actions">
              <button 
                class="btn-wishlist"
                onclick={() => toggleWishlist(selectedProduct!)}
              >
                {isInWishlist(selectedProduct.id) ? '❤️ In Wishlist' : '🤍 Add to Wishlist'}
              </button>
              <button 
                class="btn-cart"
                disabled={selectedProduct.stock === 0 || isInCart(selectedProduct.id)}
                onclick={() => { addToCart(selectedProduct!); }}
              >
                {isInCart(selectedProduct.id) ? '✓ Added to Cart' : '🛒 Add to Cart'}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- Checkout Modal -->
  {#if showCheckout}
    <div class="modal-overlay" onclick={() => showCheckout = false}>
      <div class="checkout-modal" onclick={(e) => e.stopPropagation()} in:scale={{ duration: 300 }}>
        <button class="modal-close" onclick={() => showCheckout = false}>✕</button>
        
        <h2>✅ Order Confirmed!</h2>
        <p>Thank you for your purchase!</p>
        
        <div class="order-summary">
          <h3>Order Summary</h3>
          {#each cart as item}
            <div class="summary-item">
              <span>{item.product.name} x{item.quantity}</span>
              <span>{formatPrice((item.product.salePrice || item.product.price) * item.quantity)}</span>
            </div>
          {/each}
          <div class="summary-total">
            <span>Total</span>
            <span>{formatPrice(getCartTotal())}</span>
          </div>
        </div>
        
        <button class="continue-btn" onclick={() => { showCheckout = false; cart = []; saveCart(); }}>
          Continue Shopping
        </button>
      </div>
    </div>
  {/if}
</div>

<style>
  :global(body) {
    margin: 0;
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #f8f9fc;
  }

  .marketplace-page {
    min-height: 100vh;
  }

  /* Header */
  .header {
    background: white;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .back-btn {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    color: #666;
  }

  .header h1 {
    font-size: 1.5rem;
    margin: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .search-bar {
    flex: 1;
    max-width: 500px;
    position: relative;
    display: flex;
    align-items: center;
  }

  .search-icon {
    position: absolute;
    left: 1rem;
    font-size: 1rem;
  }

  .search-bar input {
    width: 100%;
    padding: 0.875rem 2.5rem;
    border: 2px solid #e8e8e8;
    border-radius: 50px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
  }

  .search-bar input:focus {
    outline: none;
    border-color: #667eea;
  }

  .clear-search {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
    font-size: 0.875rem;
  }

  .header-actions {
    display: flex;
    gap: 0.75rem;
  }

  .action-btn {
    position: relative;
    background: #f8f9fc;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: transform 0.2s, background 0.2s;
  }

  .action-btn:hover {
    transform: scale(1.05);
    background: #e8eaf6;
  }

  .action-btn .badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-weight: 600;
  }

  /* Main Layout */
  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
  }

  /* Sidebar */
  .categories-sidebar {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 100px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
  }

  .categories-sidebar h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    color: #1a1a2e;
  }

  .category-list {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
  }

  .category-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: none;
    background: transparent;
    border-radius: 10px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
    font-size: 0.9rem;
    color: #555;
  }

  .category-btn:hover {
    background: #f0f4ff;
    color: #667eea;
  }

  .category-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .cat-icon {
    font-size: 1.1rem;
  }

  .filters-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
  }

  .filter-group {
    margin-bottom: 1.25rem;
  }

  .filter-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
  }

  .price-inputs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .price-inputs input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.85rem;
  }

  .price-inputs span {
    color: #999;
  }

  .rating-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
  }

  .rating-btn {
    padding: 0.375rem 0.625rem;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .rating-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  /* Products Area */
  .products-area {
    min-width: 0;
  }

  .toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .results-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .results-count {
    font-weight: 600;
    color: #1a1a2e;
  }

  .current-filter {
    color: #667eea;
    font-size: 0.9rem;
  }

  .toolbar-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .sort-select {
    padding: 0.625rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 0.9rem;
  }

  .view-toggle {
    display: flex;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
  }

  .view-btn {
    padding: 0.5rem 0.75rem;
    border: none;
    background: white;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s;
  }

  .view-btn.active {
    background: #667eea;
    color: white;
  }

  /* Products Grid */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.5rem;
  }

  .products-grid.list-view {
    grid-template-columns: 1fr;
  }

  .products-grid.list-view .product-card {
    display: grid;
    grid-template-columns: 180px 1fr auto;
  }

  .product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    transition: transform 0.3s, box-shadow 0.3s;
  }

  .product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
  }

  .card-image {
    position: relative;
    height: 200px;
    overflow: hidden;
    cursor: pointer;
  }

  .card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
  }

  .product-card:hover .card-image img {
    transform: scale(1.05);
  }

  .sale-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: #ff4757;
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
  }

  .wishlist-toggle {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 36px;
    height: 36px;
    background: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
  }

  .wishlist-toggle:hover {
    transform: scale(1.1);
  }

  .card-content {
    padding: 1rem;
  }

  .category-tag {
    font-size: 0.75rem;
    color: #667eea;
    text-transform: capitalize;
    margin-bottom: 0.375rem;
  }

  .product-name {
    font-size: 1rem;
    margin: 0 0 0.5rem;
    color: #1a1a2e;
    cursor: pointer;
    transition: color 0.2s;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .product-name:hover {
    color: #667eea;
  }

  .seller-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.375rem;
  }

  .seller-rating {
    color: #ffc107;
  }

  .product-rating {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
  }

  .stars {
    color: #ffc107;
  }

  .rating-value {
    font-weight: 600;
    color: #333;
  }

  .reviews {
    color: #999;
  }

  .price-section {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .current-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ff4757;
  }

  .original-price {
    font-size: 0.9rem;
    color: #999;
    text-decoration: line-through;
  }

  .stock-info {
    font-size: 0.8rem;
    color: #28a745;
    margin-bottom: 0.75rem;
  }

  .stock-info.low {
    color: #ff4757;
  }

  .card-actions {
    display: flex;
    gap: 0.5rem;
  }

  .btn-add, .btn-added {
    flex: 1;
    padding: 0.75rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
  }

  .btn-add {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .btn-add:hover:not(:disabled) {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .btn-add:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .btn-added {
    background: #28a745;
    color: white;
    cursor: default;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    color: #1a1a2e;
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: #666;
    margin-bottom: 1.5rem;
  }

  .reset-btn {
    padding: 0.75rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
  }

  /* Cart Sidebar */
  .cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 200;
  }

  .cart-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    max-width: 100%;
    height: 100vh;
    background: white;
    z-index: 201;
    display: flex;
    flex-direction: column;
    box-shadow: -4px 0 20px rgba(0,0,0,0.1);
  }

  .cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
  }

  .cart-header h2 {
    margin: 0;
    font-size: 1.25rem;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #666;
  }

  .cart-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }

  .cart-empty .empty-icon {
    font-size: 3rem;
  }

  .shop-btn {
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
  }

  .cart-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fc;
    border-radius: 12px;
    margin-bottom: 0.75rem;
  }

  .cart-item img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
  }

  .item-details {
    flex: 1;
    min-width: 0;
  }

  .item-details h4 {
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .item-price {
    font-weight: 600;
    color: #ff4757;
    font-size: 0.9rem;
  }

  .quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.5rem;
  }

  .quantity-controls button {
    width: 28px;
    height: 28px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
  }

  .quantity-controls span {
    font-weight: 600;
    min-width: 20px;
    text-align: center;
  }

  .remove-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
  }

  .cart-footer {
    padding: 1.5rem;
    border-top: 1px solid #eee;
  }

  .cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 1.1rem;
  }

  .total-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ff4757;
  }

  .checkout-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .checkout-btn:hover {
    transform: scale(1.02);
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 300;
    padding: 1rem;
  }

  .product-modal {
    background: white;
    border-radius: 20px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
  }

  .modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    background: #f0f0f0;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    z-index: 10;
  }

  .modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .modal-image {
    position: relative;
    height: 400px;
  }

  .modal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .modal-sale-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #ff4757;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 700;
  }

  .modal-details {
    padding: 2rem;
  }

  .modal-category {
    font-size: 0.9rem;
    color: #667eea;
    text-transform: capitalize;
    margin-bottom: 0.5rem;
  }

  .modal-details h2 {
    margin: 0 0 1rem;
    font-size: 1.5rem;
    color: #1a1a2e;
  }

  .modal-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .modal-rating .stars {
    font-size: 1.1rem;
  }

  .modal-seller {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 1rem;
  }

  .seller-badge {
    background: #ffc107;
    color: #333;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
  }

  .modal-description {
    color: #555;
    line-height: 1.6;
    margin-bottom: 1rem;
  }

  .modal-price {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .modal-price .sale-price {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ff4757;
  }

  .modal-price .original-price {
    font-size: 1.1rem;
  }

  .modal-stock {
    font-size: 0.9rem;
    color: #28a745;
    margin-bottom: 1rem;
  }

  .modal-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .tag {
    background: #f0f4ff;
    color: #667eea;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
  }

  .modal-actions {
    display: flex;
    gap: 1rem;
  }

  .btn-wishlist, .btn-cart {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-wishlist {
    background: #f0f0f0;
    color: #333;
  }

  .btn-wishlist:hover {
    background: #e0e0e0;
  }

  .btn-cart {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .btn-cart:hover:not(:disabled) {
    transform: scale(1.02);
  }

  .btn-cart:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  /* Checkout Modal */
  .checkout-modal {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    max-width: 500px;
    width: 100%;
    text-align: center;
    position: relative;
  }

  .checkout-modal h2 {
    margin: 0 0 0.5rem;
    color: #28a745;
  }

  .checkout-modal > p {
    color: #666;
    margin-bottom: 2rem;
  }

  .order-summary {
    background: #f8f9fc;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
  }

  .order-summary h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: #555;
  }

  .summary-total {
    display: flex;
    justify-content: space-between;
    padding-top: 1rem;
    margin-top: 0.5rem;
    border-top: 1px solid #ddd;
    font-weight: 700;
    font-size: 1.1rem;
  }

  .continue-btn {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
  }

  /* Responsive */
  @media (max-width: 1024px) {
    .main-content {
      grid-template-columns: 1fr;
    }
    
    .categories-sidebar {
      position: static;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .category-list {
      flex-direction: row;
      flex-wrap: wrap;
    }
    
    .filters-section {
      display: none;
    }
  }

  @media (max-width: 768px) {
    .header-content {
      flex-wrap: wrap;
    }
    
    .search-bar {
      order: 3;
      max-width: 100%;
      flex-basis: 100%;
    }
    
    .products-grid {
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 1rem;
    }
    
    .modal-content {
      grid-template-columns: 1fr;
    }
    
    .modal-image {
      height: 250px;
    }
    
    .cart-sidebar {
      width: 100%;
    }
  }
</style>
