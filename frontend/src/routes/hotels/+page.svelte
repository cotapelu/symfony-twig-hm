<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { hotelsService, type Hotel } from '$lib/services/hotels';

  let hotels: Hotel[] = [];
  let loading = true;
  let error = '';
  
  let searchParams = {
    city: '',
    checkIn: '',
    checkOut: '',
    guests: 2,
    minPrice: '',
    maxPrice: '',
    starRating: ''
  };

  onMount(async () => {
    await loadHotels();
  });

  async function loadHotels() {
    loading = true;
    error = '';
    
    try {
      hotels = await hotelsService.searchHotels({
        city: searchParams.city || 'Ho Chi Minh',
        checkIn: searchParams.checkIn || new Date().toISOString().split('T')[0],
        checkOut: searchParams.checkOut || new Date(Date.now() + 86400000).toISOString().split('T')[0],
        guests: searchParams.guests
      });
    } catch (err) {
      error = 'Failed to load hotels';
      console.error(err);
    }
    loading = false;
  }

  function handleSearch() {
    loadHotels();
  }

  function viewHotelDetails(hotelId: string) {
    goto(`/hotels/${hotelId}`);
  }

  function getStarRating(stars: number): string {
    return '⭐'.repeat(stars);
  }

  function formatAmenities(amenities: string[]): string {
    if (amenities.length <= 3) return amenities.join(', ');
    return amenities.slice(0, 3).join(', ') + ` +${amenities.length - 3} more`;
  }
</script>

<svelte:head>
  <title>Search Hotels - Hotel Booking App</title>
</svelte:head>

<div class="hotels-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Find Your Perfect Stay</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="search-field">
        <label for="city">Destination</label>
        <input
          id="city"
          type="text"
          bind:value={searchParams.city}
          placeholder="Where are you going?"
        />
      </div>
      
      <div class="search-field">
        <label for="checkIn">Check-in</label>
        <input
          id="checkIn"
          type="date"
          bind:value={searchParams.checkIn}
        />
      </div>
      
      <div class="search-field">
        <label for="checkOut">Check-out</label>
        <input
          id="checkOut"
          type="date"
          bind:value={searchParams.checkOut}
        />
      </div>
      
      <div class="search-field">
        <label for="guests">Guests</label>
        <select id="guests" bind:value={searchParams.guests}>
          <option value={1}>1 Guest</option>
          <option value={2}>2 Guests</option>
          <option value={3}>3 Guests</option>
          <option value={4}>4 Guests</option>
          <option value={5}>5+ Guests</option>
        </select>
      </div>
      
      <button class="search-btn" on:click={handleSearch}>
        Search
      </button>
    </div>
  </div>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Searching for hotels...</p>
      </div>
    {:else if error}
      <div class="error">
        <p>{error}</p>
        <button class="retry-btn" on:click={loadHotels}>Try Again</button>
      </div>
    {:else if hotels.length === 0}
      <div class="empty-state">
        <div class="empty-icon">🏨</div>
        <h3>No hotels found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    {:else}
      <div class="results-header">
        <h2>{hotels.length} Hotels Found</h2>
      </div>
      
      <div class="hotels-grid">
        {#each hotels as hotel}
          <div class="hotel-card" on:click={() => viewHotelDetails(hotel.id)}>
            <div class="hotel-image">
              {#if hotel.images && hotel.images.length > 0}
                <img src={hotel.images[0]} alt={hotel.name} />
              {:else}
                <div class="placeholder-image">🏨</div>
              {/if}
              <div class="hotel-rating">{getStarRating(hotel.starRating)}</div>
            </div>
            
            <div class="hotel-info">
              <h3 class="hotel-name">{hotel.name}</h3>
              <p class="hotel-location">📍 {hotel.city}, {hotel.country}</p>
              <p class="hotel-description">{hotel.description}</p>
              
              <div class="hotel-amenities">
                <span>✓ {formatAmenities(hotel.amenities || [])}</span>
              </div>
              
              <div class="hotel-footer">
                <div class="hotel-price">
                  <span class="price-label">From</span>
                  <span class="price-value">$250</span>
                  <span class="price-unit">/night</span>
                </div>
                <button class="view-btn">View Details</button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </main>
</div>

<style>
  .hotels-page {
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
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
  }

  .search-field {
    flex: 1;
    min-width: 150px;
  }

  .search-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .search-field input,
  .search-field select {
    width: 100%;
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
    padding: 0.75rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .search-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
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

  .error {
    text-align: center;
    padding: 4rem;
  }

  .error p {
    color: #ef4444;
    margin-bottom: 1rem;
  }

  .retry-btn {
    padding: 0.75rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
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

  .results-header {
    margin-bottom: 1.5rem;
  }

  .results-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .hotels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .hotel-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .hotel-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  }

  .hotel-image {
    position: relative;
    height: 200px;
    background: #e2e8f0;
  }

  .hotel-image img {
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
    font-size: 4rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .hotel-rating {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
  }

  .hotel-info {
    padding: 1.5rem;
  }

  .hotel-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .hotel-location {
    margin: 0 0 0.75rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .hotel-description {
    margin: 0 0 1rem 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .hotel-amenities {
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #059669;
  }

  .hotel-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .hotel-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }

  .price-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .price-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .price-unit {
    font-size: 0.875rem;
    color: #64748b;
  }

  .view-btn {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
  }

  @media (max-width: 768px) {
    .header,
    .search-section {
      padding: 1rem;
    }

    .search-form {
      flex-direction: column;
    }

    .search-field {
      width: 100%;
    }

    .main-content {
      padding: 1rem;
    }

    .hotels-grid {
      grid-template-columns: 1fr;
    }
  }
</style>