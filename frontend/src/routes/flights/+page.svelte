<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { flightsService, type Flight } from '$lib/services/flights';

  let flights: Flight[] = [];
  let loading = false;
  let error = '';
  let tripType = 'ONE_WAY';
  
  let searchParams = {
    departureCity: '',
    arrivalCity: '',
    departureDate: '',
    returnDate: '',
    passengers: 1,
    seatClass: 'ECONOMY'
  };

  const seatClasses = [
    { value: 'ECONOMY', label: 'Economy' },
    { value: 'PREMIUM_ECONOMY', label: 'Premium Economy' },
    { value: 'BUSINESS', label: 'Business' },
    { value: 'FIRST', label: 'First Class' }
  ];

  async function searchFlights() {
    if (!searchParams.departureCity || !searchParams.arrivalCity || !searchParams.departureDate) {
      error = 'Please fill in all required fields';
      return;
    }

    loading = true;
    error = '';
    
    try {
      flights = await flightsService.searchFlights({
        origin: searchParams.departureCity,
        destination: searchParams.arrivalCity,
        departureDate: searchParams.departureDate,
        returnDate: tripType === 'ROUND_TRIP' ? searchParams.returnDate : undefined,
        passengers: searchParams.passengers,
        class: searchParams.seatClass
      });
    } catch (err) {
      error = 'Failed to search flights. Please try again.';
      console.error(err);
    }
    loading = false;
  }

  function viewFlightDetails(flightId: string) {
    goto(`/flights/${flightId}`);
  }

  function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
  }

  function formatTime(dateTimeStr: string): string {
    return new Date(dateTimeStr).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric'
    });
  }
</script>

<svelte:head>
  <title>Search Flights - Travel Booking App</title>
</svelte:head>

<div class="flights-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Find Your Flight</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="trip-type">
        <button class:active={tripType === 'ONE_WAY'} on:click={() => tripType = 'ONE_WAY'}>
          One Way
        </button>
        <button class:active={tripType === 'ROUND_TRIP'} on:click={() => tripType = 'ROUND_TRIP'}>
          Round Trip
        </button>
      </div>

      <div class="search-fields">
        <div class="search-field">
          <label for="from">From</label>
          <input
            id="from"
            type="text"
            bind:value={searchParams.departureCity}
            placeholder="Departure city"
          />
        </div>

        <div class="search-field">
          <label for="to">To</label>
          <input
            id="to"
            type="text"
            bind:value={searchParams.arrivalCity}
            placeholder="Arrival city"
          />
        </div>

        <div class="search-field">
          <label for="depart">Depart</label>
          <input
            id="depart"
            type="date"
            bind:value={searchParams.departureDate}
          />
        </div>

        {#if tripType === 'ROUND_TRIP'}
          <div class="search-field">
            <label for="return">Return</label>
            <input
              id="return"
              type="date"
              bind:value={searchParams.returnDate}
            />
          </div>
        {/if}

        <div class="search-field">
          <label for="passengers">Passengers</label>
          <select id="passengers" bind:value={searchParams.passengers}>
            {#each [1, 2, 3, 4, 5, 6, 7, 8] as num}
              <option value={num}>{num} Passenger{num > 1 ? 's' : ''}</option>
            {/each}
          </select>
        </div>

        <div class="search-field">
          <label for="class">Class</label>
          <select id="class" bind:value={searchParams.seatClass}>
            {#each seatClasses as cls}
              <option value={cls.value}>{cls.label}</option>
            {/each}
          </select>
        </div>
      </div>

      <button class="search-btn" on:click={searchFlights} disabled={loading}>
        {loading ? 'Searching...' : 'Search Flights'}
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
        <p>Searching for flights...</p>
      </div>
    {:else if flights.length > 0}
      <div class="results-header">
        <h2>{flights.length} Flights Found</h2>
      </div>

      <div class="flights-list">
        {#each flights as flightWrapper}
          {@const flight = flightWrapper.flight}
          {@const price = flightWrapper.prices[searchParams.seatClass]}
          <div class="flight-card" on:click={() => viewFlightDetails(flight.id)}>
            <div class="flight-header">
              <div class="airline">
                <span class="airline-name">{flight.airline.name}</span>
                <span class="flight-number">{flight.flightNumber}</span>
              </div>
              <div class="price">${price}</div>
            </div>

            <div class="flight-route">
              <div class="departure">
                <div class="time">{formatTime(flight.departureTime)}</div>
                <div class="city">{flight.departureAirport.city}</div>
                <div class="code">{flight.departureAirport.code}</div>
              </div>

              <div class="flight-info">
                <div class="duration">{formatDuration(flight.duration)}</div>
                <div class="arrow">→</div>
                <div class="stops">{flight.stops === 0 ? 'Non-stop' : `${flight.stops} stop${flight.stops > 1 ? 's' : ''}`}</div>
              </div>

              <div class="arrival">
                <div class="time">{formatTime(flight.arrivalTime)}</div>
                <div class="city">{flight.arrivalAirport.city}</div>
                <div class="code">{flight.arrivalAirport.code}</div>
              </div>
            </div>

            <div class="flight-footer">
              <div class="aircraft">{flight.aircraft}</div>
              <div class="seats-available">
                {flightWrapper.availableSeats[searchParams.seatClass] || 0} seats left
              </div>
            </div>
          </div>
        {/each}
      </div>
    {:else if !loading && flights.length === 0 && error === ''}
      <div class="empty-state">
        <div class="empty-icon">✈️</div>
        <h3>Search for flights</h3>
        <p>Enter your travel details above to find available flights</p>
      </div>
    {/if}
  </main>
</div>

<style>
  .flights-page {
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
  }

  .trip-type {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .trip-type button {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
  }

  .trip-type button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
  }

  .search-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
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
    padding: 0.875rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
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

  .flights-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .flight-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .flight-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  }

  .flight-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .airline {
    display: flex;
    flex-direction: column;
  }

  .airline-name {
    font-weight: 600;
    color: #1e293b;
  }

  .flight-number {
    font-size: 0.875rem;
    color: #64748b;
  }

  .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
  }

  .flight-route {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .departure,
  .arrival {
    text-align: center;
  }

  .time {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .city {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .code {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  .flight-info {
    text-align: center;
    flex: 1;
  }

  .duration {
    font-size: 0.875rem;
    color: #64748b;
  }

  .arrow {
    font-size: 1.5rem;
    color: #94a3b8;
    margin: 0.25rem 0;
  }

  .stops {
    font-size: 0.75rem;
    color: #059669;
  }

  .flight-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .aircraft {
    font-size: 0.875rem;
    color: #64748b;
  }

  .seats-available {
    font-size: 0.875rem;
    color: #ef4444;
    font-weight: 500;
  }

  @media (max-width: 768px) {
    .header,
    .search-section {
      padding: 1rem;
    }

    .search-fields {
      grid-template-columns: 1fr;
    }

    .main-content {
      padding: 1rem;
    }

    .flight-route {
      flex-direction: column;
      gap: 1rem;
    }

    .flight-info {
      order: -1;
    }
  }
</style>