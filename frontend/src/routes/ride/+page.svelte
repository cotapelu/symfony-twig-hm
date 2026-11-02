<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, slide, scale } from 'svelte/transition';

  // Types
  interface RideOption {
    id: string;
    name: string;
    type: 'car' | 'bike' | 'motorcycle' | 'premium' | 'van';
    icon: string;
    capacity: number;
    price: number;
    eta: number;
    description: string;
  }

  interface Driver {
    id: string;
    name: string;
    rating: number;
    vehicle: string;
    licensePlate: string;
    photo: string;
    responseTime: number;
  }

  interface Trip {
    id: string;
    pickup: string;
    destination: string;
    status: 'pending' | 'finding_driver' | 'driver_assigned' | 'arriving' | 'in_progress' | 'completed' | 'cancelled';
    rideOption: RideOption;
    price: number;
    driver?: Driver;
    createdAt: string;
    completedAt?: string;
    distance: string;
    duration: string;
  }

  // State
  let activeTab: 'book' | 'active' | 'history' = 'book';
  let loading = true;
  let trips: Trip[] = [];
  
  // Booking state
  let pickupLocation = '';
  let destination = '';
  let pickupSuggestions = [
    '123 Nguyen Trai St, District 1, HCMC',
    '456 Le Van Sy St, District 3, HCMC',
    '78 Dong Khoi St, District 1, HCMC',
    '102 Pasteur St, District 1, HCMC',
    '200 Ly Tu Trong St, District 1, HCMC'
  ];
  let destinationSuggestions = [
    'Tan Son Nhat Airport, HCMC',
    'Ben Thanh Market, District 1, HCMC',
    'Vincom Center, District 1, HCMC',
    'Saigon Opera House, District 1, HCMC',
    'District 7, HCMC'
  ];
  let showPickupSuggestions = false;
  let showDestinationSuggestions = false;
  let selectedPickupSuggestion: string | null = null;
  let selectedDestinationSuggestion: string | null = null;
  
  // Ride options
  let rideOptions: RideOption[] = [
    { id: 'car', name: 'Car', type: 'car', icon: '🚗', capacity: 4, price: 85000, eta: 5, description: 'Affordable everyday rides' },
    { id: 'premium', name: 'Premium', type: 'premium', icon: '🚐', capacity: 4, price: 150000, eta: 8, description: 'Luxury vehicles' },
    { id: 'motorcycle', name: 'Motorcycle', type: 'motorcycle', icon: '🏍️', capacity: 1, price: 35000, eta: 3, description: 'Quick & affordable' },
    { id: 'bike', name: 'Bike', type: 'bike', icon: '🚲', capacity: 1, price: 15000, eta: 6, description: 'Eco-friendly option' },
    { id: 'van', name: 'Van', type: 'van', icon: '🚐', capacity: 6, price: 180000, eta: 10, description: 'Group rides' }
  ];
  let selectedRide: RideOption | null = null;
  
  // Price estimation
  let priceEstimate: { min: number; max: number; distance: string; duration: string } | null = null;
  let showPriceEstimate = false;
  
  // Driver matching
  let isFindingDriver = false;
  let findingDriverStep = 0;
  let matchedDriver: Driver | null = null;
  let tripInProgress: Trip | null = null;
  let matchingInterval: ReturnType<typeof setInterval>;
  
  // Simulated drivers for matching
  let availableDrivers: Driver[] = [
    { id: 'd1', name: 'Nguyen Van A', rating: 4.9, vehicle: 'Toyota Vios', licensePlate: '51A-123.45', photo: '👨', responseTime: 45 },
    { id: 'd2', name: 'Tran Thi B', rating: 4.8, vehicle: 'Honda Wave', licensePlate: '51B-678.90', photo: '👩', responseTime: 30 },
    { id: 'd3', name: 'Le Van C', rating: 5.0, vehicle: 'Kia Morning', licensePlate: '51C-111.22', photo: '👨', responseTime: 60 }
  ];

  // Storage key
  const STORAGE_KEY = 'ride_trips';

  onMount(() => {
    loadFromStorage();
  });

  function loadFromStorage() {
    loading = true;
    const stored = localStorage.getItem(STORAGE_KEY);

    if (stored) {
      trips = JSON.parse(stored);
    } else {
      // Sample completed trips
      trips = [
        {
          id: 'trip_001',
          pickup: '123 Nguyen Trai St, District 1, HCMC',
          destination: 'Ben Thanh Market, District 1, HCMC',
          status: 'completed',
          rideOption: rideOptions[0],
          price: 65000,
          driver: availableDrivers[0],
          createdAt: '2026-02-10T08:30:00',
          completedAt: '2026-02-10T09:15:00',
          distance: '3.2 km',
          duration: '15 min'
        },
        {
          id: 'trip_002',
          pickup: '456 Le Van Sy St, District 3, HCMC',
          destination: 'Tan Son Nhat Airport, HCMC',
          status: 'completed',
          rideOption: rideOptions[1],
          price: 145000,
          driver: availableDrivers[2],
          createdAt: '2026-02-08T14:00:00',
          completedAt: '2026-02-08T15:00:00',
          distance: '8.5 km',
          duration: '35 min'
        },
        {
          id: 'trip_003',
          pickup: '78 Dong Khoi St, District 1, HCMC',
          destination: 'District 7, HCMC',
          status: 'cancelled',
          rideOption: rideOptions[2],
          price: 0,
          createdAt: '2026-02-05T18:30:00',
          distance: '6.0 km',
          duration: '25 min'
        }
      ];
      saveToStorage();
    }

    // Check for active trip
    const activeTrip = trips.find(t => 
      ['finding_driver', 'driver_assigned', 'arriving', 'in_progress'].includes(t.status)
    );
    if (activeTrip) {
      tripInProgress = activeTrip;
      selectedRide = activeTrip.rideOption;
      if (activeTrip.driver) matchedDriver = activeTrip.driver;
      if (['finding_driver', 'driver_assigned', 'arriving'].includes(activeTrip.status)) {
        startDriverMatching();
      }
    }

    loading = false;
  }

  function saveToStorage() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(trips));
  }

  // Computed
  $: activeTrips = trips.filter(t => 
    ['finding_driver', 'driver_assigned', 'arriving', 'in_progress'].includes(t.status)
  );

  $: tripHistory = trips.filter(t => 
    ['completed', 'cancelled'].includes(t.status)
  );

  // Helper functions
  function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('vi-VN').format(amount);
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function formatTime(dateStr: string): string {
    return new Date(dateStr).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending: 'Pending',
      finding_driver: 'Finding Driver',
      driver_assigned: 'Driver Assigned',
      arriving: 'Driver Arriving',
      in_progress: 'In Progress',
      completed: 'Completed',
      cancelled: 'Cancelled'
    };
    return labels[status] || status;
  }

  function getStatusClass(status: string): string {
    const classes: Record<string, string> = {
      pending: 'status-pending',
      finding_driver: 'status-finding',
      driver_assigned: 'status-assigned',
      arriving: 'status-arriving',
      in_progress: 'status-progress',
      completed: 'status-completed',
      cancelled: 'status-cancelled'
    };
    return classes[status] || 'status-default';
  }

  // Location handlers
  function handlePickupInput() {
    showPickupSuggestions = pickupLocation.length > 0;
    showDestinationSuggestions = false;
    selectedPickupSuggestion = null;
  }

  function handleDestinationInput() {
    showDestinationSuggestions = destination.length > 0;
    showPickupSuggestions = false;
    selectedDestinationSuggestion = null;
  }

  function selectPickupSuggestion(suggestion: string) {
    pickupLocation = suggestion;
    showPickupSuggestions = false;
    selectedPickupSuggestion = suggestion;
    estimatePrice();
  }

  function selectDestinationSuggestion(suggestion: string) {
    destination = suggestion;
    showDestinationSuggestions = false;
    selectedDestinationSuggestion = suggestion;
    estimatePrice();
  }

  function selectRideOption(option: RideOption) {
    selectedRide = option;
    showPriceEstimate = true;
  }

  function estimatePrice() {
    if (pickupLocation && destination) {
      // Simulate price estimation based on ride type
      const baseDistance = 5 + Math.random() * 5;
      const baseDuration = 15 + Math.random() * 20;
      
      priceEstimate = {
        min: Math.round(selectedRide?.price * 0.8 || 30000),
        max: Math.round(selectedRide?.price * 1.2 || 50000),
        distance: baseDistance.toFixed(1) + ' km',
        duration: Math.round(baseDuration) + ' min'
      };
      showPriceEstimate = true;
    }
  }

  function startBooking() {
    if (!pickupLocation || !destination || !selectedRide) return;
    
    isFindingDriver = true;
    findingDriverStep = 0;
    
    const newTrip: Trip = {
      id: 'trip_' + Date.now(),
      pickup: pickupLocation,
      destination: destination,
      status: 'finding_driver',
      rideOption: selectedRide,
      price: selectedRide.price,
      createdAt: new Date().toISOString(),
      distance: priceEstimate?.distance || '5.0 km',
      duration: priceEstimate?.duration || '20 min'
    };
    
    trips = [newTrip, ...trips];
    tripInProgress = newTrip;
    saveToStorage();
    
    startDriverMatching();
  }

  function startDriverMatching() {
    findingDriverStep = 0;
    isFindingDriver = true;
    
    matchingInterval = setInterval(() => {
      findingDriverStep++;
      
      if (findingDriverStep === 1) {
        // Searching for nearby drivers
        tripInProgress = { ...tripInProgress!, status: 'finding_driver' };
      } else if (findingDriverStep === 2) {
        // Driver found
        const randomDriver = availableDrivers[Math.floor(Math.random() * availableDrivers.length)];
        matchedDriver = randomDriver;
        tripInProgress = { 
          ...tripInProgress!, 
          status: 'driver_assigned',
          driver: randomDriver
        };
      } else if (findingDriverStep === 3) {
        // Driver arriving
        tripInProgress = { ...tripInProgress!, status: 'arriving' };
      } else if (findingDriverStep === 4) {
        // Trip started
        tripInProgress = { ...tripInProgress!, status: 'in_progress' };
      } else if (findingDriverStep === 6) {
        // Complete trip
        completeTrip();
      }
    }, 2000);
  }

  function completeTrip() {
    clearInterval(matchingInterval);
    isFindingDriver = false;
    
    if (tripInProgress) {
      tripInProgress = { 
        ...tripInProgress, 
        status: 'completed',
        completedAt: new Date().toISOString()
      };
      
      trips = trips.map(t => 
        t.id === tripInProgress!.id ? tripInProgress! : t
      );
      saveToStorage();
    }
  }

  function cancelTrip() {
    clearInterval(matchingInterval);
    isFindingDriver = false;
    
    if (tripInProgress) {
      tripInProgress = { 
        ...tripInProgress, 
        status: 'cancelled'
      };
      
      trips = trips.map(t => 
        t.id === tripInProgress!.id ? tripInProgress! : t
      );
      saveToStorage();
      
      // Reset booking
      resetBooking();
    }
  }

  function resetBooking() {
    pickupLocation = '';
    destination = '';
    selectedRide = null;
    priceEstimate = null;
    showPriceEstimate = false;
    isFindingDriver = false;
    matchedDriver = null;
    tripInProgress = null;
    findingDriverStep = 0;
  }

  function closeClickOutside(e: MouseEvent) {
    showPickupSuggestions = false;
    showDestinationSuggestions = false;
  }
