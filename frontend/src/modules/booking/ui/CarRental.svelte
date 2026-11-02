<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';

  interface Car {
    id: string;
    brand: string;
    model: string;
    year: number;
    type: 'economy' | 'compact' | 'midsize' | 'suv' | 'luxury' | 'van';
    seats: number;
    transmission: 'automatic' | 'manual';
    fuelType: string;
    features: string[];
    images: string[];
    pricePerDay: number;
    rating: number;
    location: {
      city: string;
      address: string;
    };
  }

  interface Booking {
    id: string;
    carId: string;
    startDate: string;
    endDate: string;
    pickupLocation: string;
    dropoffLocation: string;
    totalPrice: number;
    status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled';
  }

  const cars = writable<Car[]>([]);
  const loading = writable(false);
  const error = writable<string | null>(null);

  let selectedCity = '';
  let pickupDate = '';
  let returnDate = '';
  let selectedType: string = '';
  let selectedTransmission: string = '';
  let minPrice: number = 0;
  let maxPrice: number = 5000000;
  let selectedCar: Car | null = null;
  let showBookingModal = false;
  let bookingStep: 'search' | 'results' | 'confirmation' = 'search';
  let pickupLocation = '';
  let dropoffLocation = '';

  const carTypes = [
    { value: 'economy', label: 'Tiết kiệm', icon: '🚗' },
    { value: 'compact', label: 'Nhỏ gọn', icon: '🚙' },
    { value: 'midsize', label: 'Trung bình', icon: '🚐' },
    { value: 'suv', label: 'SUV', icon: '🚚' },
    { value: 'luxury', label: 'Sang trọng', icon: '🏎️' },
    { value: 'van', label: 'Xe van', icon: '🚐' }
  ];

  const cities = ['Hà Nội', 'TP.HCM', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Nha Trang'];

  onMount(() => {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    pickupDate = today.toISOString().split('T')[0];
    returnDate = tomorrow.toISOString().split('T')[0];
  });

  async function searchCars() {
    loading.set(true);
    error.set(null);

    try {
      const params = new URLSearchParams();
      if (selectedCity) params.append('city', selectedCity);
      if (selectedType) params.append('type', selectedType);
      if (selectedTransmission) params.append('transmission', selectedTransmission);
      params.append('startDate', pickupDate);
      params.append('endDate', returnDate);

      const response = await fetch(`/api/car-rental/cars/search?${params}`);
      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      cars.set(data.cars || []);
      bookingStep = 'results';
    } catch (err) {
      error.set(err instanceof Error ? err.message : 'Search failed');
    } finally {
      loading.set(false);
    }
  }

  function selectCar(car: Car) {
    selectedCar = car;
    pickupLocation = selectedCity;
    dropoffLocation = selectedCity;
    showBookingModal = true;
  }

  function calculateDays(): number {
    if (!pickupDate || !returnDate) return 1;
    const start = new Date(pickupDate);
    const end = new Date(returnDate);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 1;
  }

  function calculateTotal(): number {
    if (!selectedCar) return 0;
    return selectedCar.pricePerDay * calculateDays();
  }

  async function confirmBooking() {
    if (!selectedCar) return;

    loading.set(true);
    try {
      const response = await fetch('/api/car-rental/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          carId: selectedCar.id,
          startDate: pickupDate,
          endDate: returnDate,
          pickupLocation,
          dropoffLocation
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

  function getCarTypeIcon(type: string): string {
    const typeMap: Record<string, string> = {
      economy: '🚗',
      compact: '🚙',
      midsize: '🚐',
      suv: '🚚',
      luxury: '🏎️',
      van: '🚐'
    };
    return typeMap[type] || '🚗';
  }
</script>

<div class="car-rental-page">
  <div class="rental-container">
    <div class="rental-header">
      <h1>🚗 Thuê xe</h1>
      <p class="subtitle">Thuê xe tự lái với giá tốt nhất</p>
    </div>

    {#if $error}
      <div class="error-message">{$error}</div>
    {/if}

    {#if bookingStep === 'search'}
      <div class="search-form">
        <div class="search-grid">
          <div class="search-field">
            <label>📍 Địa điểm nhận xe</label>
            <select bind:value={selectedCity}>
              <option value="">Chọn thành phố</option>
              {#each cities as city}
                <option value={city}>{city}</option>
              {/each}
            </select>
          </div>

          <div class="search-field">
            <label>📅 Ngày nhận</label>
            <input type="date" bind:value={pickupDate} />
          </div>

          <div class="search-field">
            <label>📅 Ngày trả</label>
            <input type="date" bind:value={returnDate} />
          </div>

          <div class="search-field">
            <label>🚗 Loại xe</label>
            <select bind:value={selectedType}>
              <option value="">Tất cả</option>
              {#each carTypes as type}
                <option value={type.value}>{type.icon} {type.label}</option>
              {/each}
            </select>
          </div>

          <div class="search-field">
            <label>⚙️ Hộp số</label>
            <select bind:value={selectedTransmission}>
              <option value="">Tất cả</option>
              <option value="automatic">Tự động</option>
              <option value="manual">Số sàn</option>
            </select>
          </div>

          <div class="search-field">
            <label>💰 Giá tối đa: {formatPrice(maxPrice)}</label>
            <input 
              type="range" 
              bind:value={maxPrice} 
              min="100000" 
              max="5000000" 
              step="100000"
            />
          </div>
        </div>

        <button 
          class="btn-search" 
          on:click={searchCars}
          disabled={$loading || !selectedCity}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang tìm...
          {:else}
            🔍 Tìm xe
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
          <h2>Danh sách xe có sẵn</h2>
          <p>{selectedCity} • {pickupDate} → {returnDate}</p>
        </div>

        {#if $cars.length === 0}
          <div class="empty">Không tìm thấy xe nào phù hợp</div>
        {:else}
          <div class="cars-grid">
            {#each $cars as car}
              <div class="car-card">
                <div class="car-image">
                  {#if car.images && car.images.length > 0}
                    <img src={car.images[0]} alt={`${car.brand} ${car.model}`} />
                  {:else}
                    <div class="placeholder-image">{getCarTypeIcon(car.type)}</div>
                  {/if}
                </div>

                <div class="car-info">
                  <div class="car-header">
                    <h3>{car.brand} {car.model}</h3>
                    <span class="car-year">{car.year}</span>
                  </div>

                  <div class="car-specs">
                    <span class="spec">👥 {car.seats} chỗ</span>
                    <span class="spec">⚙️ {car.transmission === 'automatic' ? 'Tự động' : 'Số sàn'}</span>
                    <span class="spec">⛽ {car.fuelType}</span>
                  </div>

                  <div class="car-features">
                    {#each car.features.slice(0, 4) as feature}
                      <span class="feature-tag">{feature}</span>
                    {/each}
                  </div>

                  <div class="car-location">
                    📍 {car.location.address}
                  </div>

                  <div class="car-footer">
                    <div class="car-price">
                      <span class="price">{formatPrice(car.pricePerDay)}</span>
                      <span class="unit">/ngày</span>
                    </div>
                    <button class="btn-rent" on:click={() => selectCar(car)}>
                      Thuê ngay
                    </button>
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
        <h2>Đặt xe thành công!</h2>
        <p>Xe của bạn đã được đặt. Vui lòng đến đúng giờ để nhận xe.</p>
        <button class="btn-primary" on:click={() => bookingStep = 'search'}>
          Thuê xe khác
        </button>
      </div>
    {/if}
  </div>
</div>

{#if showBookingModal && selectedCar}
  <div class="modal-overlay" on:click={() => showBookingModal = false}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>Xác nhận thuê xe</h2>
        <button class="btn-close" on:click={() => showBookingModal = false}>✕</button>
      </div>

      <div class="modal-body">
        <div class="car-summary">
          <div class="summary-car">
            <span class="car-icon">{getCarTypeIcon(selectedCar.type)}</span>
            <div>
              <strong>{selectedCar.brand} {selectedCar.model}</strong>
              <span>{selectedCar.year} • {selectedCar.seats} chỗ</span>
            </div>
          </div>
        </div>

        <div class="booking-details">
          <div class="detail-row">
            <span>Ngày nhận:</span>
            <strong>{pickupDate}</strong>
          </div>
          <div class="detail-row">
            <span>Ngày trả:</span>
            <strong>{returnDate}</strong>
          </div>
          <div class="detail-row">
            <span>Số ngày thuê:</span>
            <strong>{calculateDays()} ngày</strong>
          </div>
          <div class="detail-row">
            <span>Giá/ngày:</span>
            <strong>{formatPrice(selectedCar.pricePerDay)}</strong>
          </div>
        </div>

        <div class="form-group">
          <label>Địa điểm nhận xe</label>
          <input type="text" bind:value={pickupLocation} placeholder="Nhập địa chỉ" />
        </div>

        <div class="form-group">
          <label>Địa điểm trả xe</label>
          <input type="text" bind:value={dropoffLocation} placeholder="Nhập địa chỉ" />
        </div>

        <div class="price-total">
          <span>Tổng cộng:</span>
          <strong class="total-amount">{formatPrice(calculateTotal())}</strong>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" on:click={() => showBookingModal = false}>
          Hủy
        </button>
        <button 
          class="btn-primary" 
          on:click={confirmBooking}
          disabled={$loading || !pickupLocation || !dropoffLocation}
        >
          {#if $loading}
            <span class="spinner"></span>
            Đang xử lý...
          {:else}
            Xác nhận thuê
          {/if}
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .car-rental-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #4ade80 0%, #059669 100%);
    padding: 2rem;
  }

  .rental-container {
    max-width: 1200px;
    margin: 0 auto;
  }

  .rental-header {
    text-align: center;
    color: white;
    margin-bottom: 2rem;
  }

  .rental-header h1 {
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
    background: linear-gradient(135deg, #4ade80 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.125rem;
    font-weight: 600;
    cursor: pointer;
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
    color: #059669;
    cursor: pointer;
    margin-bottom: 1rem;
  }

  .cars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .car-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
  }

  .car-image {
    height: 200px;
    overflow: hidden;
  }

  .car-image img {
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

  .car-info {
    padding: 1.25rem;
  }

  .car-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .car-header h3 {
    margin: 0;
    font-size: 1.25rem;
  }

  .car-year {
    color: #6b7280;
    font-size: 0.875rem;
  }

  .car-specs {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    color: #4b5563;
  }

  .car-features {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .feature-tag {
    background: #f0fdf4;
    color: #166534;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
  }

  .car-location {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
  }

  .car-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .car-price .price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #059669;
  }

  .car-price .unit {
    color: #6b7280;
    font-size: 0.875rem;
  }

  .btn-rent {
    padding: 0.75rem 1.5rem;
    background: #059669;
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
    background: linear-gradient(135deg, #4ade80 0%, #059669 100%);
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

  .car-summary {
    background: #f0fdf4;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .summary-car {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .car-icon {
    font-size: 2.5rem;
  }

  .booking-details {
    margin-bottom: 1.5rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }

  .price-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-top: 1rem;
    border-top: 2px solid #e5e7eb;
    font-size: 1.25rem;
  }

  .total-amount {
    color: #059669;
    font-size: 1.5rem;
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
