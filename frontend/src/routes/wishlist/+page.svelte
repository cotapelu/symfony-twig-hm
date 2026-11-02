<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';

  interface WishlistItem {
    id: string;
    type: 'hotel' | 'flight' | 'car' | 'restaurant' | 'package' | 'event' | 'experience';
    name: string;
    location: string;
    image: string;
    price: number;
    rating?: number;
    savedAt: string;
    notes?: string;
  }

  let wishlist: WishlistItem[] = [];
  let loading = true;
  let activeTab: 'all' | 'hotels' | 'flights' | 'cars' | 'restaurants' | 'packages' | 'events' | 'experiences' = 'all';

  onMount(() => {
    loadWishlist();
  });

  function loadWishlist() {
    loading = true;
    try {
      const saved = localStorage.getItem('wishlist');
      if (saved) {
        wishlist = JSON.parse(saved);
      }
    } catch (err) {
      console.error('Failed to load wishlist:', err);
      wishlist = [];
    } finally {
      loading = false;
    }
  }

  function removeFromWishlist(id: string) {
    if (!confirm('Remove this item from wishlist?')) return;
    wishlist = wishlist.filter(item => item.id !== id);
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
  }

  function getFilteredItems(): WishlistItem[] {
    if (activeTab === 'all') return wishlist;
    return wishlist.filter(item => item.type === activeTab.slice(0, -1));
  }

  function getTypeIcon(type: string): string {
    const icons: Record<string, string> = {
      hotel: '🏨',
      flight: '✈️',
      car: '🚗',
      restaurant: '🍽️',
      package: '📦',
      event: '🎫',
      experience: '🎯'
    };
    return icons[type] || '❤️';
  }

  function getTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      hotel: 'Hotel',
      flight: 'Flight',
      car: 'Car',
      restaurant: 'Restaurant',
      package: 'Package',
      event: 'Event',
      experience: 'Experience'
    };
    return labels[type] || type;
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function bookNow(item: WishlistItem) {
    const routes: Record<string, string> = {
      hotel: '/hotels',
      flight: '/flights',
      car: '/cars',
      restaurant: '/restaurants',
      package: '/packages',
      event: '/events',
      experience: '/experiences'
    };
    goto(`${routes[item.type]}/${item.id}`);
  }

  function getPriceDisplay(price: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(price);
  }
</script>

<svelte:head>
  <title>My Wishlist - Travel App</title>
</svelte:head>

<div class="wishlist-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>❤️ My Wishlist</h1>
      <div class="header-actions">
        <span class="item-count">{wishlist.length} items</span>
      </div>
    </div>
  </header>

  <main class="main-content">
    {#if loading}
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Loading wishlist...</p>
      </div>
    {:else if wishlist.length === 0}
      <div class="empty-state">
        <div class="empty-icon">💔</div>
        <h2>Your wishlist is empty</h2>
        <p>Save your favorite hotels, flights, and experiences to book them later</p>
        <button class="primary-btn" onclick={() => goto('/dashboard')}>
          Start Exploring
        </button>
      </div>
    {:else}
      <!-- Category Tabs -->
      <div class="category-tabs">
        <button 
          class="tab" 
          class:active={activeTab === 'all'}
          onclick={() => activeTab = 'all'}
        >
          All ({wishlist.length})
        </button>
        {#each ['hotels', 'flights', 'cars', 'restaurants', 'packages', 'events', 'experiences'] as type}
          {@const count = wishlist.filter(i => i.type === type.slice(0, -1)).length}
          {#if count > 0}
            <button 
              class="tab" 
              class:active={activeTab === type}
              onclick={() => activeTab = type}
            >
              {type.charAt(0).toUpperCase() + type.slice(1)} ({count})
            </button>
          {/if}
        {/each}
      </div>

      <!-- Wishlist Grid -->
      <div class="wishlist-grid">
        {#each getFilteredItems() as item (item.id)}
          <div class="wishlist-card">
            <div class="card-image" style="background-image: url({item.image})">
              <button 
                class="remove-btn" 
                onclick={() => removeFromWishlist(item.id)}
                title="Remove from wishlist"
              >
                ✕
              </button>
              <span class="type-badge">
                {getTypeIcon(item.type)} {getTypeLabel(item.type)}
              </span>
            </div>
            
            <div class="card-content">
              <div class="card-header">
                <h3>{item.name}</h3>
                {#if item.rating}
                  <span class="rating">⭐ {item.rating.toFixed(1)}</span>
                {/if}
              </div>
              
              <p class="location">📍 {item.location}</p>
              
              <div class="card-footer">
                <span class="price">{getPriceDisplay(item.price)}</span>
                <button class="book-btn" onclick={() => bookNow(item)}>
                  Book Now
                </button>
              </div>
              
              <p class="saved-date">Saved on {formatDate(item.savedAt)}</p>
              
              {#if item.notes}
                <p class="notes">📝 {item.notes}</p>
              {/if}
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </main>
</div>

<style>
  .wishlist-page {
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
    max-width: 1400px;
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
    padding: 0.5rem;
  }

  h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1a1a2e;
  }

  .item-count {
    font-size: 0.875rem;
    color: #666;
  }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem;
    color: #666;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #eee;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h2 {
    color: #1a1a2e;
    margin-bottom: 0.5rem;
  }

  .empty-state p {
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
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .primary-btn:hover {
    transform: translateY(-2px);
  }

  .category-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }

  .tab {
    background: white;
    border: 1px solid #e0e0e0;
    padding: 0.75rem 1.25rem;
    border-radius: 20px;
    font-size: 0.875rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
  }

  .tab:hover {
    border-color: #667eea;
    color: #667eea;
  }

  .tab.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
  }

  .wishlist-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .wishlist-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  }

  .card-image {
    height: 180px;
    background-size: cover;
    background-position: center;
    position: relative;
  }

  .remove-btn {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 32px;
    height: 32px;
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    opacity: 0;
    transition: opacity 0.2s;
  }

  .wishlist-card:hover .remove-btn {
    opacity: 1;
  }

  .remove-btn:hover {
    background: #ff4757;
    color: white;
  }

  .type-badge {
    position: absolute;
    bottom: 0.75rem;
    left: 0.75rem;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
  }

  .card-content {
    padding: 1.25rem;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
  }

  .card-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0;
  }

  .rating {
    font-size: 0.875rem;
    color: #666;
  }

  .location {
    font-size: 0.875rem;
    color: #666;
    margin: 0 0 1rem;
  }

  .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #667eea;
  }

  .book-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .book-btn:hover {
    transform: scale(1.05);
  }

  .saved-date {
    font-size: 0.75rem;
    color: #999;
    margin: 0;
  }

  .notes {
    font-size: 0.875rem;
    color: #666;
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    margin-top: 0.75rem;
  }
</style>
