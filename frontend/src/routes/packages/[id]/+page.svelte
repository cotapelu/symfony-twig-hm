<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';
  import { bookingService, type Booking } from '$lib/services/booking';

  let pkg: any = null;
  let itinerary: any[] = [];
  let reviews: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let travelDate = '';
  let travelers = 2;
  let specialRequests = '';

  onMount(async () => {
    const packageId = $page.params.id;
    if (packageId) {
      await loadPackageDetails(packageId);
    }
  });

  async function loadPackageDetails(packageId: string) {
    try {
      const data = await bookingService.getBookingById(packageId);
      pkg = {
        id: data.id,
        name: data.serviceName,
        description: data.serviceType,
        price: data.totalAmount
      };
      loading = false;
    } catch (err) {
      error = 'Failed to load package details';
      loading = false;
    }
  }

  async function loadItinerary(packageId: string) {
    // Optional: load from API if available
  }

  async function bookPackage() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!travelDate) {
      alert('Please select a travel date');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch(`http://localhost:9030/api/packages/${pkg.id}/book`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': $userStore.user?.id || 'anonymous'
        },
        body: JSON.stringify({
          userId: $userStore.user?.id,
          travelDate,
          travelers,
          specialRequests
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Package booked successfully! Booking ID: ' + data.booking.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function calculateTotalPrice(): number {
    return (pkg?.totalPrice || 0) * travelers;
  }
</script>

<svelte:head>
  <title>{pkg?.name || 'Package Details'} - Travel App</title>
</svelte:head>

<div class="package-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/packages" class="back-link">← Back to Packages</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading package details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadPackageDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if pkg}
    <div class="package-hero">
      <div class="hero-image">
        {#if pkg.images && pkg.images.length > 0}
          <img src={pkg.images[0]} alt={pkg.name} />
        {:else}
          <div class="placeholder-image">🎒</div>
        {/if}
        <div class="hero-overlay">
          <div class="hero-content">
            <h1>{pkg.name}</h1>
            <p class="destination">🌍 {pkg.destination}</p>
            <div class="package-meta">
              <span class="duration">📅 {pkg.durationDays} days</span>
              <span class="rating">⭐ {pkg.rating.toFixed(1)}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>Overview</h2>
            <p class="description">{pkg.description}</p>
            <div class="highlights">
              {#each pkg.highlights || [] as highlight}
                <div class="highlight-item">✦ {highlight}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>What's Included</h2>
            <div class="includes-list">
              {#each pkg.inclusions || [] as item}
                <div class="include-item">✓ {item}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>Itinerary</h2>
            <div class="itinerary-list">
              {#each itinerary as day}
                <div class="day-card">
                  <div class="day-number">Day {day.dayNumber}</div>
                  <h4>{day.title}</h4>
                  <p>{day.description}</p>
                  {#if day.activities && day.activities.length > 0}
                    <div class="activities">
                      {#each day.activities as activity}
                        <span class="activity">{activity}</span>
                      {/each}
                    </div>
                  {/if}
                </div>
              {/each}
            </div>
          </section>

          {#if pkg.exclusions && pkg.exclusions.length > 0}
            <section class="section">
              <h2>Not Included</h2>
              <div class="exclusions-list">
                {#each pkg.exclusions as item}
                  <div class="exclusion-item">✗ {item}</div>
                {/each}
              </div>
            </section>
          {/if}
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Book This Package</h3>
            
            <div class="price-display">
              <span class="price">${pkg.totalPrice.toLocaleString()}</span>
              <span class="per-person">/person</span>
            </div>

            <div class="input-group">
              <label>Travel Date</label>
              <input type="date" bind:value={travelDate} min={new Date().toISOString().split('T')[0]} />
            </div>

            <div class="input-group">
              <label>Number of Travelers</label>
              <select bind:value={travelers}>
                {#each [1, 2, 3, 4, 5, 6, 7, 8] as num}
                  <option value={num}>{num} {num === 1 ? 'Person' : 'People'}</option>
                {/each}
              </select>
            </div>

            <div class="input-group">
              <label>Special Requests</label>
              <textarea bind:value={specialRequests} placeholder="Dietary requirements, accessibility needs, etc." rows="3"></textarea>
            </div>

            <div class="total-section">
              <div class="total-row">
                <span>${pkg.totalPrice.toLocaleString()} × {travelers}</span>
                <span>${calculateTotalPrice().toLocaleString()}</span>
              </div>
              <div class="total-row grand-total">
                <span>Total</span>
                <span>${calculateTotalPrice().toLocaleString()}</span>
              </div>
            </div>

            <button 
              class="book-btn" 
              disabled={!travelDate || bookingLoading}
              on:click={bookPackage}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Book Package
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .package-detail-page {
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

  .package-hero {
    position: relative;
    height: 400px;
    overflow: hidden;
  }

  .hero-image {
    width: 100%;
    height: 100%;
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

  .hero-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    padding: 2rem;
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
    color: white;
  }

  .hero-content h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
  }

  .destination {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    opacity: 0.9;
  }

  .package-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 1rem;
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
    margin-bottom: 1rem;
  }

  .highlights {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .highlight-item {
    background: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .includes-list, .exclusions-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }

  .include-item {
    color: #059669;
    font-size: 0.875rem;
  }

  .exclusion-item {
    color: #dc2626;
    font-size: 0.875rem;
  }

  .itinerary-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .day-card {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .day-number {
    background: #667eea;
    color: white;
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .day-card h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .day-card p {
    margin: 0 0 0.5rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .activities {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .activity {
    background: #ede9fe;
    color: #7c3aed;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
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
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .price {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
  }

  .per-person {
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

  .total-section {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .total-row.grand-total {
    font-size: 1.25rem;
    font-weight: 700;
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

    .package-hero {
      height: 300px;
    }

    .hero-content h1 {
      font-size: 1.75rem;
    }
  }
</style>
