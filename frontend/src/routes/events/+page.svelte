<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { eventsService, type Event } from '$lib/services/events';

  let events: Event[] = [];
  let categories: string[] = [];
  let loading = true;
  let error = '';
  
  let searchParams = {
    query: '',
    category: '',
    city: ''
  };

  onMount(async () => {
    await loadEvents();
  });

  async function loadEvents() {
    loading = true;
    error = '';
    
    try {
      events = await eventsService.getEvents();
    } catch (err) {
      error = 'Failed to load events';
      console.error('Failed to load events:', err);
    }
    loading = false;
  }

  function handleSearch() {
    loadEvents();
  }

  function viewEventDetails(eventId: string) {
    goto(`/events/${eventId}`);
  }

  function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      weekday: 'short', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getCategoryIcon(category: string): string {
    const icons: { [key: string]: string } = {
      'CONCERT': '🎵',
      'CONFERENCE': '💼',
      'SPORTS': '⚽',
      'THEATER': '🎭',
      'EXHIBITION': '🎨',
      'WORKSHOP': '🛠️',
      'FESTIVAL': '🎪',
      'NETWORKING': '🤝',
      'OTHER': '📅'
    };
    return icons[category] || '📅';
  }
</script>

<svelte:head>
  <title>Events & Tickets - Travel Booking App</title>
</svelte:head>

<div class="events-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Discover Amazing Events</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="search-field">
        <label for="query">Search Events</label>
        <input
          id="query"
          type="text"
          bind:value={searchParams.query}
          placeholder="Concert, conference, sports..."
        />
      </div>
      
      <div class="search-field">
        <label for="category">Category</label>
        <select id="category" bind:value={searchParams.category}>
          <option value="">All Categories</option>
          {#each categories as category}
            <option value={category}>{category}</option>
          {/each}
        </select>
      </div>
      
      <div class="search-field">
        <label for="city">City</label>
        <input
          id="city"
          type="text"
          bind:value={searchParams.city}
          placeholder="City name"
        />
      </div>
      
      <button class="search-btn" on:click={handleSearch}>
        Find Events
      </button>
    </div>
  </div>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Finding exciting events...</p>
      </div>
    {:else if error}
      <div class="error">
        <p>{error}</p>
        <button class="retry-btn" on:click={loadEvents}>Try Again</button>
      </div>
    {:else if events.length === 0}
      <div class="empty-state">
        <div class="empty-icon">🎫</div>
        <h3>No events found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    {:else}
      <div class="results-header">
        <h2>{events.length} Events Found</h2>
      </div>
      
      <div class="events-grid">
        {#each events as event}
          <div class="event-card" on:click={() => viewEventDetails(event.id)}>
            <div class="event-image">
              {#if event.images && event.images.length > 0}
                <img src={event.images[0]} alt={event.title} />
              {:else}
                <div class="placeholder-image">{getCategoryIcon(event.category)}</div>
              {/if}
              <div class="event-category">{getCategoryIcon(event.category)} {event.category}</div>
            </div>
            
            <div class="event-info">
              <h3 class="event-title">{event.title}</h3>
              <p class="event-date">📅 {formatDate(event.startDate)}</p>
              <p class="event-venue">📍 {event.venue.name}, {event.venue.city}</p>
              <p class="event-description">{event.description}</p>
              
              <div class="event-footer">
                <div class="event-price">
                  <span class="price-label">From</span>
                  <span class="price-value">${Math.min(...event.ticketTypes.map((t: any) => t.price))}</span>
                </div>
                <div class="event-tickets">
                  <span class="tickets-left">{event.capacity - event.soldTickets} tickets left</span>
                </div>
                <button class="book-btn">Get Tickets</button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </main>
</div>

<style>
  .events-page {
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

  .events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
  }

  .event-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  }

  .event-image {
    position: relative;
    height: 200px;
    background: #e2e8f0;
  }

  .event-image img {
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
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  }

  .event-category {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
  }

  .event-info {
    padding: 1.5rem;
  }

  .event-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .event-date {
    margin: 0 0 0.25rem 0;
    color: #7c3aed;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .event-venue {
    margin: 0 0 0.75rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .event-description {
    margin: 0 0 1rem 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .event-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }

  .price-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  .price-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .tickets-left {
    font-size: 0.875rem;
    color: #059669;
    font-weight: 500;
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

    .events-grid {
      grid-template-columns: 1fr;
    }

    .event-footer {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
