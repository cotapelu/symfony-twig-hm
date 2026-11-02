<script lang="ts">
  import { onMount } from 'svelte';

  interface Deal {
    id: string;
    type: 'hotel' | 'flight' | 'car' | 'restaurant' | 'package' | 'event' | 'experience';
    title: string;
    description: string;
    location: string;
    originalPrice: number;
    salePrice: number;
    discount: number;
    image: string;
    expiresAt: string;
    badge: string;
  }

  const deals: Deal[] = [
    {
      id: '1',
      type: 'hotel',
      title: 'Grand Plaza Hotel & Suites',
      description: 'Luxury stay in downtown with breakfast included',
      location: 'New York, NY',
      originalPrice: 299,
      salePrice: 149,
      discount: 50,
      image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
      expiresAt: '2024-12-31',
      badge: '50% OFF'
    },
    {
      id: '2',
      type: 'flight',
      title: 'Round-trip to Paris',
      description: 'Non-stop flights with Air France',
      location: 'JFK → CDG',
      originalPrice: 899,
      salePrice: 599,
      discount: 33,
      image: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=800',
      expiresAt: '2024-12-20',
      badge: 'BEST DEAL'
    },
    {
      id: '3',
      type: 'package',
      title: '7-Day Thailand Paradise',
      description: 'Bangkok + Phuket with all transfers',
      location: 'Thailand',
      originalPrice: 1899,
      salePrice: 999,
      discount: 47,
      image: 'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=800',
      expiresAt: '2025-01-15',
      badge: 'EARLY BIRD'
    },
    {
      id: '4',
      type: 'car',
      title: 'Luxury SUV Rental',
      description: 'BMW X5 for your road trip',
      location: 'Los Angeles, CA',
      originalPrice: 150,
      salePrice: 89,
      discount: 41,
      image: 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800',
      expiresAt: '2024-12-25',
      badge: '40% OFF'
    },
    {
      id: '5',
      type: 'restaurant',
      title: 'Romantic Dinner for Two',
      description: '5-course tasting menu with wine pairing',
      location: 'San Francisco, CA',
      originalPrice: 250,
      salePrice: 125,
      discount: 50,
      image: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800',
      expiresAt: '2024-12-14',
      badge: 'HALF PRICE'
    },
    {
      id: '6',
      type: 'experience',
      title: 'Private Wine Tour',
      description: 'Full-day vineyard experience with lunch',
      location: 'Napa Valley, CA',
      originalPrice: 350,
      salePrice: 199,
      discount: 43,
      image: 'https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?w=800',
      expiresAt: '2024-12-30',
      badge: 'LAST CHANCE'
    },
    {
      id: '7',
      type: 'event',
      title: 'Music Festival Pass',
      description: '3-day access to all stages',
      location: 'Austin, TX',
      originalPrice: 450,
      salePrice: 299,
      discount: 34,
      image: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800',
      expiresAt: '2024-12-10',
      badge: '33% OFF'
    },
    {
      id: '8',
      type: 'hotel',
      title: 'Beachfront Villa',
      description: 'Private pool villa with ocean view',
      location: 'Maldives',
      originalPrice: 1200,
      salePrice: 699,
      discount: 42,
      image: 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800',
      expiresAt: '2025-02-28',
      badge: 'FLASH SALE'
    }
  ];

  let selectedCategory = 'all';
  let sortBy = 'discount';
  let showExpired = false;

  $: filteredDeals = deals
    .filter(deal => selectedCategory === 'all' || deal.type === selectedCategory)
    .filter(deal => showExpired || new Date(deal.expiresAt) > new Date())
    .sort((a, b) => {
      if (sortBy === 'discount') return b.discount - a.discount;
      if (sortBy === 'price') return a.salePrice - b.salePrice;
      if (sortBy === 'expiring') return new Date(a.expiresAt).getTime() - new Date(b.expiresAt).getTime();
      return 0;
    });

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
    return icons[type] || '🏷️';
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  }

  function getDaysRemaining(dateStr: string): number {
    const diff = new Date(dateStr).getTime() - Date.now();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  }

  function getPriceDisplay(price: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(price);
  }
</script>

