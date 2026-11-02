<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';
  import { flightsService, type Flight } from '$lib/services/flights';

  let flight: Flight | null = null;
  let seats: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let selectedSeat: string | null = null;
  let passengers = 1;
  let seatClass = 'ECONOMY';

  onMount(async () => {
    const flightId = $page.params.id;
    if (flightId) {
      await loadFlightDetails(flightId);
    }
  });

  async function loadFlightDetails(flightId: string) {
    try {
      flight = await flightsService.getFlight(flightId);
    } catch (err) {
      error = 'Failed to load flight details';
    }
  }

  async function bookFlight() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!selectedSeat) {
      alert('Please select a seat');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch('http://localhost:9030/api/flight-bookings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': $userStore.user?.id || 'anonymous'
        },
        body: JSON.stringify({
          flightId: flight.id,
          seatIds: [selectedSeat],
          passengerDetails: [{
            firstName: $userStore.user?.name?.split(' ')[0] || 'Guest',
            lastName: $userStore.user?.name?.split(' ')[1] || '',
            email: $userStore.user?.email || '',
            phone: '',
            dateOfBirth: ''
          }],
          contactEmail: $userStore.user?.email || '',
          contactPhone: '',
          specialRequests: ''
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Flight booked successfully! Booking ID: ' + data.booking.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
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

  function getSeatClassColor(seatClass: string): string {
    switch(seatClass) {
      case 'FIRST': return '#fbbf24';
      case 'BUSINESS': return '#a78bfa';
      case 'PREMIUM_ECONOMY': return '#60a5fa';
      default: return '#34d399';
    }
  }
</script>

<svelte:head>
  <title>{flight ? `${flight.airline?.name} - ${flight.flightNumber}` : 'Flight Details'} - Travel App</title>
</svelte:head>

<div class="flight-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/flights" class="back-link">← Back to Flights</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading flight details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadFlightDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if flight}
    <div class="flight-hero">
      <div class="hero-content">
        <div class="airline-info">
          <div class="airline-logo">✈️</div>
          <div>
            <h1>{flight.airline?.name}</h1>
            <p class="flight-number">Flight {flight.flightNumber}</p>
          </div>
        </div>
        
        <div class="flight-route">
          <div class="route-point">
            <div class="city">{flight.departureAirport?.city}</div>
            <div class="airport">{flight.departureAirport?.code}</div>
            <div class="time">{formatDate(flight.departureTime)}</div>
          </div>
          
          <div class="route-line">
            <div class="duration">{formatDuration(flight.duration)}</div>
            <div class="line"></div>
            <div class="plane-icon">✈️</div>
          </div>
          
          <div class="route-point">
            <div class="city">{flight.arrivalAirport?.city}</div>
            <div class="airport">{flight.arrivalAirport?.code}</div>
            <div class="time">{formatDate(flight.arrivalTime)}</div>
          </div>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>Flight Details</h2>
            <div class="details-grid">
              <div class="detail-item">
                <label>Aircraft</label>
                <value>{flight.aircraft}</value>
              </div>
              <div class="detail-item">
                <label>Duration</label>
                <value>{formatDuration(flight.duration)}</value>
              </div>
              <div class="detail-item">
                <label>Class</label>
                <value>{seatClass}</value>
              </div>
              <div class="detail-item">
                <label>Baggage</label>
                <value>{flight.baggageAllowance?.checkedBags} checked bags</value>
              </div>
            </div>
          </section>

          <section class="section">
            <h2>Seat Selection</h2>
            <div class="seat-class-selector">
              <button class:active={seatClass === 'ECONOMY'} on:click={() => {seatClass = 'ECONOMY'; loadSeats(flight.id);}}>Economy</button>
              <button class:active={seatClass === 'BUSINESS'} on:click={() => {seatClass = 'BUSINESS'; loadSeats(flight.id);}}>Business</button>
              <button class:active={seatClass === 'FIRST'} on:click={() => {seatClass = 'FIRST'; loadSeats(flight.id);}}>First</button>
            </div>
            
            <div class="seat-map">
              <div class="seat-legend">
                <div class="legend-item"><div class="seat available"></div> Available</div>
                <div class="legend-item"><div class="seat selected"></div> Selected</div>
                <div class="legend-item"><div class="seat occupied"></div> Occupied</div>
              </div>
              
              <div class="seats-grid">
                {#each seats as seat}
                  <button 
                    class="seat" 
                    class:available={seat.isAvailable}
                    class:occupied={!seat.isAvailable}
                    class:selected={selectedSeat === seat.id}
                    disabled={!seat.isAvailable}
                    on:click={() => selectedSeat = seat.id}
                    style="--seat-color: {getSeatClassColor(seat.seatClass)}"
                  >
                    {seat.seatNumber}
                  </button>
                {/each}
              </div>
            </div>
          </section>
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Booking Summary</h3>
            
            <div class="summary-item">
              <span>Flight</span>
              <span>{flight.flightNumber}</span>
            </div>
            
            <div class="summary-item">
              <span>Route</span>
              <span>{flight.departureAirport?.code} → {flight.arrivalAirport?.code}</span>
            </div>
            
            <div class="summary-item">
              <span>Class</span>
              <span>{seatClass}</span>
            </div>
            
            {#if selectedSeat}
              {@const seat = seats.find(s => s.id === selectedSeat)}
              <div class="summary-item">
                <span>Seat</span>
                <span>{seat?.seatNumber}</span>
              </div>
              <div class="summary-item total">
                <span>Total Price</span>
                <span>${seat?.price}</span>
              </div>
            {/if}

            <button 
              class="book-btn" 
              disabled={!selectedSeat || bookingLoading}
              on:click={bookFlight}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Book Flight
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .flight-detail-page {
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

  .flight-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
  }

  .airline-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .airline-logo {
    font-size: 3rem;
  }

  .airline-info h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .flight-number {
    margin: 0.25rem 0 0 0;
    opacity: 0.9;
  }

  .flight-route {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 3rem;
  }

  .route-point {
    text-align: center;
  }

  .city {
    font-size: 1.5rem;
    font-weight: 600;
  }

  .airport {
    font-size: 1.25rem;
    opacity: 0.9;
  }

  .time {
    font-size: 0.875rem;
    opacity: 0.8;
    margin-top: 0.5rem;
  }

  .route-line {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 200px;
  }

  .duration {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .line {
    width: 100%;
    height: 2px;
    background: rgba(255, 255, 255, 0.5);
  }

  .plane-icon {
    font-size: 1.5rem;
    margin-top: -0.75rem;
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

  .details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }

  .detail-item label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .detail-item value {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
  }

  .seat-class-selector {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .seat-class-selector button {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .seat-class-selector button.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .seat-legend {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    justify-content: center;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .seats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.5rem;
    max-width: 400px;
    margin: 0 auto;
  }

  .seat {
    aspect-ratio: 1;
    border: none;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .seat.available {
    background: var(--seat-color, #34d399);
    color: white;
  }

  .seat.occupied {
    background: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
  }

  .seat.selected {
    background: #667eea;
    color: white;
    transform: scale(1.1);
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

  .summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .summary-item.total {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    padding-top: 0.75rem;
    border-top: 1px solid #e2e8f0;
    margin-top: 0.75rem;
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
    margin-top: 1.5rem;
    transition: all 0.2s;
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
    display: inline-block;
    margin-right: 0.5rem;
  }

  @media (max-width: 968px) {
    .content-grid {
      grid-template-columns: 1fr;
    }

    .booking-sidebar {
      position: static;
    }

    .flight-route {
      flex-direction: column;
      gap: 1rem;
    }

    .route-line {
      transform: rotate(90deg);
      max-width: 100px;
    }
  }
</style>
