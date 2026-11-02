<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Restaurant {
    id: string;
    name: string;
    cuisine: string;
    address: string;
    city: string;
    priceRange: number;
    rating: number;
    reviewCount: number;
    features: string[];
    images: string[];
    ambiance: string;
  }

  interface Reservation {
    id: string;
    restaurantId: string;
    date: string;
    time: string;
    partySize: number;
    status: 'pending' | 'confirmed' | 'cancelled';
    confirmationCode: string;
  }

  const restaurants = writable<Restaurant[]>([]);
  const loading = writable(false);
  const error = writable<string | null>(null);

  let searchQuery = '';
  let selectedCuisine = '';
  let selectedCity = '';
  let selectedDate = '';
  let selectedTime = '';
  let partySize = 2;
  let selectedRestaurant: Restaurant | null = null;
  let showReservationModal = false;
  let bookingStep: 'search' | 'results' | 'confirmation' = 'search';

  const cuisines = ['Việt Nam', 'Hàn Quốc', 'Nhật Bản', 'Ý', 'Trung Quốc', 'Thái Lan', 'Ấn Độ'];
  const cities = ['Hà Nội', 'TP.HCM', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ'];
  const timeSlots = ['11:00', '11:30', '12:00', '12:30', '13:00', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'];

  onMount(() => {
    const today = new Date();
    selectedDate = today.toISOString().split('T')[0];
  });

  async function searchRestaurants() {
    loading.set(true);
    error.set(null);

    try {
      const params = new URLSearchParams();
      if (searchQuery) params.append('query', searchQuery);
      if (selectedCuisine) params.append('cuisine', selectedCuisine);
      if (selectedCity) params.append('city', selectedCity);

      const response = await fetch(`/api/restaurants/search?${params}`);
      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      restaurants.set(data.restaurants || data);
      bookingStep = 'results';
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Search failed');
    } finally {
      loading.set(false);
    }
  }

  function selectRestaurant(restaurant: Restaurant) {
    selectedRestaurant = restaurant;
    showReservationModal = true;
  }

  async function confirmReservation() {
    if (!selectedRestaurant) return;

    loading.set(true);

    try {
      const response = await fetch('/api/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          restaurantId: selectedRestaurant.id,
          date: selectedDate,
          time: selectedTime,
          partySize
        })
      });

      if (!response.ok) throw new Error('Reservation failed');

      showReservationModal = false;
      bookingStep = 'confirmation';
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Reservation failed');
    } finally {
      loading.set(false);
    }
  }

  function renderPriceRange(price: number): string {
    return '💰'.repeat(price);
  }

  function renderStars(rating: number): string {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    return '★'.repeat(fullStars) + (hasHalfStar ? '½' : '') + '☆'.repeat(5 - fullStars - (hasHalfStar ? 1 : 0));
  }
</script>

