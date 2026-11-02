<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';
  import { hotelsService, type Hotel } from '$lib/services/hotels';

  let hotel: Hotel | null = null;
  let roomTypes: any[] = [];
  let reviews: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let checkIn = '';
  let checkOut = '';
  let guests = 2;
  let selectedRoom: string | null = null;

  onMount(async () => {
    const hotelId = $page.params.id;
    if (hotelId) {
      await loadHotelDetails(hotelId);
      await loadRoomTypes(hotelId);
    }
  });

  async function loadHotelDetails(hotelId: string) {
    try {
      hotel = await hotelsService.getHotel(hotelId);
    } catch (err) {
      error = 'Failed to load hotel details';
    }
  }

  async function loadRoomTypes(hotelId: string) {
    try {
      if (hotel && hotel.roomTypes) {
        roomTypes = hotel.roomTypes;
      }
      loading = false;
    } catch (err) {
      error = 'Failed to load room types';
      loading = false;
    }
  }

  async function loadReviews(hotelId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/hotels/${hotelId}/reviews`);
      if (!response.ok) throw new Error('Failed to load reviews');
      const data = await response.json();
      reviews = data.reviews || [];
    } catch (err) {
      console.error('Failed to load reviews:', err);
    }
  }

  async function bookRoom(roomTypeId: string) {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!checkIn || !checkOut) {
      alert('Please select check-in and check-out dates');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch('http://localhost:9030/api/bookings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': $userStore.user?.id || 'anonymous'
        },
        body: JSON.stringify({
          hotelId: hotel.id,
          roomTypeId,
          checkIn,
          checkOut,
          guestCount: guests,
          guestInfo: {
            firstName: $userStore.user?.name?.split(' ')[0] || 'Guest',
            lastName: $userStore.user?.name?.split(' ')[1] || '',
            email: $userStore.user?.email || '',
            phone: '',
            country: ''
          },
          specialRequests: ''
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Booking successful! Booking ID: ' + data.booking.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function getStarRating(stars: number): string {
    return '⭐'.repeat(stars);
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
  <title>{hotel?.name || 'Hotel Details'} - Hotel Booking App</title>
</svelte:head>

<div class="hotel-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/hotels" class="back-link">← Back to Hotels</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading hotel details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadHotelDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if hotel}
    <div class="hotel-hero">
      <div class="hero-image">
        {#if hotel.images && hotel.images.length > 0}
          <img src={hotel.images[0]} alt={hotel.name} />
        {:else}
          <div class="placeholder-image">🏨</div>
        {/if}
        <div class="hero-overlay">
          <div class="hero-content">
            <h1>{hotel.name}</h1>
            <div class="hotel-meta">
              <span class="stars">{getStarRating(hotel.starRating)}</span>
              <span class="location">📍 {hotel.city}, {hotel.country}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>About</h2>
            <p class="description">{hotel.description}</p>
            <p class="address">{hotel.address}</p>
          </section>

          <section class="section">
            <h2>Amenities</h2>
            <div class="amenities-grid">
              {#each hotel.amenities || [] as amenity}
                <div class="amenity-item">✓ {amenity}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>Policies</h2>
            <div class="policies">
              <div class="policy-item">
                <strong>Check-in:</strong> {hotel.policies?.checkInTime || '14:00'}
              </div>
              <div class="policy-item">
                <strong>Check-out:</strong> {hotel.policies?.checkOutTime || '12:00'}
              </div>
              <div class="policy-item">
                <strong>Cancellation:</strong> {hotel.policies?.cancellationPolicy || 'Free cancellation up to 24 hours'}
              </div>
            </div>
          </section>

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
            <h3>Book Your Stay</h3>
            
            <div class="date-inputs">
              <div class="input-group">
                <label>Check-in</label>
                <input type="date" bind:value={checkIn} min={new Date().toISOString().split('T')[0]} />
              </div>
              <div class="input-group">
                <label>Check-out</label>
                <input type="date" bind:value={checkOut} min={checkIn || new Date().toISOString().split('T')[0]} />
              </div>
            </div>

            <div class="input-group">
              <label>Guests</label>
              <select bind:value={guests}>
                <option value={1}>1 Guest</option>
                <option value={2}>2 Guests</option>
                <option value={3}>3 Guests</option>
                <option value={4}>4 Guests</option>
              </select>
            </div>

            <div class="room-selection">
              <h4>Select Room Type</h4>
              {#each roomTypes as room}
                <div 
                  class="room-option" 
                  class:selected={selectedRoom === room.id}
                  on:click={() => selectedRoom = room.id}
                >
                  <div class="room-info">
                    <div class="room-name">{room.name}</div>
                    <div class="room-details">
                      {room.maxOccupancy} guests • {room.bedType} • {room.size}m²
                    </div>
                    <div class="room-amenities">
                      {(room.amenities || []).slice(0, 3).join(', ')}
                    </div>
                  </div>
                  <div class="room-price">
                    <div class="price">${room.basePrice}</div>
                    <div class="per-night">per night</div>
                  </div>
                </div>
              {/each}
            </div>

            <button 
              class="book-btn" 
              disabled={!selectedRoom || !checkIn || !checkOut || bookingLoading}
              on:click={() => selectedRoom && bookRoom(selectedRoom)}
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
  .hotel-detail-page {
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

  .back-link:hover {
    text-decoration: underline;
  }

  .loading,
  .error {
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

  .hotel-hero {
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
    font-size: 6rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

  .hotel-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .stars {
    font-size: 1.25rem;
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

  .address {
    color: #94a3b8;
    font-size: 0.875rem;
  }

  .amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.75rem;
  }

  .amenity-item {
    color: #059669;
    font-size: 0.875rem;
  }

  .policies {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .policy-item {
    color: #64748b;
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

  .date-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
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
  .input-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .input-group input:focus,
  .input-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .room-selection {
    margin: 1.5rem 0;
  }

  .room-selection h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
  }

  .room-option {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .room-option:hover {
    border-color: #667eea;
  }

  .room-option.selected {
    border-color: #667eea;
    background: #f5f3ff;
  }

  .room-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .room-details {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .room-amenities {
    font-size: 0.75rem;
    color: #059669;
  }

  .room-price {
    text-align: right;
  }

  .room-price .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .room-price .per-night {
    font-size: 0.75rem;
    color: #64748b;
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

    .hotel-hero {
      height: 300px;
    }

    .hero-content h1 {
      font-size: 1.75rem;
    }
  }

  @media (max-width: 640px) {
    .main-content {
      padding: 1rem;
    }

    .section {
      padding: 1rem;
    }

    .date-inputs {
      grid-template-columns: 1fr;
    }
  }
</style>