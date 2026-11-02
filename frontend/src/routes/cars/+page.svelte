<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { bookingService } from '$lib/services/booking';

  let cars: any[] = [];
  let loading = false;
  let error = '';
  
  let searchParams = {
    location: '',
    carType: '',
    maxPrice: '',
    seats: ''
  };

  const carTypes = [
    { value: '', label: 'All Types' },
    { value: 'ECONOMY', label: 'Economy' },
    { value: 'COMPACT', label: 'Compact' },
    { value: 'MIDSIZE', label: 'Midsize' },
    { value: 'SUV', label: 'SUV' },
    { value: 'LUXURY', label: 'Luxury' },
    { value: 'VAN', label: 'Van' }
  ];

  onMount(async () => {
    await loadFeaturedCars();
  });

  async function loadFeaturedCars() {
    try {
      const data = await bookingService.getMyBookings();
      cars = data.bookings || [];
    } catch (err) {
      console.error('Failed to load cars:', err);
    }
  }

  async function searchCars() {
    loading = true;
    error = '';
    
    try {
      const data = await bookingService.getMyBookings();
      cars = data.bookings || [];
    } catch (err) {
      error = 'Failed to search cars';
    }
    loading = false;
  }

  function viewCarDetails(carId: string) {
    goto(`/cars/${carId}`);
  }

  function getCarTypeIcon(type: string): string {
    const icons: Record<string, string> = {
      'ECONOMY': '🚗',
      'COMPACT': '🚙',
      'MIDSIZE': '🚘',
      'SUV': '🚐',
      'LUXURY': '🏎️',
      'VAN': '🚐',
      'CONVERTIBLE': '🏎️'
    };
    return icons[type] || '🚗';
  }
</script>

<svelte:head>
  <title>Car Rental - Travel Booking App</title>
</svelte:head>

<div class="cars-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Rent a Car</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="search-field">
        <label for="location">Pickup Location</label>
        <input
          id="location"
          type="text"
          bind:value={searchParams.location}
          placeholder="City or airport"
        />
      </div>

      <div class="search-field">
        <label for="carType">Car Type</label>
        <select id="carType" bind:value={searchParams.carType}>
          {#each carTypes as type}
            <option value={type.value}>{type.label}</option>
          {/each}
        </select>
      </div>

      <div class="search-field">
        <label for="maxPrice">Max Price/Day ($)</label>
        <input
          id="maxPrice"
          type="number"
          bind:value={searchParams.maxPrice}
          placeholder="Max price"
        />
      </div>

      <div class="search-field">
        <label for="seats">Min Seats</label>
        <select id="seats" bind:value={searchParams.seats}>
          <option value="">Any</option>
          <option value="2">2+</option>
          <option value="4">4+</option>
          <option value="5">5+</option>
          <option value="7">7+</option>
        </select>
      </div>

      <button class="search-btn" on:click={searchCars} disabled={loading}>
        {loading ? 'Searching...' : 'Search Cars'}
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
        <p>Searching for cars...</p>
      </div>
    {:else if cars.length > 0}
      <div class="results-header">
        <h2>{cars.length} Cars Available</h2>
      </div>

      <div class="cars-grid">
        {#each cars as car}
          <div class="car-card" on:click={() => viewCarDetails(car.id)}>
            <div class="car-image">
              <div class="car-icon">{getCarTypeIcon(car.type)}</div>
              <div class="car-rating">
                ⭐ {car.rating.toFixed(1)}
              </div>
            </div>

            <div class="car-info">
              <h3 class="car-name">{car.brand} {car.model}</h3>
              <p class="car-details">{car.year} • {car.type}</p>
              
              <div class="car-specs">
                <span class="spec">👤 {car.seats} seats</span>
                <span class="spec">⚙️ {car.transmission}</span>
                <span class="spec">⛽ {car.fuelType}</span>
              </div>

              <div class="car-features">
                {#each car.features.slice(0, 3) as feature}
                  <span class="feature">{feature}</span>
                {/each}
              </div>

              <div class="car-location">
                📍 {car.location.city}
              </div>
            </div>

            <div class="car-footer">
              <div class="car-price">
                <span class="price">${car.pricePerDay}</span>
                <span class="per-day">/day</span>
              </div>
              <button class="view-btn">View Details</button>
            </div>
          </div>
        {/each}
      </div>
    {:else if !loading}
      <div class="empty-state">
        <div class="empty-icon">🚗</div>
        <h3>No cars found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    {/if}
  </main>
</div>

<style>
  .cars-page {
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

  .cars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .car-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .car-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
  }

  .car-image {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .car-icon {
    font-size: 5rem;
  }

  .car-rating {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
  }

  .car-info {
    padding: 1.5rem;
  }

  .car-name {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .car-details {
    margin: 0 0 1rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .car-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .spec {
    font-size: 0.875rem;
    color: #64748b;
  }

  .car-features {
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

  .car-location {
    font-size: 0.875rem;
    color: #059669;
    margin-bottom: 1rem;
  }

  .car-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .car-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }

  .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .per-day {
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

    .cars-grid {
      grid-template-columns: 1fr;
    }
  }
</style>