<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';
  import { foodDeliveryService, type Restaurant } from '$lib/services/fooddelivery';

  let restaurant: Restaurant | null = null;
  let tables: any[] = [];
  let reviews: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let date = '';
  let time = '';
  let partySize = 2;
  let selectedTable: string | null = null;
  let specialRequests = '';

  onMount(async () => {
    const restaurantId = $page.params.id;
    if (restaurantId) {
      await loadRestaurantDetails(restaurantId);
    }
  });

  async function loadRestaurantDetails(restaurantId: string) {
    try {
      restaurant = await foodDeliveryService.getRestaurant(restaurantId);
      loading = false;
    } catch (err) {
      error = 'Failed to load restaurant details';
      loading = false;
    }
  }

  async function loadTables() {
    if (!date || !time) return;
    try {
      const response = await fetch(`http://localhost:9030/api/restaurants/${$page.params.id}/tables?date=${date}&time=${time}&partySize=${partySize}`);
      if (!response.ok) throw new Error('Failed to load tables');
      const data = await response.json();
      tables = data.tables || [];
    } catch (err) {
      console.error('Failed to load tables:', err);
    }
  }

  async function loadReviews(restaurantId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/restaurants/${restaurantId}/reviews`);
      if (!response.ok) throw new Error('Failed to load reviews');
      const data = await response.json();
      reviews = data.reviews || [];
    } catch (err) {
      console.error('Failed to load reviews:', err);
    }
  }

  async function makeReservation() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!date || !time) {
      alert('Please select date and time');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch('http://localhost:9030/api/restaurant-reservations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': $userStore.user?.id || 'anonymous'
        },
        body: JSON.stringify({
          restaurantId: restaurant.id,
          date,
          time,
          partySize,
          specialRequests,
          tableId: selectedTable
        })
      });

      if (!response.ok) throw new Error('Reservation failed');
      
      const data = await response.json();
      alert('Reservation successful! Confirmation code: ' + data.reservation.confirmationCode);
      goto('/bookings');
    } catch (err) {
      alert('Reservation failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function getPriceRangeSymbol(count: number): string {
    return '$'.repeat(count);
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }
</script>

<svelte:head>
  <title>{restaurant?.name || 'Restaurant Details'} - Travel App</title>
</svelte:head>

<div class="restaurant-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/restaurants" class="back-link">← Back to Restaurants</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading restaurant details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadRestaurantDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if restaurant}
    <div class="restaurant-hero">
      <div class="hero-image">
        {#if restaurant.images && restaurant.images.length > 0}
          <img src={restaurant.images[0]} alt={restaurant.name} />
        {:else}
          <div class="placeholder-image">🍽️</div>
        {/if}
      </div>
      <div class="hero-content">
        <div class="hero-text">
          <h1>{restaurant.name}</h1>
          <p class="cuisine">{restaurant.cuisine} • {getPriceRangeSymbol(restaurant.priceRange)}</p>
          <div class="rating">
            ⭐ {restaurant.rating.toFixed(1)} ({restaurant.reviewCount} reviews)
          </div>
          <p class="location">📍 {restaurant.address}, {restaurant.city}</p>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>About</h2>
            <p class="description">{restaurant.description}</p>
          </section>

          <section class="section">
            <h2>Opening Hours</h2>
            <div class="hours-list">
              {#each Object.entries(restaurant.openingHours || {}) as [day, hours]}
                <div class="hour-item">
                  <span class="day">{day}</span>
                  <span class="time">{hours.open} - {hours.close}</span>
                </div>
              {/each}
            </div>
          </section>

          {#if restaurant.features && restaurant.features.length > 0}
            <section class="section">
              <h2>Features</h2>
              <div class="features-list">
                {#each restaurant.features as feature}
                  <div class="feature-item">✓ {feature}</div>
                {/each}
              </div>
            </section>
          {/if}

          {#if reviews.length > 0}
            <section class="section">
              <h2>Reviews ({reviews.length})</h2>
              <div class="reviews-list">
                {#each reviews.slice(0, 3) as review}
                  <div class="review-card">
                    <div class="review-header">
                      <span class="review-rating">{'⭐'.repeat(review.rating)}</span>
                      <span class="review-date">{formatDate(review.createdAt)}</span>
                    </div>
                    <h4>{review.title}</h4>
                    <p>{review.content}</p>
                  </div>
                {/each}
              </div>
            </section>
          {/if}
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Make a Reservation</h3>
            
            <div class="input-group">
              <label>Date</label>
              <input type="date" bind:value={date} min={new Date().toISOString().split('T')[0]} on:change={loadTables} />
            </div>

            <div class="input-group">
              <label>Time</label>
              <input type="time" bind:value={time} on:change={loadTables} />
            </div>

            <div class="input-group">
              <label>Party Size</label>
              <select bind:value={partySize} on:change={loadTables}>
                {#each [1, 2, 3, 4, 5, 6, 7, 8] as size}
                  <option value={size}>{size} {size === 1 ? 'Person' : 'People'}</option>
                {/each}
              </select>
            </div>

            {#if tables.length > 0}
              <div class="tables-section">
                <h4>Available Tables</h4>
                {#each tables as table}
                  <div 
                    class="table-option" 
                    class:selected={selectedTable === table.id}
                    on:click={() => selectedTable = table.id}
                  >
                    <span class="table-type">{table.type} Table</span>
                    <span class="table-capacity">{table.capacity} seats</span>
                    <span class="table-location">{table.location}</span>
                  </div>
                {/each}
              </div>
            {:else if date && time}
              <p class="no-tables">No tables available for selected time</p>
            {/if}

            <div class="input-group">
              <label>Special Requests</label>
              <textarea bind:value={specialRequests} placeholder="Allergies, special occasions, etc." rows="3"></textarea>
            </div>

            <button 
              class="book-btn" 
              disabled={!date || !time || bookingLoading}
              on:click={makeReservation}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Reserve Table
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .restaurant-detail-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
  }

  .back-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
  }

  .loading, .error {
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

  .restaurant-hero {
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }

  .hero-image {
    height: 350px;
    overflow: hidden;
  }

  .hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .placeholder-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8rem;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .hero-text h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    color: #1e293b;
  }

  .cuisine {
    margin: 0 0 0.5rem 0;
    color: #64748b;
    font-size: 1rem;
  }

  .rating {
    color: #f59e0b;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .location {
    color: #64748b;
    font-size: 0.875rem;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
  }

  .section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section h2 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .description {
    color: #64748b;
    line-height: 1.6;
  }

  .hours-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .hour-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .day {
    font-weight: 500;
    color: #1e293b;
  }

  .time {
    color: #64748b;
  }

  .features-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }

  .feature-item {
    color: #059669;
    font-size: 0.875rem;
  }

  .reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .review-card {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }

  .review-rating {
    font-size: 0.875rem;
  }

  .review-date {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .review-card h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .review-card p {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .booking-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
  }

  .booking-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .booking-card h3 {
    margin: 0 0 1.5rem 0;
    color: #1e293b;
  }

  .input-group {
    margin-bottom: 1rem;
  }

  .input-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .input-group input,
  .input-group select,
  .input-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .input-group textarea {
    resize: vertical;
  }

  .tables-section {
    margin: 1rem 0;
  }

  .tables-section h4 {
    margin: 0 0 0.75rem 0;
    color: #1e293b;
    font-size: 0.875rem;
  }

  .table-option {
    display: flex;
    flex-direction: column;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .table-option:hover {
    border-color: #667eea;
  }

  .table-option.selected {
    border-color: #667eea;
    background: #f5f3ff;
  }

  .table-type {
    font-weight: 600;
    color: #1e293b;
  }

  .table-capacity {
    font-size: 0.875rem;
    color: #64748b;
  }

  .table-location {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .no-tables {
    color: #ef4444;
    font-size: 0.875rem;
    text-align: center;
    padding: 1rem;
  }

  .book-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .book-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .book-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @media (max-width: 968px) {
    .content-grid {
      grid-template-columns: 1fr;
    }

    .booking-sidebar {
      position: static;
    }
  }
</style>
