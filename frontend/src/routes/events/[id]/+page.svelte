<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';

  let event: any = null;
  let schedule: any[] = [];
  let reviews: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let selectedTickets: Map<string, number> = new Map();

  onMount(async () => {
    const eventId = $page.params.id;
    if (eventId) {
      await loadEventDetails(eventId);
      await loadSchedule(eventId);
      await loadReviews(eventId);
    }
  });

  async function loadEventDetails(eventId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/events/${eventId}`);
      if (!response.ok) throw new Error('Failed to load event details');
      const data = await response.json();
      event = data;
      loading = false;
    } catch (err) {
      error = (err as Error).message;
      loading = false;
    }
  }

  async function loadSchedule(eventId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/events/${eventId}/schedule`);
      if (!response.ok) throw new Error('Failed to load schedule');
      const data = await response.json();
      schedule = data || [];
    } catch (err) {
      console.error('Failed to load schedule:', err);
    }
  }

  async function loadReviews(eventId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/events/${eventId}/reviews`);
      if (!response.ok) throw new Error('Failed to load reviews');
      const data = await response.json();
      reviews = data || [];
    } catch (err) {
      console.error('Failed to load reviews:', err);
    }
  }

  function updateTicketQuantity(ticketTypeId: string, quantity: number) {
    if (quantity > 0) {
      selectedTickets.set(ticketTypeId, quantity);
    } else {
      selectedTickets.delete(ticketTypeId);
    }
    selectedTickets = selectedTickets;
  }

  function getTicketQuantity(ticketTypeId: string): number {
    return selectedTickets.get(ticketTypeId) || 0;
  }

  function calculateTotal(): number {
    let total = 0;
    for (const [ticketTypeId, quantity] of selectedTickets) {
      const ticketType = event?.ticketTypes?.find((t: any) => t.id === ticketTypeId);
      if (ticketType) {
        total += ticketType.price * quantity;
      }
    }
    return total;
  }

  async function bookTickets() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (selectedTickets.size === 0) {
      alert('Please select at least one ticket');
      return;
    }

    bookingLoading = true;
    try {
      const tickets = [];
      for (const [ticketTypeId, quantity] of selectedTickets) {
        tickets.push({ ticketTypeId, quantity });
      }

      const response = await fetch(`http://localhost:9030/api/events/${event.id}/book`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          userId: $userStore.user?.id,
          tickets
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Tickets booked successfully! Booking ID: ' + data.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getCategoryIcon(category: string): string {
    const icons: { [key: string]: string } = {
      'CONCERT': '🎵', 'CONFERENCE': '💼', 'SPORTS': '⚽',
      'THEATER': '🎭', 'EXHIBITION': '🎨', 'WORKSHOP': '🛠️',
      'FESTIVAL': '🎪', 'NETWORKING': '🤝', 'OTHER': '📅'
    };
    return icons[category] || '📅';
  }
</script>

<svelte:head>
  <title>{event?.title || 'Event Details'} - Travel App</title>
</svelte:head>

