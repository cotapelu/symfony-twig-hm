<script lang="ts">
  import { goto } from '$app/navigation';

  interface CompareItem {
    id: string;
    type: 'hotel' | 'flight' | 'package';
    name: string;
    image: string;
    rating: number;
    reviews: number;
    price: number;
    features: Record<string, string | boolean>;
    location: string;
  }

  const hotels: CompareItem[] = [
    {
      id: 'h1',
      type: 'hotel',
      name: 'Grand Plaza Hotel',
      image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
      rating: 4.8,
      reviews: 1250,
      price: 299,
      features: {
        'Free WiFi': true,
        'Pool': true,
        'Gym': true,
        'Spa': true,
        'Restaurant': true,
        'Room Service': true,
        'Parking': true,
        'Airport Shuttle': false,
        'Pet Friendly': false,
        'Cancellation': 'Free'
      },
      location: 'New York, NY'
    },
    {
      id: 'h2',
      type: 'hotel',
      name: 'Seaside Resort',
      image: 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800',
      rating: 4.5,
      reviews: 890,
      price: 249,
      features: {
        'Free WiFi': true,
        'Pool': true,
        'Gym': true,
        'Spa': false,
        'Restaurant': true,
        'Room Service': true,
        'Parking': true,
        'Airport Shuttle': true,
        'Pet Friendly': true,
        'Cancellation': 'Free'
      },
      location: 'Miami, FL'
    },
    {
      id: 'h3',
      type: 'hotel',
      name: 'Mountain Lodge',
      image: 'https://images.unsplash.com/photo-1519449556851-5720b33024e7?w=800',
      rating: 4.6,
      reviews: 567,
      price: 199,
      features: {
        'Free WiFi': true,
        'Pool': false,
        'Gym': true,
        'Spa': true,
        'Restaurant': true,
        'Room Service': false,
        'Parking': true,
        'Airport Shuttle': false,
        'Pet Friendly': true,
        'Cancellation': 'Paid'
      },
      location: 'Aspen, CO'
    }
  ];

  const flights: CompareItem[] = [
    {
      id: 'f1',
      type: 'flight',
      name: 'Delta Airlines',
      image: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=800',
      rating: 4.3,
      reviews: 3400,
      price: 450,
      features: {
        'Non-stop': true,
        'Duration': '6h 30m',
        'Baggage': '1 checked bag',
        'Meals': 'Included',
        'WiFi': false,
        'Entertainment': true,
        'Seat Type': 'Economy',
        'Flexibility': 'Changeable'
      },
      location: 'JFK → LAX'
    },
    {
      id: 'f2',
      type: 'flight',
      name: 'United Airlines',
      image: 'https://images.unsplash.com/photo-1556388158-158ea5ccacbd?w=800',
      rating: 4.1,
      reviews: 2800,
      price: 389,
      features: {
        'Non-stop': false,
        'Duration': '8h 15m',
        'Baggage': '1 checked bag',
        'Meals': 'Snack only',
        'WiFi': true,
        'Entertainment': true,
        'Seat Type': 'Economy',
        'Flexibility': 'Changeable'
      },
      location: 'JFK → LAX'
    }
  ];

  let compareType: 'hotel' | 'flight' | 'package' = 'hotel';
  let selectedItems: string[] = [];
  let maxItems = 3;

  $: items = compareType === 'hotel' ? hotels : flights;

  function toggleItem(id: string) {
    if (selectedItems.includes(id)) {
      selectedItems = selectedItems.filter(i => i !== id);
    } else if (selectedItems.length < maxItems) {
      selectedItems = [...selectedItems, id];
    }
  }

  function isSelected(id: string): boolean {
    return selectedItems.includes(id);
  }

  $: selectedObjects = items.filter(item => selectedItems.includes(item.id));

  function getFeatureLabel(key: string): string {
    return key;
  }

  function getFeatureValue(value: string | boolean): string {
    if (typeof value === 'boolean') {
      return value ? '✓' : '✗';
    }
    return value;
  }

  function getPriceDisplay(price: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(price);
  }

  function bookNow(item: CompareItem) {
    goto(`/${compareType}s/${item.id}`);
  }
</script>

<svelte:head>
  <title>Compare - Travel App</title>
</svelte:head>