</script>

<svelte:head>
  <title>🚗 Ride - Hotel Booking App</title>
</svelte:head>

<div class="ride-page" on:click={closeClickOutside}>
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>🚗 Ride</h1>
        <p>Book your ride in seconds</p>
      </div>
    </div>
  </header>

  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'book'} 
      on:click={() => activeTab = 'book'}
    >
      Book Ride
      {#if activeTrips.length > 0}
        <span class="badge">{activeTrips.length}</span>
      {/if}
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'active'} 
      on:click={() => activeTab = 'active'}
    >
      Active Trip
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'history'} 
      on:click={() => activeTab = 'history'}
    >
      History
    </button>
  </nav>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading rides...</p>
      </div>
    {:else}
      {#if activeTab === 'book'}
        <div class="book-section" in:fade>
            {#if tripInProgress && ['finding_driver', 'driver_assigned', 'arriving', 'in_progress'].includes(tripInProgress.status)}
            <div class="active-trip-card" in:slide>
              <div class="trip-status-header">
                <span class="status-badge {getStatusClass(tripInProgress.status)}">
                  {getStatusLabel(tripInProgress.status)}
                </span>
              </div>
              
              <div class="trip-route">
                <div class="route-point">
                  <span class="point-icon">📍</span>
                  <div class="point-info">
                    <span class="point-label">Pickup</span>
                    <span class="point-value">{tripInProgress.pickup}</span>
                  </div>
                </div>
                <div class="route-connector">
                  <div class="connector-line"></div>
                </div>
                <div class="route-point">
                  <span class="point-icon">🏁</span>
                  <div class="point-info">
                    <span class="point-label">Destination</span>
                    <span class="point-value">{tripInProgress.destination}</span>
                  </div>
                </div>
              </div>
              
              {#if matchedDriver}
                <div class="driver-info">
                  <div class="driver-avatar">{matchedDriver.photo}</div>
                  <div class="driver-details">
                    <div class="driver-name">{matchedDriver.name}</div>
                    <div class="driver-vehicle">{matchedDriver.vehicle} • {matchedDriver.licensePlate}</div>
                    <div class="driver-rating">⭐ {matchedDriver.rating}</div>
                  </div>
                </div>
              {/if}
              
              {#if isFindingDriver}
                <div class="finding-animation">
                  <div class="pulse-ring"></div>
                  <div class="searching-icon">🔍</div>
                </div>
              {/if}
              
              <button class="cancel-btn" on:click={cancelTrip}>
                Cancel Trip
              </button>
            </div>
          {:else}
            <div class="booking-card">
              <div class="location-input-group">
                <div class="input-row">
                  <span class="input-icon pickup-icon">🟢</span>
                  <div class="input-wrapper">
                    <input 
                      type="text" 
                      placeholder="Pickup location"
                      bind:value={pickupLocation}
                      on:input={handlePickupInput}
                      on:click|stopPropagation
                    />
                  </div>
                  {#if pickupLocation}
                    <button class="clear-btn" on:click|stopPropagation={() => { pickupLocation = ''; showPickupSuggestions = false; }}>×</button>
                  {/if}
                </div>
                
                {#if showPickupSuggestions}
                  <div class="suggestions-dropdown" transition:slide>
                    {#each pickupSuggestions.filter(s => s.toLowerCase().includes(pickupLocation.toLowerCase())) as suggestion}
                      <button class="suggestion-item" on:click|stopPropagation={() => selectPickupSuggestion(suggestion)}>
                        📍 {suggestion}
                      </button>
                    {/each}
                  </div>
                {/if}
              </div>
              
              <div class="location-input-group">
                <div class="input-row">
                  <span class="input-icon dest-icon">🔴</span>
                  <div class="input-wrapper">
                    <input 
                      type="text" 
                      placeholder="Where to?"
                      bind:value={destination}
                      on:input={handleDestinationInput}
                      on:click|stopPropagation
                    />
                  </div>
                  {#if destination}
                    <button class="clear-btn" on:click|stopPropagation={() => { destination = ''; showDestinationSuggestions = false; }}>×</button>
                  {/if}
                </div>
                
                {#if showDestinationSuggestions}
                  <div class="suggestions-dropdown" transition:slide>
                    {#each destinationSuggestions.filter(s => s.toLowerCase().includes(destination.toLowerCase())) as suggestion}
                      <button class="suggestion-item" on:click|stopPropagation={() => selectDestinationSuggestion(suggestion)}>
                        📍 {suggestion}
                      </button>
                    {/each}
                  </div>
                {/if}
              </div>
              
              <button class="swap-btn" on:click={() => { const t = pickupLocation; pickupLocation = destination; destination = t; }}>
                ⇅
              </button>
            </div>
            
            {#if pickupLocation || destination}
              <div class="ride-options-section" transition:slide>
                <h3>Select Ride</h3>
                <div class="ride-options-grid">
                  {#each rideOptions as option}
                    <button 
                      class="ride-option-card"
                      class:selected={selectedRide?.id === option.id}
                      on:click={() => selectRideOption(option)}
                    >
                      <div class="ride-icon">{option.icon}</div>
                      <div class="ride-info">
                        <div class="ride-name">{option.name}</div>
                        <div class="ride-desc">{option.description}</div>
                        <div class="ride-meta">
                          <span class="capacity">👤 {option.capacity}</span>
                          <span class="eta">⏱️ {option.eta} min</span>
                        </div>
                      </div>
                      <div class="ride-price">
                        <span class="price-value">₫{formatCurrency(option.price)}</span>
                      </div>
                    </button>
                  {/each}
                </div>
              </div>
            {/if}
            
            {#if showPriceEstimate && selectedRide}
              <div class="price-estimate-card" transition:slide>
                <div class="estimate-header">
                  <span class="estimate-icon">💰</span>
                  <div class="estimate-info">
                    <div class="estimate-title">Estimated Price</div>
                    <div class="estimate-range">₫{formatCurrency(priceEstimate?.min || 0)} - ₫{formatCurrency(priceEstimate?.max || 0)}</div>
                  </div>
                </div>
                <div class="estimate-details">
                  <div class="estimate-item">
                    <span class="item-label">Distance</span>
                    <span class="item-value">{priceEstimate?.distance}</span>
                  </div>
                  <div class="estimate-item">
                    <span class="item-label">Duration</span>
                    <span class="item-value">{priceEstimate?.duration}</span>
                  </div>
                </div>
                
                <button 
                  class="book-btn"
                  on:click={startBooking}
                  disabled={!pickupLocation || !destination || !selectedRide}
                >
                  {isFindingDriver ? 'Finding Driver...' : 'Book Ride'}
                </button>
              </div>
            {/if}
          {/if}
        </div>
      {/if}
      
      {#if activeTab === 'active'}
        <div class="active-section" in:fade>
          {#if activeTrips.length === 0}
            <div class="empty-state">
              <div class="empty-icon">🚗</div>
              <h3>No active trips</h3>
              <p>Your current trips will appear here</p>
            </div>
          {:else}
            {#each activeTrips as trip}
              <div class="trip-card">
                <div class="trip-header">
                  <span class="status-badge {getStatusClass(trip.status)}">
                    {getStatusLabel(trip.status)}
                  </span>
                  <span class="trip-id">#{trip.id.slice(-6)}</span>
                </div>
                
                <div class="trip-route-mini">
                  <div class="route-point-mini">
                    <span class="point-dot green"></span>
                    <span>{trip.pickup}</span>
                  </div>
                  <div class="route-point-mini">
                    <span class="point-dot red"></span>
                    <span>{trip.destination}</span>
                  </div>
                </div>
                
                <div class="trip-footer">
                  <div class="trip-meta">
                    <span>{trip.rideOption.icon} {trip.rideOption.name}</span>
                    <span>•</span>
                    <span>{trip.distance}</span>
                  </div>
                  <div class="trip-price">₫{formatCurrency(trip.price)}</div>
                </div>
              </div>
            {/each}
          {/if}
        </div>
      {/if}
      
      {#if activeTab === 'history'}
        <div class="history-section" in:fade>
          {#if tripHistory.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📋</div>
              <h3>No trip history</h3>
              <p>Your completed trips will appear here</p>
            </div>
          {:else}
            <div class="history-list">
              {#each tripHistory as trip}
                <div class="history-item">
                  <div class="history-icon">
                    {trip.status === 'completed' ? '✅' : '❌'}
                  </div>
                  <div class="history-info">
                    <div class="history-route">
                      <span class="route-from">{trip.pickup.split(',')[0]}</span>
                      <span class="route-arrow">→</span>
                      <span class="route-to">{trip.destination.split(',')[0]}</span>
                    </div>
                    <div class="history-meta">
                      {trip.rideOption.icon} {trip.rideOption.name} • {formatDate(trip.createdAt)} • {trip.distance}
                    </div>
                    {#if trip.driver}
                      <div class="history-driver">
                        Driver: {trip.driver.name} • ⭐ {trip.driver.rating}
                      </div>
                    {/if}
                  </div>
                  <div class="history-right">
                    <span class="status-badge {getStatusClass(trip.status)}">
                      {getStatusLabel(trip.status)}
                    </span>
                    {#if trip.price > 0}
                      <div class="history-price">₫{formatCurrency(trip.price)}</div>
                    {/if}
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {/if}
    {/if}
  </main>
</div>

<style>
  /* Base */
  .ride-page {
    min-height: 100vh;
    background: #f0fdf4;
  }

  .header {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    color: white;
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
  }

  .header-left h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .header-left p {
    margin: 0.25rem 0 0;
    opacity: 0.9;
    font-size: 0.9rem;
  }

  /* Tabs */
  .tabs {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
    display: flex;
    gap: 0.5rem;
    max-width: 1200px;
    margin: 0 auto;
  }

  .tab {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .tab:hover {
    color: #16a34a;
  }

  .tab.active {
    color: #16a34a;
    border-bottom-color: #16a34a;
  }

  .badge {
    background: #16a34a;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
  }

  /* Main */
  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  /* Loading */
  .loading {
    text-align: center;
    padding: 4rem;
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e2e8f0;
    border-top-color: #16a34a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Booking Card */
  .booking-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);
    position: relative;
  }

  .location-input-group {
    position: relative;
    margin-bottom: 0.5rem;
  }

  .input-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px solid transparent;
    transition: all 0.2s;
  }

  .input-row:focus-within {
    border-color: #16a34a;
    background: white;
  }

  .input-icon {
    font-size: 1rem;
    width: 24px;
    text-align: center;
  }

  .pickup-icon {
    color: #22c55e;
  }

  .dest-icon {
    color: #ef4444;
  }

  .input-wrapper {
    flex: 1;
  }

  .input-wrapper input {
    width: 100%;
    border: none;
    background: transparent;
    font-size: 1rem;
    color: #1e293b;
    outline: none;
  }

  .input-wrapper input::placeholder {
    color: #94a3b8;
  }

  .clear-btn {
    background: #e2e8f0;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    color: #64748b;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .clear-btn:hover {
    background: #cbd5e1;
  }

  .suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    z-index: 100;
    margin-top: 0.5rem;
    overflow: hidden;
  }

  .suggestion-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    color: #475569;
    transition: background 0.2s;
  }

  .suggestion-item:hover {
    background: #f0fdf4;
    color: #16a34a;
  }

  .swap-btn {
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    background: #16a34a;
    color: white;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: transform 0.2s;
  }

  .swap-btn:hover {
    transform: translateY(-50%) rotate(180deg);
  }

  /* Ride Options */
  .ride-options-section {
    margin-top: 1.5rem;
  }

  .ride-options-section h3 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1.1rem;
  }

  .ride-options-grid {
    display: grid;
    gap: 0.75rem;
  }

  .ride-option-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
  }

  .ride-option-card:hover {
    border-color: #22c55e;
    transform: translateX(4px);
  }

  .ride-option-card.selected {
    border-color: #16a34a;
    background: #f0fdf4;
    box-shadow: 0 4px 12px rgba(22, 197, 94, 0.15);
  }

  .ride-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0fdf4;
    border-radius: 12px;
  }

  .ride-info {
    flex: 1;
  }

  .ride-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
  }

  .ride-desc {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.125rem;
  }

  .ride-meta {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #64748b;
  }

  .ride-price {
    text-align: right;
  }

  .price-value {
    font-weight: 700;
    color: #16a34a;
    font-size: 1.1rem;
  }

  /* Price Estimate */
  .price-estimate-card {
    margin-top: 1.5rem;
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);
  }

  .estimate-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .estimate-icon {
    font-size: 2rem;
  }

  .estimate-title {
    font-size: 0.85rem;
    color: #64748b;
  }

  .estimate-range {
    font-size: 1.5rem;
    font-weight: 700;
    color: #16a34a;
  }

  .estimate-details {
    display: flex;
    gap: 2rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 1.5rem;
  }

  .estimate-item {
    display: flex;
    flex-direction: column;
  }

  .item-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .item-value {
    font-weight: 600;
    color: #1e293b;
  }

  .book-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .book-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);
  }

  .book-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
  }

  /* Active Trip Card */
  .active-trip-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(22, 163, 74, 0.15);
    text-align: center;
  }

  .trip-status-header {
    margin-bottom: 1.5rem;
  }

  .status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
  }

  .status-pending { background: #fef3c7; color: #92400e; }
  .status-finding { background: #dbeafe; color: #1d4ed8; }
  .status-assigned { background: #e0e7ff; color: #4338ca; }
  .status-arriving { background: #fef3c7; color: #b45309; }
  .status-progress { background: #d1fae5; color: #065f46; }
  .status-completed { background: #d1fae5; color: #059669; }
  .status-cancelled { background: #fee2e2; color: #991b1b; }

  .trip-route {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .route-point {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    text-align: left;
  }

  .point-icon {
    font-size: 1.25rem;
  }

  .point-info {
    display: flex;
    flex-direction: column;
  }

  .point-label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .point-value {
    font-weight: 500;
    color: #1e293b;
    margin-top: 0.25rem;
  }

  .route-connector {
    padding-left: 0.6rem;
    margin: 0.25rem 0;
  }

  .connector-line {
    width: 2px;
    height: 24px;
    background: #e2e8f0;
  }

  .driver-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f0fdf4;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .driver-avatar {
    font-size: 2.5rem;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .driver-details {
    text-align: left;
  }

  .driver-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1.1rem;
  }

  .driver-vehicle {
    font-size: 0.9rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .driver-rating {
    font-size: 0.9rem;
    color: #f59e0b;
    margin-top: 0.25rem;
  }

  .finding-animation {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 2rem auto;
  }

  .pulse-ring {
    position: absolute;
    inset: 0;
    border: 3px solid #22c55e;
    border-radius: 50%;
    animation: pulse 2s ease-out infinite;
  }

  @keyframes pulse {
    0% { transform: scale(0.8); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
  }

  .searching-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
  }

  .cancel-btn {
    padding: 0.875rem 2rem;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .cancel-btn:hover {
    background: #fecaca;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    color: #1e293b;
    margin: 0 0 0.5rem;
  }

  .empty-state p {
    color: #64748b;
    margin: 0;
  }

  /* Trip Card */
  .trip-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }

  .trip-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .trip-id {
    font-size: 0.8rem;
    color: #94a3b8;
  }

  .trip-route-mini {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .route-point-mini {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #475569;
  }

  .point-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .point-dot.green { background: #22c55e; }
  .point-dot.red { background: #ef4444; }

  .trip-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .trip-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #64748b;
  }

  .trip-price {
    font-weight: 700;
    color: #16a34a;
  }

  /* History List */
  .history-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .history-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  }

  .history-icon {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 10px;
  }

  .history-info {
    flex: 1;
  }

  .history-route {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #1e293b;
  }

  .route-arrow {
    color: #94a3b8;
  }

  .history-meta {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .history-driver {
    font-size: 0.8rem;
    color: #16a34a;
    margin-top: 0.25rem;
  }

  .history-right {
    text-align: right;
  }

  .history-price {
    font-weight: 600;
    color: #1e293b;
    margin-top: 0.5rem;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 1rem;
    }

    .tabs {
      padding: 0 1rem;
      overflow-x: auto;
    }

    .booking-card {
      padding: 1rem;
    }

    .swap-btn {
      right: 1rem;
    }

    .ride-option-card {
      padding: 0.875rem;
    }

    .ride-icon {
      width: 44px;
      height: 44px;
      font-size: 1.5rem;
    }

    .estimate-details {
      flex-direction: column;
      gap: 1rem;
    }
  }
</style>