<div class="restaurant-booking-page">
  <div class="booking-container">
    <div class="booking-header">
      <h1>🍽️ Đặt bàn nhà hàng</h1>
      <p class="subtitle">Khám phá và đặt bàn tại nhà hàng yêu thích</p>
    </div>

    {#if $error}
      <div class="error-message">{$error}</div>
    {/if}

    {#if bookingStep === 'search'}
      <div class="search-form">
        <div class="search-row">
          <div class="search-field">
            <label>🔍 Tìm kiếm</label>
            <input 
              type="text" 
              bind:value={searchQuery}
              placeholder="Tên nhà hàng, món ăn..."
            />
          </div>
          
          <div class="search-field">
            <label>🍜 Ẩm thực</label>
            <select bind:value={selectedCuisine}>
              <option value="">Tất cả</option>
              {#each cuisines as cuisine}
                <option value={cuisine}>{cuisine}</option>
              {/each}
            </select>
          </div>
          
          <div class="search-field">
            <label>📍 Thành phố</label>
            <select bind:value={selectedCity}>
              <option value="">Tất cả</option>
              {#each cities as city}
                <option value={city}>{city}</option>
              {/each}
            </select>
          </div>
        </div>
        
        <button 
          class="btn-search" 
          on:click={searchRestaurants}
          disabled={$loading}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang tìm...
          {:else}
            🔍 Tìm nhà hàng
          {/if}
        </button>
      </div>
    {/if}

    {#if bookingStep === 'results'}
      <div class="results-section">
        <div class="results-header">
          <button class="btn-back" on:click={() => bookingStep = 'search'}>
            ← Quay lại
          </button>
          <h2>Kết quả tìm kiếm</h2>
        </div>

        {#if $restaurants.length === 0}
          <div class="empty">Không tìm thấy nhà hàng nào</div>
        {:else}
          <div class="restaurants-grid">
            {#each $restaurants as restaurant}
              <div class="restaurant-card" on:click={() => selectRestaurant(restaurant)} role="button" tabindex="0">
                <div class="restaurant-image">
                  {#if restaurant.images && restaurant.images.length > 0}
                    <img src={restaurant.images[0]} alt={restaurant.name} />
                  {:else}
                    <div class="placeholder-image">🍽️</div>
                  {/if}
                </div>
                
                <div class="restaurant-info">
                  <div class="restaurant-header">
                    <h3>{restaurant.name}</h3>
                    <span class="cuisine-tag">{restaurant.cuisine}</span>
                  </div>
                  
                  <div class="rating">
                    <span class="stars">{renderStars(restaurant.rating)}</span>
                    <span class="rating-count">({restaurant.reviewCount} đánh giá)</span>
                  </div>
                  
                  <p class="location">📍 {restaurant.address}, {restaurant.city}</p>
                  
                  <div class="features">
                    {#each restaurant.features.slice(0, 3) as feature}
                      <span class="feature-tag">{feature}</span>
                    {/each}
                  </div>
                  
                  <div class="restaurant-footer">
                    <span class="price-range">{renderPriceRange(restaurant.priceRange)}</span>
                    <button class="btn-reserve">Đặt bàn →</button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}

    {#if bookingStep === 'confirmation'}
      <div class="confirmation-section">
        <div class="success-icon">✅</div>
        <h2>Đặt bàn thành công!</h2>
        <p>Cảm ơn bạn đã đặt bàn. Thông tin xác nhận đã được gửi đến email của bạn.</p>
        <button class="btn-primary" on:click={() => bookingStep = 'search'}>
          Đặt bàn khác
        </button>
      </div>
    {/if}
  </div>
</div>

{#if showReservationModal && selectedRestaurant}
  <div class="modal-overlay" on:click={() => showReservationModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>🍽️ Đặt bàn - {selectedRestaurant.name}</h2>
        <button class="btn-close" on:click={() => showReservationModal = false}>✕</button>
      </div>

      <div class="modal-body">
        <div class="reservation-form">
          <div class="form-row">
            <div class="form-field">
              <label>📅 Ngày</label>
              <input type="date" bind:value={selectedDate} />
            </div>
            
            <div class="form-field">
              <label>🕐 Giờ</label>
              <select bind:value={selectedTime}>
                <option value="">Chọn giờ</option>
                {#each timeSlots as time}
                  <option value={time}>{time}</option>
                {/each}
              </select>
            </div>
          </div>
          
          <div class="form-field">
            <label>👥 Số người</label>
            <select bind:value={partySize}>
              {#each [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] as size}
                <option value={size}>{size} người</option>
              {/each}
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" on:click={() => showReservationModal = false}>
          Hủy
        </button>
        <button 
          class="btn-primary" 
          on:click={confirmReservation}
          disabled={$loading || !selectedTime}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang xử lý...
          {:else}
            Xác nhận đặt bàn
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .restaurant-booking-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

  .search-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
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
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.125rem;
    font-weight: 600;
    cursor: pointer;
  }

  .spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: inline-block;
    margin-right: 0.5rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .results-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
  }

  .btn-back {
    background: none;
    border: none;
    color: #f5576c;
    cursor: pointer;
    margin-bottom: 1rem;
  }

  .restaurants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .restaurant-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s;
  }

  .restaurant-card:hover {
    transform: translateY(-4px);
  }

  .restaurant-image {
    height: 200px;
    overflow: hidden;
  }

  .restaurant-image img {
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

  .restaurant-info {
    padding: 1.25rem;
  }

  .restaurant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .restaurant-header h3 {
    margin: 0;
    font-size: 1.25rem;
  }

  .cuisine-tag {
    background: #fce7f3;
    color: #be185d;
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
    font-size: 0.75rem;
  }

  .rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .stars {
    color: #fbbf24;
  }

  .rating-count {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .location {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .features {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .feature-tag {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #4b5563;
  }

  .restaurant-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .btn-reserve {
    padding: 0.5rem 1rem;
    background: #f5576c;
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
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
    max-width: 450px;
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

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .form-field label {
    display: block;
    font-size: 0.875rem;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .form-field input,
  .form-field select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
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
