<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Flight {
    id: string;
    flightNumber: string;
    airline: string;
    airlineLogo?: string;
    departure: {
      airport: string;
      city: string;
      time: string;
      date: string;
    };
    arrival: {
      airport: string;
      city: string;
      time: string;
      date: string;
    };
    duration: string;
    price: number;
    class: 'economy' | 'business' | 'first';
    stops: number;
    stopover?: string;
  }

  interface Booking {
    id: string;
    flightId: string;
    userId: string;
    passengerCount: number;
    totalPrice: number;
    status: 'pending' | 'confirmed' | 'cancelled';
    bookingDate: string;
  }

  // Stores
  const flights = writable<Flight[]>([]);
  const loading = writable(false);
  const error = writable<string | null>(null);

  // State
  let origin = '';
  let destination = '';
  let departureDate = '';
  let returnDate = '';
  let passengerCount = 1;
  let flightClass: 'economy' | 'business' | 'first' = 'economy';
  let tripType: 'one-way' | 'round-trip' = 'one-way';
  
  let selectedFlight: Flight | null = null;
  let showBookingModal = false;
  let bookingStep: 'search' | 'results' | 'booking' | 'confirmation' = 'search';

  onMount(() => {
    // Set default dates
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    departureDate = today.toISOString().split('T')[0];
    returnDate = tomorrow.toISOString().split('T')[0];
  });

  async function searchFlights() {
    if (!origin || !destination || !departureDate) {
      error.set('Vui lòng điền đầy đủ thông tin');
      return;
    }

    loading.set(true);
    error.set(null);

    try {
      const params = new URLSearchParams({
        origin,
        destination,
        departureDate,
        passengers: passengerCount.toString(),
        class: flightClass
      });

      if (tripType === 'round-trip' && returnDate) {
        params.append('returnDate', returnDate);
      }

      const response = await fetch(`/api/booking/flights/search?${params}`);
      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      flights.set(data.flights || []);
      bookingStep = 'results';
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Search failed');
    } finally {
      loading.set(false);
    }
  }

  function selectFlight(flight: Flight) {
    selectedFlight = flight;
    showBookingModal = true;
  }

  async function confirmBooking() {
    if (!selectedFlight) return;

    loading.set(true);

    try {
      const response = await fetch('/api/booking/flights/book', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          flightId: selectedFlight.id,
          passengerCount,
          flightClass
        })
      });

      if (!response.ok) throw new Error('Booking failed');

      showBookingModal = false;
      bookingStep = 'confirmation';
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Booking failed');
    } finally {
      loading.set(false);
    }
  }

  function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(price);
  }

  function getAirlineLogo(airline: string): string {
    const logos: Record<string, string> = {
      'Vietnam Airlines': '✈️',
      'VietJet Air': '🔴',
      'Bamboo Airways': '🎋',
      'Pacific Airlines': '⭐'
    };
    return logos[airline] || '✈️';
  }

  function getClassLabel(cls: string): string {
    const labels: Record<string, string> = {
      economy: 'Phổ thông',
      business: 'Thương gia',
      first: 'Hạng nhất'
    };
    return labels[cls] || cls;
  }
</script>