<div class="compare-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>⚖️ Compare</h1>
    </div>
  </header>

  <main class="main-content">
    <!-- Type Selector -->
    <div class="type-selector">
      <button 
        class="type-btn"
        class:active={compareType === 'hotel'}
        onclick={() => { compareType = 'hotel'; selectedItems = []; }}
      >
        🏨 Hotels
      </button>
      <button 
        class="type-btn"
        class:active={compareType === 'flight'}
        onclick={() => { compareType = 'flight'; selectedItems = []; }}
      >
        ✈️ Flights
      </button>
      <button 
        class="type-btn"
        class:active={compareType === 'package'}
        onclick={() => { compareType = 'package'; selectedItems = []; }}
      >
        📦 Packages
      </button>
    </div>

    <!-- Selection Counter -->
    <div class="selection-info">
      <p>Select up to {maxItems} {compareType}s to compare</p>
      <span>{selectedItems.length} selected</span>
    </div>

    <!-- Items Grid -->
    <div class="items-grid">
      {#each items as item}
        {@const selected = isSelected(item.id)}
        <div 
          class="item-card"
          class:selected={selected}
          onclick={() => toggleItem(item.id)}
        >
          {#if selected}
            <div class="selected-badge">✓</div>
          {/if}
          <div class="card-image" style="background-image: url({item.image})"></div>
          <div class="card-content">
            <h3>{item.name}</h3>
            <p class="location">{item.location}</p>
            <div class="rating">
              <span class="stars">⭐ {item.rating}</span>
              <span class="reviews">({item.reviews} reviews)</span>
            </div>
            <p class="price">{getPriceDisplay(item.price)}</p>
            <button class="select-btn" class:selected={selected}>
              {selected ? 'Selected' : 'Select'}
            </button>
          </div>
        </div>
      {/each}
    </div>

    <!-- Comparison Table -->
    {#if selectedObjects.length > 0}
      <div class="comparison-section">
        <h2>Comparison</h2>
        
        <div class="comparison-table">
          <div class="table-header">
            <div class="feature-label">Features</div>
            {#each selectedObjects as item}
              <div class="item-header">
                <img src={item.image} alt={item.name} />
                <h4>{item.name}</h4>
                <p class="item-location">{item.location}</p>
                <button class="book-btn" onclick={() => bookNow(item)}>
                  Book Now
                </button>
              </div>
            {/each}
          </div>

          {#each Object.entries(selectedObjects[0]?.features || {}) as [key, value]}
            <div class="table-row">
              <div class="feature-label">{getFeatureLabel(key)}</div>
              {#each selectedObjects as item}
                {@const featureValue = item.features[key]}
                <div class="feature-value" class:highlight={typeof featureValue === 'boolean' && featureValue}>
                  {getFeatureValue(featureValue)}
                </div>
              {/each}
            </div>
          {/each}

          <div class="table-row total-row">
            <div class="feature-label">Price</div>
            {#each selectedObjects as item}
              <div class="price-cell">
                <span class="price-value">{getPriceDisplay(item.price)}</span>
              </div>
            {/each}
          </div>

          <div class="table-row">
            <div class="feature-label">Rating</div>
            {#each selectedObjects as item}
              <div class="rating-cell">
                <span class="rating-value">⭐ {item.rating}</span>
                <span class="review-count">({item.reviews} reviews)</span>
              </div>
            {/each}
          </div>
        </div>
      </div>
    {:else if selectedItems.length > 0}
      <div class="compare-placeholder">
        <p>Select 2 or more items to see comparison</p>
      </div>
    {/if}
  </main>
</div>

<style>
  .compare-page {
    min-height: 100vh;
    background: #f8f9fa;
  }

  .header {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: none;
    border: none;
    font-size: 1rem;
    color: #666;
    cursor: pointer;
  }

  h1 {
    font-size: 1.25rem;
    margin: 0;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .type-selector {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .type-btn {
    flex: 1;
    padding: 1rem;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .type-btn:hover {
    border-color: #667eea;
  }

  .type-btn.active {
    border-color: #667eea;
    background: #f0f4ff;
  }

  .selection-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
  }

  .selection-info p {
    margin: 0;
    color: #666;
  }

  .selection-info span {
    font-weight: 600;
    color: #667eea;
  }

  .items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .item-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    border: 3px solid transparent;
    transition: all 0.2s;
  }

  .item-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  }

  .item-card.selected {
    border-color: #667eea;
  }

  .selected-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    z-index: 10;
  }

  .card-image {
    height: 160px;
    background-size: cover;
    background-position: center;
  }

  .card-content {
    padding: 1rem;
  }

  .card-content h3 {
    font-size: 1rem;
    margin: 0 0 0.25rem;
    color: #1a1a2e;
  }

  .location {
    color: #666;
    font-size: 0.875rem;
    margin: 0 0 0.5rem;
  }

  .rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .stars {
    font-weight: 600;
  }

  .reviews {
    color: #999;
    font-size: 0.875rem;
  }

  .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
    margin: 0 0 1rem;
  }

  .select-btn {
    width: 100%;
    padding: 0.75rem;
    background: #f0f4ff;
    color: #667eea;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .select-btn:hover {
    background: #667eea;
    color: white;
  }

  .select-btn.selected {
    background: #667eea;
    color: white;
  }

  .comparison-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    overflow-x: auto;
  }

  .comparison-section h2 {
    margin: 0 0 1.5rem;
    color: #1a1a2e;
  }

  .comparison-table {
    min-width: 600px;
  }

  .table-header {
    display: grid;
    grid-template-columns: 180px repeat(var(--items, 3), 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .feature-label {
    font-weight: 600;
    color: #666;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
  }

  .item-header {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
  }

  .item-header img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 0.75rem;
  }

  .item-header h4 {
    margin: 0 0 0.25rem;
    color: #1a1a2e;
  }

  .item-location {
    color: #666;
    font-size: 0.875rem;
    margin: 0 0 1rem;
  }

  .book-btn {
    width: 100%;
    padding: 0.625rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
  }

  .table-row {
    display: grid;
    grid-template-columns: 180px repeat(var(--items, 3), 1fr);
    gap: 1rem;
    border-bottom: 1px solid #eee;
  }

  .table-row:last-child {
    border-bottom: none;
  }

  .feature-value {
    padding: 1rem;
    text-align: center;
    color: #666;
  }

  .feature-value.highlight {
    color: #28a745;
    font-weight: 600;
  }

  .total-row {
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 1rem;
  }

  .price-cell {
    padding: 1rem;
    text-align: center;
  }

  .price-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
  }

  .rating-cell {
    padding: 1rem;
    text-align: center;
  }

  .rating-value {
    font-weight: 600;
  }

  .review-count {
    display: block;
    font-size: 0.75rem;
    color: #999;
  }

  .compare-placeholder {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    color: #666;
  }

  @media (max-width: 768px) {
    .type-selector {
      flex-direction: column;
    }
    
    .items-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
