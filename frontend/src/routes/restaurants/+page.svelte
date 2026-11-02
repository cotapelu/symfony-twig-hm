<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { foodDeliveryService, type Restaurant } from '$lib/services/fooddelivery';

  let restaurants: Restaurant[] = [];
  let cuisines: string[] = [];
  let loading = false;
  let error = '';
  
  let searchParams = {
    query: '',
    city: '',
    cuisine: '',
    minRating: ''
  };

  onMount(async () => {
    await loadFeaturedRestaurants();
  });

  async function loadFeaturedRestaurants() {
    try {
      const data = await foodDeliveryService.getRestaurants();
      restaurants = data;
    } catch (err) {
      console.error('Failed to load restaurants:', err);
    }
  }

  async function searchRestaurants() {
    loading = true;
    error = '';
    
    try {
      restaurants = await foodDeliveryService.getRestaurants();
    } catch (err) {
      error = 'Failed to search restaurants';
      console.error(err);
    }
    loading = false;
  }

  async function loadCuisines() {
    // Cuisines can be derived from restaurants
  }

  function viewRestaurantDetails(restaurantId: string) {
    goto(`/restaurants/${restaurantId}`);
  }

  function getPriceRangeIndicator(range: number): string {
    return '💰'.repeat(range);
  }

  function getRatingStars(rating: number): string {
    return '⭐'.repeat(Math.floor(rating));
  }
</script>

<svelte:head>
  <title>Restaurant Booking - Travel Booking App</title>
</svelte:head>

<div class="restaurants-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Book a Table</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="search-field">
        <label for="query">Search</label>
        <input
          id="query"
          type="text"
          bind:value={searchParams.query}
          placeholder="Restaurant name or cuisine"
        />
      </div>

      <div class="search-field">
        <label for="city">City</label>
        <input
          id="city"
          type="text"
          bind:value={searchParams.city}
          placeholder="City"
        />
      </div>

      <div class="search-field">
        <label for="cuisine">Cuisine</label>
        <select id="cuisine" bind:value={searchParams.cuisine}>
          <option value="">All Cuisines</option>
          {#each cuisines as cuisine}
            <option value={cuisine}>{cuisine}</option>
          {/each}
        </select>
      </div>

      <div class="search-field">
        <label for="rating">Min Rating</label>
        <select id="rating" bind:value={searchParams.minRating}>
          <option value="">Any Rating</option>
          <option value="4.5">4.5+ ⭐</option>
          <option value="4.0">4.0+ ⭐</option>
          <option value="3.5">3.5+ ⭐</option>
        </select>
      </div>

      <button class="search-btn" on:click={searchRestaurants} disabled={loading}>
        {loading ? 'Searching...' : 'Search Restaurants'}
      </button>
    </div>
  </div>

  <main class="main-content">
    {#if error}
      <div class="error">
        <p>{error}</p>
      </div>
    {/if}

    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Searching for restaurants...</p>
      </div>
    {:else if restaurants.length > 0}
      <div class="results-header">
        <h2>{restaurants.length} Restaurants Found</h2>
      </div>

      <div class="restaurants-grid">
        {#each restaurants as restaurant}
          <div class="restaurant-card" on:click={() => viewRestaurantDetails(restaurant.id)}>
            <div class="restaurant-image">
              <div class="restaurant-icon">🍽️</div>
              <div class="restaurant-rating">
                ⭐ {restaurant.rating.toFixed(1)}
              </div>
            </div>

            <div class="restaurant-info">
              <h3 class="restaurant-name">{restaurant.name}</h3>
              <p class="restaurant-cuisine">{restaurant.cuisine}</p>
              
              <div class="restaurant-meta">
                <span class="price-range">{getPriceRangeIndicator(restaurant.priceRange)}</span>
                <span class="review-count">({restaurant.reviewCount} reviews)</span>
              </div>

              <div class="restaurant-features">
                {#each restaurant.features.slice(0, 3) as feature}
                  <span class="feature">{feature}</span>
                {/each}
              </div>

              <div class="restaurant-address">
                📍 {restaurant.address}, {restaurant.city}
              </div>

              <div class="restaurant-ambiance">
                {restaurant.ambiance}
              </div>
            </div>

            <div class="restaurant-footer">
              <div class="restaurant-price">
                <span class="label">Price Range:</span>
                <span class="value">{getPriceRangeIndicator(restaurant.priceRange)}</span>
              </div>
              <button class="book-btn">Book Table</button>
            </div>
          </div>
        {/each}
      </div>
    {:else if !loading}
      <div class="empty-state">
        <div class="empty-icon">🍽️</div>
        <h3>No restaurants found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    {/if}
  </main>
</div>

<style>
  .restaurants-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
  }

  .back-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
    margin-bottom: 0.5rem;
  }

  .back-link:hover {
    text-decoration: underline;
  }

  .header h1 {
    margin: 0;
    font-size: 1.75rem;
    color: #1e293b;
  }

  .search-section {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem 2rem;
  }

  .search-form {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
  }

  .search-field {
    display: flex;
    flex-direction: column;
  }

  .search-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .search-field input,
  .search-field select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .search-field input:focus,
  .search-field select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .search-btn {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    height: fit-content;
  }

  .search-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .search-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    color: #991b1b;
  }

  .loading {
    text-align: center;
    padding: 4rem;
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .results-header {
    margin-bottom: 1.5rem;
  }

  .results-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .restaurants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .restaurant-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .restaurant-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
  }

  .restaurant-image {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .restaurant-icon {
    font-size: 5rem;
  }

  .restaurant-rating {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .restaurant-info {
    padding: 1.5rem;
  }

  .restaurant-name {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .restaurant-cuisine {
    margin: 0 0 0.75rem 0;
    color: #667eea;
    font-weight: 500;
    font-size: 0.875rem;
  }

  .restaurant-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .price-range {
    font-size: 0.875rem;
  }

  .review-count {
    font-size: 0.875rem;
    color: #64748b;
  }

  .restaurant-features {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .feature {
    font-size: 0.75rem;
    background: #f1f5f9;
    color: #64748b;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
  }

  .restaurant-address {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .restaurant-ambiance {
    font-size: 0.875rem;
    color: #94a3b8;
    font-style: italic;
  }

  .restaurant-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .restaurant-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .restaurant-price .label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .restaurant-price .value {
    font-size: 1rem;
  }

  .book-btn {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
  }

  .empty-state {
    text-align: center;
    padding: 4rem;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    color: #1e293b;
    margin: 0 0 0.5rem 0;
  }

  .empty-state p {
    color: #64748b;
  }

  @media (max-width: 768px) {
    .header,
    .search-section {
      padding: 1rem;
    }

    .search-form {
      grid-template-columns: 1fr;
    }

    .main-content {
      padding: 1rem;
    }

    .restaurants-grid {
      grid-template-columns: 1fr;
    }
  }
</style>