<div class="flight-booking-page">
  <div class="booking-container">
    <!-- Header -->
    <div class="booking-header">
      <h1>✈️ Đặt vé máy bay</h1>
      <p class="subtitle">Tìm chuyến bay tốt nhất với giá ưu đãi</p>
    </div>

    {#if $error}
      <div class="error-message">{$error}</div>
    {/if}

    <!-- Search Form -->
    {#if bookingStep === 'search'}
      <div class="search-form">
        <div class="trip-type">
          <button 
            class="trip-btn {tripType === 'one-way' ? 'active' : ''}"
            on:click={() => tripType = 'one-way'}
          >
            Một chiều
          </button>
          <button 
            class="trip-btn {tripType === 'round-trip' ? 'active' : ''}"
            on:click={() => tripType = 'round-trip'}
          >
            Khứ hồi
          </button>
        </div>

        <div class="search-grid">
          <div class="search-field">
            <label>📍 Điểm đi</label>
            <input 
              type="text" 
              bind:value={origin}
              placeholder="Hà Nội (HAN)"
            />
          </div>
          
          <div class="search-field">
            <label>📍 Điểm đến</label>
            <input 
              type="text" 
              bind:value={destination}
              placeholder="TP.HCM (SGN)"
            />
          </div>
          
          <div class="search-field">
            <label>📅 Ngày đi</label>
            <input type="date" bind:value={departureDate} />
          </div>
          
          {#if tripType === 'round-trip'}
            <div class="search-field">
              <label>📅 Ngày về</label>
              <input type="date" bind:value={returnDate} />
            </div>
          {/if}
          
          <div class="search-field">
            <label>👥 Hành khách</label>
            <select bind:value={passengerCount}>
              {#each [1, 2, 3, 4, 5, 6, 7, 8, 9] as count}
                <option value={count}>{count} hành khách</option>
              {/each}
            </select>
          </div>
          
          <div class="search-field">
            <label>💺 Hạng vé</label>
            <select bind:value={flightClass}>
              <option value="economy">Phổ thông</option>
              <option value="business">Thương gia</option>
              <option value="first">Hạng nhất</option>
            </select>
          </div>
        </div>
        
        <button 
          class="btn-search" 
          on:click={searchFlights}
          disabled={$loading}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang tìm...
          {:else}
            🔍 Tìm chuyến bay
          {/if}
        </button>
      </div>
    {/if}

    <!-- Results -->
    {#if bookingStep === 'results'}
      <div class="results-section">
        <div class="results-header">
          <button class="btn-back" on:click={() => bookingStep = 'search'}>
            ← Quay lại
          </button>
          <h2>Kết quả tìm kiếm</h2>
          <p>{origin} → {destination}</p>
        </div>

        {#if $flights.length === 0}
          <div class="empty">Không tìm thấy chuyến bay nào</div>
        {:else}
          <div class="flights-list">
            {#each $flights as flight}
              <div class="flight-card">
                <div class="flight-airline">
                  <span class="airline-logo">{getAirlineLogo(flight.airline)}</span>
                  <span class="airline-name">{flight.airline}</span>
                </div>

                <div class="flight-route">
                  <div class="departure">
                    <span class="time">{flight.departure.time}</span>
                    <span class="city">{flight.departure.city}</span>
                    <span class="airport">{flight.departure.airport}</span>
                  </div>

                  <div class="flight-info">
                    <span class="duration">{flight.duration}</span>
                    <div class="flight-line">
                      {#if flight.stops === 0}
                        <span class="direct">Bay thẳng</span>
                      {:else}
                        <span class="stops">{flight.stops} điểm dừng</span>
                      {/if}
                    </div>
                  </div>

                  <div class="arrival">
                    <span class="time">{flight.arrival.time}</span>
                    <span class="city">{flight.arrival.city}</span>
                    <span class="airport">{flight.arrival.airport}</span>
                  </div>
                </div>

                <div class="flight-price">
                  <span class="price">{formatPrice(flight.price)}</span>
                  <span class="class">{getClassLabel(flight.class)}</span>
                  <button 
                    class="btn-select"
                    on:click={() => selectFlight(flight)}
                  >
                    Chọn
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}

    <!-- Confirmation -->
    {#if bookingStep === 'confirmation'}
      <div class="confirmation-section">
        <div class="success-icon">✅</div>
        <h2>Đặt vé thành công!</h2>
        <p>Cảm ơn bạn đã đặt vé. Thông tin chi tiết đã được gửi đến email của bạn.</p>
        <button class="btn-primary" on:click={() => bookingStep = 'search'}>
          Đặt vé khác
        </button>
      </div>
    {/if}
  </div>
</div>

<!-- Booking Modal -->
{#if showBookingModal && selectedFlight}
  <div class="modal-overlay" on:click={() => showBookingModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Xác nhận đặt vé</h2>
        <button class="btn-close" on:click={() => showBookingModal = false}>✕</button>
      </div>

      <div class="modal-body">
        <div class="flight-summary">
          <div class="summary-airline">
            <span class="logo">{getAirlineLogo(selectedFlight.airline)}</span>
            <span>{selectedFlight.airline}</span>
          </div>
          
          <div class="summary-route">
            <div class="route-point">
              <strong>{selectedFlight.departure.time}</strong>
              <span>{selectedFlight.departure.city}</span>
            </div>
            <span class="route-arrow">→</span>
            <div class="route-point">
              <strong>{selectedFlight.arrival.time}</strong>
              <span>{selectedFlight.arrival.city}</span>
            </div>
          </div>

          <div class="summary-details">
            <p><strong>Chuyến bay:</strong> {selectedFlight.flightNumber}</p>
            <p><strong>Ngày:</strong> {selectedFlight.departure.date}</p>
            <p><strong>Thời gian:</strong> {selectedFlight.duration}</p>
            <p><strong>Hạng vé:</strong> {getClassLabel(selectedFlight.class)}</p>
            <p><strong>Số hành khách:</strong> {passengerCount}</p>
          </div>
        </div>

        <div class="price-summary">
          <div class="price-row">
            <span>Giá vé ({passengerCount} x {formatPrice(selectedFlight.price)})</span>
            <span>{formatPrice(selectedFlight.price * passengerCount)}</span>
          </div>
          <div class="price-row total">
            <span>Tổng cộng</span>
            <span class="total-price">{formatPrice(selectedFlight.price * passengerCount)}</span>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" on:click={() => showBookingModal = false}>
          Hủy
        </button>
        <button 
          class="btn-primary" 
          on:click={confirmBooking}
          disabled={$loading}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang xử lý...
          {:else}
            Xác nhận đặt vé
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .flight-booking-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
  }

  .booking-container {
    max-width: 1000px;
    margin: 0 auto;
  }

  .booking-header {
    text-align: center;
    color: white;
    margin-bottom: 2rem;
  }

  .booking-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .subtitle {
    opacity: 0.9;
  }

  .error-message {
    background: #fee2e2;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .search-form {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  }

  .trip-type {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .trip-btn {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
  }

  .trip-btn.active {
    border-color: #667eea;
    background: #eff6ff;
    color: #667eea;
  }

  .search-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .search-field label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
  }

  .search-field input,
  .search-field select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
  }

  .btn-search {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.125rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .btn-search:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .results-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
  }

  .results-header {
    margin-bottom: 1.5rem;
  }

  .btn-back {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    margin-bottom: 0.5rem;
  }

  .flights-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .flight-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    gap: 1.5rem;
  }

  .flight-airline {
    width: 120px;
    text-align: center;
  }

  .airline-logo {
    font-size: 2rem;
    display: block;
  }

  .airline-name {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .flight-route {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .departure,
  .arrival {
    text-align: center;
  }

  .time {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
  }

  .city {
    display: block;
    font-weight: 600;
  }

  .airport {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .flight-info {
    text-align: center;
  }

  .duration {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .flight-line {
    margin-top: 0.25rem;
  }

  .direct {
    font-size: 0.75rem;
    color: #10b981;
  }

  .stops {
    font-size: 0.75rem;
    color: #f59e0b;
  }

  .flight-price {
    text-align: right;
  }

  .price {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #dc2626;
  }

  .class {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .btn-select {
    display: block;
    margin-top: 0.5rem;
    padding: 0.5rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }

  .confirmation-section {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
  }

  .success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .btn-primary {
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
  }

  .btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .flight-summary {
    margin-bottom: 1.5rem;
  }

  .summary-airline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
  }

  .logo {
    font-size: 1.5rem;
  }

  .summary-route {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .route-point {
    text-align: center;
  }

  .route-arrow {
    font-size: 1.5rem;
    color: #6b7280;
  }

  .summary-details p {
    margin: 0.25rem 0;
    color: #4b5563;
  }

  .price-summary {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 8px;
  }

  .price-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
  }

  .price-row.total {
    border-top: 2px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 700;
  }

  .total-price {
    color: #dc2626;
    font-size: 1.25rem;
  }

  .modal-footer {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
  }

  .btn-secondary {
    flex: 1;
    padding: 1rem;
    background: #f3f4f6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .empty {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
  }
</style>
