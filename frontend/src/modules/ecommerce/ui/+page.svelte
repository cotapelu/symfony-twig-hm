<script lang="ts">
  import { ecommerceStore, loadProducts, addToCart, removeFromCart, updateQuantity } from '../state/ecommerceStore';
  import { onMount } from 'svelte';
  import type { Product, CartItem } from '../contracts/ecommerceTypes';

  let activeTab = 'products';
  let searchQuery = '';
  let selectedCategory = 'all';
  let showCart = false;
  let selectedProduct: Product | null = null;

  onMount(() => {
    loadProducts();
  });

  $: products = $ecommerceStore.products;
  $: cart = $ecommerceStore.cart;
  $: orders = $ecommerceStore.orders;
  $: loading = $ecommerceStore.loading;

  $: filteredProducts = products.filter((p: Product) => {
    const matchesSearch = searchQuery === '' || 
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.description.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || p.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  $: categories = [...new Set(products.map((p: Product) => p.category))];
  $: cartTotal = cart.reduce((sum: number, item: CartItem) => sum + (item.price * item.quantity), 0);
  $: cartItemCount = cart.reduce((sum: number, item: CartItem) => sum + item.quantity, 0);

  function formatPrice(price: number, currency: string = 'USD') {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency
    }).format(price);
  }

  function getStarRating(rating: number) {
    return '★'.repeat(Math.floor(rating)) + '☆'.repeat(5 - Math.floor(rating));
  }
</script>

<div class="ecommerce-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Shop</h1>
        <button 
          class="relative p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          onclick={() => showCart = true}
        >
          🛒 Cart
          {#if cartItemCount > 0}
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
              {cartItemCount}
            </span>
          {/if}
        </button>
      </div>

      <div class="flex gap-4 mb-6 border-b">
        <button 
          class="px-4 py-2 font-medium {activeTab === 'products' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'products'}
        >
          Products
        </button>
        <button 
          class="px-4 py-2 font-medium {activeTab === 'orders' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'orders'}
        >
          My Orders
        </button>
      </div>

      {#if loading}
        <div class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else if activeTab === 'products'}
        <div class="flex flex-wrap gap-4 mb-6">
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Search products..."
            class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <select 
            bind:value={selectedCategory}
            class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Categories</option>
            {#each categories as category}
              <option value={category}>{category}</option>
            {/each}
          </select>
        </div>

        {#if filteredProducts.length === 0}
          <div class="text-center py-12 text-gray-500">
            <div class="text-6xl mb-4">📦</div>
            <p>No products found</p>
          </div>
        {:else}
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {#each filteredProducts as product (product.id)}
              <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="aspect-square bg-gray-100 relative">
                  <img 
                    src={product.image || '/placeholder-product.png'} 
                    alt={product.name}
                    class="w-full h-full object-cover"
                  />
                  {#if product.discount}
                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                      -{product.discount}%
                    </span>
                  {/if}
                </div>
                <div class="p-4">
                  <p class="text-xs text-gray-500 uppercase tracking-wide">{product.category}</p>
                  <h3 class="font-semibold text-gray-900 mt-1">{product.name}</h3>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">{product.description}</p>
                  <div class="flex items-center gap-1 mt-2">
                    <span class="text-yellow-500">{getStarRating(product.rating || 0)}</span>
                    <span class="text-sm text-gray-500">({product.reviews || 0})</span>
                  </div>
                  <div class="flex items-center justify-between mt-4">
                    <div>
                      {#if product.discount}
                        <span class="text-lg font-bold text-red-600">
                          {formatPrice(product.price * (1 - product.discount / 100))}
                        </span>
                        <span class="text-sm text-gray-400 line-through ml-2">
                          {formatPrice(product.price)}
                        </span>
                      {:else}
                        <span class="text-lg font-bold">{formatPrice(product.price)}</span>
                      {/if}
                    </div>
                    <button 
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                      onclick={() => addToCart(product)}
                    >
                      Add to Cart
                    </button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}

      {:else if activeTab === 'orders'}
        <div class="space-y-4">
          {#if orders.length === 0}
            <div class="text-center py-12 text-gray-500">
              <div class="text-6xl mb-4">📋</div>
              <p>No orders yet</p>
            </div>
          {:else}
            {#each orders as order}
              <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <p class="font-semibold">Order #{order.id}</p>
                    <p class="text-sm text-gray-500">{new Date(order.date).toLocaleDateString()}</p>
                  </div>
                  <span class="px-3 py-1 rounded-full text-sm {order.status === 'delivered' ? 'bg-green-100 text-green-800' : order.status === 'shipped' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'}">
                    {order.status}
                  </span>
                </div>
                <div class="space-y-2">
                  {#each order.items as item}
                    <div class="flex items-center gap-4 py-2">
                      <img src={item.image} alt={item.name} class="w-12 h-12 rounded object-cover" />
                      <div class="flex-1">
                        <p class="font-medium">{item.name}</p>
                        <p class="text-sm text-gray-500">Qty: {item.quantity}</p>
                      </div>
                      <p class="font-medium">{formatPrice(item.price * item.quantity)}</p>
                    </div>
                  {/each}
                </div>
                <div class="border-t mt-4 pt-4 flex items-center justify-between">
                  <p class="text-gray-600">Total</p>
                  <p class="text-xl font-bold">{formatPrice(order.total)}</p>
                </div>
              </div>
            {/each}
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>

{#if showCart}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg w-full max-w-lg max-h-[90vh] overflow-hidden">
      <div class="p-4 border-b flex items-center justify-between">
        <h2 class="text-xl font-bold">Shopping Cart ({cartItemCount})</h2>
        <button onclick={() => showCart = false} class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      
      <div class="overflow-y-auto max-h-96 p-4">
        {#if cart.length === 0}
          <div class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">🛒</div>
            <p>Your cart is empty</p>
          </div>
        {:else}
          <div class="space-y-4">
            {#each cart as item (item.id)}
              <div class="flex items-center gap-4 py-2">
                <img src={item.image} alt={item.name} class="w-16 h-16 rounded object-cover" />
                <div class="flex-1">
                  <p class="font-medium">{item.name}</p>
                  <p class="text-sm text-gray-500">{formatPrice(item.price)}</p>
                </div>
                <div class="flex items-center gap-2">
                  <button 
                    class="w-8 h-8 rounded bg-gray-100 hover:bg-gray-200"
                    onclick={() => updateQuantity(item.id, item.quantity - 1)}
                  >
                    -
                  </button>
                  <span class="w-8 text-center">{item.quantity}</span>
                  <button 
                    class="w-8 h-8 rounded bg-gray-100 hover:bg-gray-200"
                    onclick={() => updateQuantity(item.id, item.quantity + 1)}
                  >
                    +
                  </button>
                </div>
                <button 
                  class="text-red-500 hover:text-red-700"
                  onclick={() => removeFromCart(item.id)}
                >
                  🗑️
                </button>
              </div>
            {/each}
          </div>
        {/if}
      </div>

      {#if cart.length > 0}
        <div class="border-t p-4">
          <div class="flex items-center justify-between mb-4">
            <span class="text-lg">Total</span>
            <span class="text-2xl font-bold">{formatPrice(cartTotal)}</span>
          </div>
          <button 
            class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
            onclick={() => { /* checkout */ }}
          >
            Checkout
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .ecommerce-page {
    min-height: 100vh;
    background: #f9fafb;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
