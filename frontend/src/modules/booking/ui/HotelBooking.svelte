<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Hotel {
    id: string;
    name: string;
    description: string;
    starRating: number;
    address: string;
    city: string;
    country: string;
    amenities: string[];
    images: string[];
    basePrice: number;
  }

  interface RoomType {
    id: string;
    name: string;
    description: string;
    maxOccupancy: number;
    basePrice: number;
    amenities: string[];
    images: string[];
  }

  interface Booking {
    id: string;
    hotelId: string;
    roomTypeId: string;
    checkInDate: string;
    checkOutDate: string;
    guestCount: number;
    totalPrice: number;
    status: 'pending' | 'confirmed' | 'cancelled';
  }

  // Stores
  const hotels = writable<Hotel[]>([]);
  const roomTypes = writable<RoomType[]>([]);
  const loading = writable(false);
  const error = writable<string | null>(null);

  // State
  let searchQuery = '';
  let selectedCity = '';
  let checkInDate = '';
  let checkOutDate = '';
  let guestCount = 2;
  let selectedHotel: Hotel | null = null;
  let selectedRoom: RoomType | null = null;
  let showBookingModal = false;
  let bookingStep: 'search' | 'rooms' | 'details' | 'confirmation' = 'search';

  onMount(() => {
    loadHotels();
    
    // Set default dates
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    checkInDate = today.toISOString().split('T')[0];
    checkOutDate = tomorrow.toISOString().split('T')[0];
  });

  async function loadHotels() {
    loading.set(true);
    error.set(null);
    
    try {
      const response = await fetch('/api/booking/hotels');
      if (!response.ok) throw new Error('Failed to load hotels');
      
      const data = await response.json();
      hotels.set(data.hotels || data);
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      loading.set(false);
    }
  }

  async function searchHotels() {
    loading.set(true);
    error.set(null);
    
    try {
      const params = new URLSearchParams();
      if (selectedCity) params.append('city', selectedCity);
      if (checkInDate) params.append('checkIn', checkInDate);
      if (checkOutDate) params.append('checkOut', checkOutDate);
      params.append('guests', guestCount.toString());
      
      const response = await fetch(`/api/booking/hotels/search?${params}`);
      if (!response.ok) throw new Error('Search failed');
      
      const data = await response.json();
      hotels.set(data.hotels || data);
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Search failed');
    } finally {
      loading.set(false);
    }
  }

  async function loadRoomTypes(hotelId: string) {
    loading.set(true);
    
    try {
      const response = await fetch(`/api/booking/hotels/${hotelId}/rooms?checkIn=${checkInDate}&checkOut=${checkOutDate}`);
      if (!response.ok) throw new Error('Failed to load rooms');
      
      const data = await response.json();
      roomTypes.set(data.roomTypes || data);
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Failed to load rooms');
    } finally {
      loading.set(false);
    }
  }

  function selectHotel(hotel: Hotel) {
    selectedHotel = hotel;
    loadRoomTypes(hotel.id);
    bookingStep = 'rooms';
  }

  function selectRoom(room: RoomType) {
    selectedRoom = room;
    showBookingModal = true;
  }

  function calculateNights(): number {
    if (!checkInDate || !checkOutDate) return 1;
    const checkIn = new Date(checkInDate);
    const checkOut = new Date(checkOutDate);
    const diffTime = Math.abs(checkOut.getTime() - checkIn.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  }

  function calculateTotalPrice(): number {
    if (!selectedRoom) return 0;
    return selectedRoom.basePrice * calculateNights();
  }

  async function confirmBooking() {
    if (!selectedHotel || !selectedRoom) return;
    
    loading.set(true);
    
    try {
      const response = await fetch('/api/booking/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          hotelId: selectedHotel.id,
          roomTypeId: selectedRoom.id,
          checkInDate,
          checkOutDate,
          guestCount
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

  function renderStars(rating: number): string {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
  }
</script>

<div class="hotel-booking-page">
  <div class="booking-container">
    <!-- Header -->
    <div class="booking-header">
      <h1>🏨 Đặt phòng khách sạn</h1>
      <p class="subtitle">Tìm và đặt phòng khách sạn tốt nhất</p>
    </div>

    <!-- Search Form -->
    {#if bookingStep === 'search'}
      <div class="search-form">
        <div class="search-row">
          <div class="search-field">
            <label>📍 Điểm đến</label>
            <input 
              type="text" 
              bind:value={selectedCity}
              placeholder="Nhập thành phố..."
            />
          </div>
          
          <div class="search-field">
            <label>📅 Nhận phòng</label>
            <input type="date" bind:value={checkInDate} />
          </div>
          
          <div class="search-field">
            <label>📅 Trả phòng</label>
            <input type="date" bind:value={checkOutDate} />
          </div>
          
          <div class="search-field">
            <label>👥 Số khách</label>
            <select bind:value={guestCount}>
              {#each [1, 2, 3, 4, 5, 6] as count}
                <option value={count}>{count} khách</option>
              {/each}
            </select>
          </div>
        </div>
        
        <button class="btn-search" on:click={searchHotels} disabled={$loading}>
          {#if $loading}
            <span class="spinner"></span>
            Đang tìm...
          {:else}
            🔍 Tìm kiếm
          {/if}
        </button>
      </div>

      <!-- Hotel List -->
      <div class="hotels-section">
        <h2>Kết quả tìm kiếm</h2>
        
        {#if $loading}
          <div class="loading">Đang tải...</div>
        {:else if $error}
          <div class="error">{$error}</div>
        {:else if $hotels.length === 0}
          <div class="empty">Không tìm thấy khách sạn nào</div>
        {:else}
          <div class="hotels-grid">
            {#each $hotels as hotel}
              <div class="hotel-card" on:click={() => selectHotel(hotel)} role="button" tabindex="0">
                <div class="hotel-image">
                  {#if hotel.images && hotel.images.length > 0}
                    <img src={hotel.images[0]} alt={hotel.name} />
                  {:else}
                    <div class="placeholder-image">🏨</div>
                  {/if}
                </div>
                
                <div class="hotel-info">
                  <div class="hotel-header">
                    <h3>{hotel.name}</h3>
                    <span class="stars">{renderStars(hotel.starRating)}</span>
                  </div>
                  
                  <p class="hotel-location">📍 {hotel.address}, {hotel.city}</p>
                  
                  <div class="hotel-amenities">
                    {#each hotel.amenities.slice(0, 4) as amenity}
                      <span class="amenity-tag">{amenity}</span>
                    {/each}
                  </div>
                  
                  <div class="hotel-footer">
                    <div class="hotel-price">
                      <span class="price">{formatPrice(hotel.basePrice)}</span>
                      <span class="per-night">/đêm</span>
                    </div>
                    <button class="btn-view">Xem phòng →</button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}

    <!-- Room Selection -->
    {#if bookingStep === 'rooms' && selectedHotel}
      <div class="rooms-section">
        <button class="btn-back" on:click={() => bookingStep = 'search'}>
          ← Quay lại
        </button>
        
        <div class="hotel-details">
          <h2>{selectedHotel.name}</h2>
          <p class="stars">{renderStars(selectedHotel.starRating)}</p>
          <p class="location">📍 {selectedHotel.address}, {selectedHotel.city}</p>
        </div>

        <h3>Chọn loại phòng</h3>
        
        {#if $loading}
          <div class="loading">Đang tải phòng...</div>
        {:else if $roomTypes.length === 0}
          <div class="empty">Không có phòng trống cho ngày đã chọn</div>
        {:else}
          <div class="rooms-list">
            {#each $roomTypes as room}
              <div class="room-card">
                <div class="room-image">
                  {#if room.images && room.images.length > 0}
                    <img src={room.images[0]} alt={room.name} />
                  {:else}
                    <div class="placeholder-image">🛏️</div>
                  {/if}
                </div>
                
                <div class="room-info">
                  <h4>{room.name}</h4>
                  <p class="room-description">{room.description}</p>
                  
                  <div class="room-features">
                    <span>👥 Tối đa {room.maxOccupancy} khách</span>
                    {#each room.amenities.slice(0, 3) as amenity}
                      <span>✓ {amenity}</span>
                    {/each}
                  </div>
                  
                  <div class="room-footer">
                    <div class="room-price">
                      <span class="price">{formatPrice(room.basePrice)}</span>
                      <span class="per-night">/đêm</span>
                    </div>
                    <button class="btn-book" on:click={() => selectRoom(room)}>
                      Đặt phòng
                    </button>
                  </div>
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
        <h2>Đặt phòng thành công!</h2>
        <p>Cảm ơn bạn đã đặt phòng. Thông tin chi tiết đã được gửi đến email của bạn.</p>
        <button class="btn-primary" on:click={() => bookingStep = 'search'}>
          Đặt phòng khác
        </button>
      </div>
    {/if}
  </div>
</div>

<!-- Booking Modal -->
{#if showBookingModal && selectedHotel && selectedRoom}
  <div class="modal-overlay" on:click={() => showBookingModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Xác nhận đặt phòng</h2>
        <button class="btn-close" on:click={() => showBookingModal = false}>✕</button>
      </div>

      <div class="modal-body">
        <div class="booking-summary">
          <div class="summary-item">
            <span>Khách sạn:</span>
            <strong>{selectedHotel.name}</strong>
          </div>
          <div class="summary-item">
            <span>Loại phòng:</span>
            <strong>{selectedRoom.name}</strong>
          </div>
          <div class="summary-item">
            <span>Nhận phòng:</span>
            <strong>{checkInDate}</strong>
          </div>
          <div class="summary-item">
            <span>Trả phòng:</span>
            <strong>{checkOutDate}</strong>
          </div>
          <div class="summary-item">
            <span>Số đêm:</span>
            <strong>{calculateNights()} đêm</strong>
          </div>
          <div class="summary-item">
            <span>Số khách:</span>
            <strong>{guestCount} khách</strong>
          </div>
        </div>

        <div class="price-summary">
          <div class="price-row">
            <span>Giá phòng ({calculateNights()} đêm x {formatPrice(selectedRoom.basePrice)})</span>
            <span>{formatPrice(calculateTotalPrice())}</span>
          </div>
          <div class="price-row total">
            <span>Tổng cộng</span>
            <span class="total-price">{formatPrice(calculateTotalPrice())}</span>
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
            Xác nhận đặt phòng
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .hotel-booking-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
  }

  .booking-container {
    max-width: 1200px;
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

  .search-form {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    margin-bottom: 2rem;
  }

  .search-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
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

  .hotels-section h2 {
    color: white;
    margin-bottom: 1rem;
  }

  .hotels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .hotel-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
  }

  .hotel-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }

  .hotel-image {
    height: 200px;
    overflow: hidden;
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
    background: #f3f4f6;
  }

  .hotel-info {
    padding: 1.25rem;
  }

  .hotel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .hotel-header h3 {
    margin: 0;
    font-size: 1.25rem;
  }

  .stars {
    color: #fbbf24;
  }

  .hotel-location {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
  }

  .hotel-amenities {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .amenity-tag {
    background: #f3f4f6;
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
    font-size: 0.75rem;
    color: #4b5563;
  }

  .hotel-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .hotel-price .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #dc2626;
  }

  .hotel-price .per-night {
    color: #6b7280;
    font-size: 0.875rem;
  }

  .btn-view {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
  }

  .rooms-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
  }

  .btn-back {
    background: none;
    border: none;
    color: #3b82f6;
    cursor: pointer;
    margin-bottom: 1rem;
  }

  .hotel-details {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
  }

  .rooms-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .room-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
  }

  .room-image {
    width: 200px;
    height: 150px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
  }

  .room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .room-info {
    flex: 1;
  }

  .room-info h4 {
    margin: 0 0 0.5rem;
  }

  .room-description {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .room-features {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 1rem;
  }

  .room-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .room-price .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #dc2626;
  }

  .btn-book {
    padding: 0.75rem 1.5rem;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
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
    max-height: 90vh;
    overflow-y: auto;
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

  .booking-summary {
    margin-bottom: 1.5rem;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
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

  .loading,
  .error,
  .empty {
    text-align: center;
    padding: 3rem;
    color: white;
  }

  .error {
    color: #fecaca;
  }
</style>
