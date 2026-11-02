<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';

  let car: any = null;
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let pickupDate = '';
  let returnDate = '';
  let pickupLocation = '';
  let dropoffLocation = '';
  let driverAge = 25;
  let addInsurance = false;

  onMount(async () => {
    const carId = $page.params.id;
    if (carId) {
      await loadCarDetails(carId);
    }
  });

  async function loadCarDetails(carId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/cars/${carId}`);
      if (!response.ok) throw new Error('Failed to load car details');
      const data = await response.json();
      car = data.car;
      pickupLocation = car?.location?.address || '';
      dropoffLocation = car?.location?.address || '';
      loading = false;
    } catch (err) {
      error = (err as Error).message;
      loading = false;
    }
  }

  async function bookCar() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!pickupDate || !returnDate) {
      alert('Please select pickup and return dates');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch('http://localhost:9030/api/car-bookings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': $userStore.user?.id || 'anonymous'
        },
        body: JSON.stringify({
          carId: car.id,
          userId: $userStore.user?.id,
          pickupDate,
          returnDate,
          pickupLocation,
          dropoffLocation,
          driverAge,
          addInsurance,
          specialRequests: ''
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Car booked successfully! Booking ID: ' + data.booking.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function calculateTotalDays(): number {
    if (!pickupDate || !returnDate) return 0;
    const start = new Date(pickupDate);
    const end = new Date(returnDate);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
  }

  function calculateTotalPrice(): number {
    const days = calculateTotalDays();
    let total = days * (car?.pricePerDay || 0);
    if (addInsurance) total += days * 15; // $15/day insurance
    return total;
  }

  function getCarTypeIcon(type: string): string {
    const icons: { [key: string]: string } = {
      'ECONOMY': '🚗', 'COMPACT': '🚙', 'MIDSIZE': '🚗',
      'FULLSIZE': '🚘', 'SUV': '🚙', 'LUXURY': '🏎️', 'VAN': '🚐'
    };
    return icons[type] || '🚗';
  }
</script>

<svelte:head>
  <title>{car ? `${car.brand} ${car.model}` : 'Car Details'} - Travel App</title>
</svelte:head>

<div class="car-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/cars" class="back-link">← Back to Cars</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading car details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadCarDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if car}
    <div class="car-hero">
      <div class="hero-image">
        {#if car.images && car.images.length > 0}
          <img src={car.images[0]} alt={`${car.brand} ${car.model}`} />
        {:else}
          <div class="placeholder-image">{getCarTypeIcon(car.type)}</div>
        {/if}
      </div>
      <div class="hero-content">
        <h1>{car.brand} {car.model}</h1>
        <p class="car-type">{getCarTypeIcon(car.type)} {car.type} • {car.year}</p>
        <div class="car-rating">
          ⭐ {car.rating.toFixed(1)} ({car.reviewCount} reviews)
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>Car Specifications</h2>
            <div class="specs-grid">
              <div class="spec-item">
                <span class="spec-icon">👥</span>
                <span class="spec-value">{car.seats} Seats</span>
              </div>
              <div class="spec-item">
                <span class="spec-icon">🧳</span>
                <span class="spec-value">{car.luggageCapacity} Bags</span>
              </div>
              <div class="spec-item">
                <span class="spec-icon">⚙️</span>
                <span class="spec-value">{car.transmission}</span>
              </div>
              <div class="spec-item">
                <span class="spec-icon">⛽</span>
                <span class="spec-value">{car.fuelType}</span>
              </div>
              <div class="spec-item">
                <span class="spec-icon">🚪</span>
                <span class="spec-value">{car.doors} Doors</span>
              </div>
              <div class="spec-item">
                <span class="spec-icon">❄️</span>
                <span class="spec-value">{car.hasAirConditioning ? 'A/C' : 'No A/C'}</span>
              </div>
            </div>
          </section>

          <section class="section">
            <h2>Features</h2>
            <div class="features-list">
              {#each car.features || [] as feature}
                <div class="feature-item">✓ {feature}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>Rental Terms</h2>
            <div class="terms-list">
              <div class="term-item">
                <strong>Minimum Age:</strong> {car.rentalTerms?.minimumAge} years
              </div>
              <div class="term-item">
                <strong>Free Cancellation:</strong> {car.rentalTerms?.freeCancellationHours} hours before pickup
              </div>
              <div class="term-item">
                <strong>Mileage:</strong> {car.rentalTerms?.mileageLimit} km/day
              </div>
              <div class="term-item">
                <strong>Fuel Policy:</strong> {car.rentalTerms?.fuelPolicy}
              </div>
            </div>
          </section>

          <section class="section">
            <h2>Location</h2>
            <p class="location-text">📍 {car.location?.address}, {car.location?.city}</p>
            <p class="location-hours">🕐 {car.location?.pickupInstructions}</p>
          </section>
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Book This Car</h3>
            
            <div class="price-display">
              <span class="price">${car.pricePerDay}</span>
              <span class="per-day">/day</span>
            </div>

            <div class="input-group">
              <label>Pickup Date</label>
              <input type="date" bind:value={pickupDate} min={new Date().toISOString().split('T')[0]} />
            </div>

            <div class="input-group">
              <label>Return Date</label>
              <input type="date" bind:value={returnDate} min={pickupDate || new Date().toISOString().split('T')[0]} />
            </div>

            <div class="input-group">
              <label>Pickup Location</label>
              <input type="text" bind:value={pickupLocation} placeholder="Enter pickup address" />
            </div>

            <div class="input-group">
              <label>Dropoff Location</label>
              <input type="text" bind:value={dropoffLocation} placeholder="Enter dropoff address" />
            </div>

            <div class="input-group">
              <label>Driver Age</label>
              <input type="number" bind:value={driverAge} min="18" max="99" />
            </div>

            <div class="checkbox-group">
              <label>
                <input type="checkbox" bind:checked={addInsurance} />
                <span>Add Insurance (+$15/day)</span>
              </label>
            </div>

            {#if calculateTotalDays() > 0}
              <div class="price-breakdown">
                <div class="breakdown-item">
                  <span>${car.pricePerDay} x {calculateTotalDays()} days</span>
                  <span>${(car.pricePerDay * calculateTotalDays()).toFixed(2)}</span>
                </div>
                {#if addInsurance}
                  <div class="breakdown-item">
                    <span>Insurance x {calculateTotalDays()} days</span>
                    <span>${(15 * calculateTotalDays()).toFixed(2)}</span>
                  </div>
                {/if}
                <div class="breakdown-item total">
                  <span>Total</span>
                  <span>${calculateTotalPrice().toFixed(2)}</span>
                </div>
              </div>
            {/if}

            <button 
              class="book-btn" 
              disabled={!pickupDate || !returnDate || !pickupLocation || bookingLoading}
              on:click={bookCar}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Book Now
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .car-detail-page {
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

  .car-hero {
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .hero-content h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    color: #1e293b;
  }

  .car-type {
    margin: 0 0 0.5rem 0;
    color: #64748b;
    font-size: 1rem;
  }

  .car-rating {
    color: #f59e0b;
    font-weight: 500;
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

  .specs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }

  .spec-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .spec-icon {
    font-size: 1.25rem;
  }

  .spec-value {
    font-size: 0.875rem;
    color: #1e293b;
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

  .terms-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .term-item {
    color: #64748b;
    font-size: 0.875rem;
  }

  .location-text {
    color: #1e293b;
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }

  .location-hours {
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
    margin: 0 0 1rem 0;
    color: #1e293b;
  }

  .price-display {
    margin-bottom: 1.5rem;
  }

  .price {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
  }

  .per-day {
    color: #64748b;
    font-size: 1rem;
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

  .input-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .checkbox-group {
    margin: 1rem 0;
  }

  .checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: #374151;
  }

  .price-breakdown {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
  }

  .breakdown-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .breakdown-item.total {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    padding-top: 0.5rem;
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
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

    .specs-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 640px) {
    .hero-image {
      height: 250px;
    }

    .specs-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .features-list {
      grid-template-columns: 1fr;
    }
  }
</style>