<svelte:head>
  <title>Deals & Discounts - Travel App</title>
</svelte:head>

<div class="deals-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>🏷️ Deals & Discounts</h1>
      <span class="deals-count">{filteredDeals.length} deals</span>
    </div>
  </header>

  <main class="main-content">
    <!-- Hero Banner -->
    <div class="hero-banner">
      <h2>🎉 Holiday Sale</h2>
      <p>Up to 50% off on hotels, flights & more!</p>
      <div class="countdown">
        <div class="countdown-item">
          <span class="countdown-value">05</span>
          <span class="countdown-label">Days</span>
        </div>
        <div class="countdown-item">
          <span class="countdown-value">12</span>
          <span class="countdown-label">Hours</span>
        </div>
        <div class="countdown-item">
          <span class="countdown-value">45</span>
          <span class="countdown-label">Mins</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="category-filter">
        <button 
          class="filter-btn"
          class:active={selectedCategory === 'all'}
          onclick={() => selectedCategory = 'all'}
        >
          All
        </button>
        <button 
          class="filter-btn"
          class:active={selectedCategory === 'hotel'}
          onclick={() => selectedCategory = 'hotel'}
        >
          🏨 Hotels
        </button>
        <button 
          class="filter-btn"
          class:active={selectedCategory === 'flight'}
          onclick={() => selectedCategory = 'flight'}
        >
          ✈️ Flights
        </button>
        <button 
          class="filter-btn"
          class:active={selectedCategory === 'car'}
          onclick={() => selectedCategory = 'car'}
        >
          🚗 Cars
        </button>
        <button 
          class="filter-btn"
          class:active={selectedCategory === 'package'}
          onclick={() => selectedCategory = 'package'}
        >
          📦 Packages
        </button>
      </div>

      <div class="sort-filter">
        <select bind:value={sortBy}>
          <option value="discount">Highest Discount</option>
          <option value="price">Lowest Price</option>
          <option value="expiring">Expiring Soon</option>
        </select>
        
        <label class="expired-toggle">
          <input type="checkbox" bind:checked={showExpired} />
          <span>Show expired</span>
        </label>
      </div>
    </div>

    <!-- Deals Grid -->
    <div class="deals-grid">
      {#each filteredDeals as deal (deal.id)}
        <div class="deal-card">
          <div class="card-image" style="background-image: url({deal.image})">
            <span class="discount-badge">-{deal.discount}%</span>
            <span class="deal-badge">{deal.badge}</span>
            <button class="wishlist-btn">❤️</button>
          </div>
          
          <div class="card-content">
            <div class="card-header">
              <span class="type-badge">{getTypeIcon(deal.type)} {deal.type}</span>
              {#if getDaysRemaining(deal.expiresAt) <= 3}
                <span class="urgent-badge">Expires in {getDaysRemaining(deal.expiresAt)} days</span>
              {/if}
            </div>
            
            <h3>{deal.title}</h3>
            <p class="location">📍 {deal.location}</p>
            <p class="description">{deal.description}</p>
            
            <div class="price-row">
              <div class="prices">
                <span class="sale-price">{getPriceDisplay(deal.salePrice)}</span>
                <span class="original-price">{getPriceDisplay(deal.originalPrice)}</span>
              </div>
              <span class="expires">Expires {formatDate(deal.expiresAt)}</span>
            </div>
            
            <button class="book-btn">
              Book Now
            </button>
          </div>
        </div>
      {/each}
    </div>

    {#if filteredDeals.length === 0}
      <div class="no-deals">
        <span class="no-icon">😢</span>
        <h3>No deals found</h3>
        <p>Try changing your filters or check back later</p>
      </div>
    {/if}

    <!-- Newsletter -->
    <div class="newsletter-section">
      <h3>📧 Get Exclusive Deals</h3>
      <p>Subscribe to our newsletter and be the first to know about new deals!</p>
      <div class="newsletter-form">
        <input type="email" placeholder="Enter your email" />
        <button>Subscribe</button>
      </div>
    </div>

    <!-- Promo Codes -->
    <div class="promo-section">
      <h3>🎁 Active Promo Codes</h3>
      <div class="promo-grid">
        <div class="promo-card">
          <span class="promo-code">SAVE20</span>
          <p>20% off on hotels</p>
          <span class="promo-valid">Valid until Dec 31</span>
        </div>
        <div class="promo-card">
          <span class="promo-code">FLIGHT50</span>
          <p>$50 off flights $300+</p>
          <span class="promo-valid">Valid until Dec 15</span>
        </div>
        <div class="promo-card">
          <span class="promo-code">WELCOME15</span>
          <p>15% off first booking</p>
          <span class="promo-valid">New users only</span>
        </div>
      </div>
    </div>
  </main>
</div>

<style>
  .deals-page {
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

  .deals-count {
    font-size: 0.875rem;
    color: #666;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .hero-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
  }

  .hero-banner h2 {
    font-size: 2rem;
    margin: 0 0 0.5rem;
  }

  .hero-banner p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0 0 1.5rem;
  }

  .countdown {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
  }

  .countdown-item {
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    min-width: 70px;
  }

  .countdown-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
  }

  .countdown-label {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .filters-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .category-filter {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    flex-wrap: wrap;
  }

  .filter-btn {
    padding: 0.75rem 1.25rem;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    background: white;
    font-size: 0.875rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
  }

  .filter-btn:hover {
    border-color: #667eea;
  }

  .filter-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .sort-filter {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .sort-filter select {
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
  }

  .expired-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .deal-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s;
  }

  .deal-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  }

  .card-image {
    height: 180px;
    background-size: cover;
    background-position: center;
    position: relative;
  }

  .discount-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #ff4757;
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.875rem;
  }

  .deal-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ffc107;
    color: #333;
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .wishlist-btn {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    background: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    opacity: 0;
    transition: opacity 0.2s;
  }

  .deal-card:hover .wishlist-btn {
    opacity: 1;
  }

  .card-content {
    padding: 1.25rem;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .type-badge {
    background: #f0f4ff;
    color: #667eea;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
  }

  .urgent-badge {
    background: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
  }

  .card-content h3 {
    font-size: 1.125rem;
    margin: 0 0 0.5rem;
    color: #1a1a2e;
  }

  .location {
    color: #666;
    font-size: 0.875rem;
    margin: 0 0 0.5rem;
  }

  .description {
    color: #888;
    font-size: 0.875rem;
    margin: 0 0 1rem;
    line-height: 1.4;
  }

  .price-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 1rem;
  }

  .prices {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
  }

  .sale-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
  }

  .original-price {
    color: #999;
    text-decoration: line-through;
    font-size: 0.875rem;
  }

  .expires {
    font-size: 0.75rem;
    color: #ff4757;
  }

  .book-btn {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .book-btn:hover {
    transform: scale(1.02);
  }

  .no-deals {
    text-align: center;
    padding: 4rem 2rem;
  }

  .no-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
  }

  .no-deals h3 {
    color: #1a1a2e;
    margin-bottom: 0.5rem;
  }

  .no-deals p {
    color: #666;
  }

  .newsletter-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
  }

  .newsletter-section h3 {
    margin: 0 0 0.5rem;
    color: #1a1a2e;
  }

  .newsletter-section p {
    color: #666;
    margin: 0 0 1.5rem;
  }

  .newsletter-form {
    display: flex;
    gap: 0.5rem;
    max-width: 400px;
    margin: 0 auto;
  }

  .newsletter-form input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
  }

  .newsletter-form button {
    padding: 0.875rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .promo-section {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    border-radius: 16px;
    padding: 2rem;
  }

  .promo-section h3 {
    text-align: center;
    color: #1a1a2e;
    margin: 0 0 1.5rem;
  }

  .promo-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }

  .promo-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
  }

  .promo-code {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
  }

  .promo-card p {
    color: #666;
    font-size: 0.875rem;
    margin: 0 0 0.5rem;
  }

  .promo-valid {
    font-size: 0.75rem;
    color: #999;
  }

  @media (max-width: 768px) {
    .filters-section {
      flex-direction: column;
      align-items: stretch;
    }
    
    .promo-grid {
      grid-template-columns: 1fr;
    }
    
    .newsletter-form {
      flex-direction: column;
    }
  }
</style>