<div class="event-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/events" class="back-link">← Back to Events</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading event details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadEventDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if event}
    <div class="event-hero">
      <div class="hero-image">
        {#if event.images && event.images.length > 0}
          <img src={event.images[0]} alt={event.title} />
        {:else}
          <div class="placeholder-image">{getCategoryIcon(event.category)}</div>
        {/if}
        <div class="hero-overlay">
          <div class="hero-content">
            <span class="category">{getCategoryIcon(event.category)} {event.category}</span>
            <h1>{event.title}</h1>
            <p class="venue">📍 {event.venue?.name}, {event.venue?.city}</p>
            <p class="datetime">📅 {formatDate(event.startDate)}</p>
          </div>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>About This Event</h2>
            <p class="description">{event.description}</p>
            <div class="event-stats">
              <div class="stat">
                <span class="stat-label">Duration</span>
                <span class="stat-value">{Math.ceil((new Date(event.endDate).getTime() - new Date(event.startDate).getTime()) / (1000 * 60 * 60))} hours</span>
              </div>
              <div class="stat">
                <span class="stat-label">Capacity</span>
                <span class="stat-value">{event.capacity} people</span>
              </div>
              <div class="stat">
                <span class="stat-label">Organizer</span>
                <span class="stat-value">{event.organizer?.name}</span>
              </div>
            </div>
          </section>

          {#if schedule.length > 0}
            <section class="section">
              <h2>Schedule</h2>
              <div class="schedule-list">
                {#each schedule as item}
                  <div class="schedule-item">
                    <span class="time">{item.time}</span>
                    <span class="activity">{item.activity}</span>
                  </div>
                {/each}
              </div>
            </section>
          {/if}

          <section class="section">
            <h2>Venue Information</h2>
            <div class="venue-details">
              <p><strong>{event.venue?.name}</strong></p>
              <p>📍 {event.venue?.address}</p>
              <p>Capacity: {event.venue?.capacity} people</p>
              {#if event.venue?.amenities}
                <div class="amenities">
                  {#each event.venue.amenities as amenity}
                    <span class="amenity">{amenity}</span>
                  {/each}
                </div>
              {/if}
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
                    </div>
                    <p>{review.comment}</p>
                  </div>
                {/each}
              </div>
            </section>
          {/if}
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Select Tickets</h3>
            
            <div class="ticket-types">
              {#each event.ticketTypes || [] as ticketType}
                <div class="ticket-option">
                  <div class="ticket-info">
                    <div class="ticket-name">{ticketType.name}</div>
                    <div class="ticket-price">${ticketType.price}</div>
                    <div class="ticket-desc">{ticketType.description}</div>
                    <div class="ticket-remaining">{ticketType.quantity - ticketType.sold} remaining</div>
                  </div>
                  <div class="ticket-quantity">
                    <button 
                      class="qty-btn" 
                      on:click={() => updateTicketQuantity(ticketType.id, getTicketQuantity(ticketType.id) - 1)}
                      disabled={getTicketQuantity(ticketType.id) === 0}
                    >-</button>
                    <span class="qty-value">{getTicketQuantity(ticketType.id)}</span>
                    <button 
                      class="qty-btn" 
                      on:click={() => updateTicketQuantity(ticketType.id, getTicketQuantity(ticketType.id) + 1)}
                      disabled={getTicketQuantity(ticketType.id) >= ticketType.maxPerOrder || getTicketQuantity(ticketType.id) >= (ticketType.quantity - ticketType.sold)}
                    >+</button>
                  </div>
                </div>
              {/each}
            </div>

            {#if selectedTickets.size > 0}
              <div class="total-section">
                <div class="total-row">
                  <span>Total</span>
                  <span class="total-price">${calculateTotal().toFixed(2)}</span>
                </div>
              </div>
            {/if}

            <button 
              class="book-btn" 
              disabled={selectedTickets.size === 0 || bookingLoading}
              on:click={bookTickets}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Get Tickets
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .event-detail-page {
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

  .event-hero {
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
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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

  .category {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .hero-content h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
  }

  .venue, .datetime {
    margin: 0.25rem 0;
    font-size: 1rem;
    opacity: 0.9;
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

  .event-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
  }

  .stat {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .stat-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .stat-value {
    font-weight: 600;
    color: #1e293b;
  }

  .schedule-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .schedule-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .time {
    font-weight: 600;
    color: #7c3aed;
    min-width: 60px;
  }

  .venue-details p {
    margin: 0.25rem 0;
    color: #64748b;
  }

  .amenities {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
  }

  .amenity {
    background: #ede9fe;
    color: #7c3aed;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
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
    margin-bottom: 0.5rem;
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

  .ticket-types {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .ticket-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .ticket-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .ticket-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .ticket-desc {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .ticket-remaining {
    font-size: 0.75rem;
    color: #059669;
  }

  .ticket-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .qty-btn:hover:not(:disabled) {
    background: #f8fafc;
  }

  .qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .qty-value {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
  }

  .total-section {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
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

    .event-hero {
      height: 300px;
    }

    .hero-content h1 {
      font-size: 1.75rem;
    }

    .event-stats {
      grid-template-columns: 1fr;
    }
  }
</style>
