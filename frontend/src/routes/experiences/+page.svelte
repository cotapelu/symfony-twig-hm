<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { bookingService } from '$lib/services/booking';

  let experiences: any[] = [];
  let categories: string[] = [];
  let loading = true;
  let error = '';
  
  let searchParams = {
    query: '',
    category: '',
    city: ''
  };

  onMount(async () => {
    await loadExperiences();
  });

  async function loadExperiences() {
    loading = true;
    error = '';
    
    try {
      const data = await bookingService.getMyBookings();
      experiences = data.bookings || [];
    } catch (err) {
      error = 'Failed to load experiences';
    }
    loading = false;
  }

  async function loadCategories() {
    // Categories can be added later
  }

  function handleSearch() {
    loadExperiences();
  }

  function viewExperienceDetails(experienceId: string) {
    goto(`/experiences/${experienceId}`);
  }

  function formatDuration(hours: number): string {
    if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''}`;
    const days = Math.floor(hours / 24);
    return `${days} day${days > 1 ? 's' : ''}`;
  }

  function getCategoryIcon(category: string): string {
    const icons: { [key: string]: string } = {
      'ADVENTURE': '🏔️',
      'CULTURAL': '🏛️',
      'FOOD': '🍽️',
      'WELLNESS': '🧘',
      'ARTS': '🎨',
      'SPORTS': '⚽',
      'NATURE': '🌿',
      'NIGHTLIFE': '🌃',
      'FAMILY': '👨‍👩‍👧‍👦',
      'EDUCATIONAL': '📚'
    };
    return icons[category] || '✨';
  }

  function formatPrice(price: number): string {
    return `$${price.toFixed(0)}`;
  }
</script>

<svelte:head>
  <title>Experiences - Travel Booking App</title>
</svelte:head>

<div class="experiences-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>Unforgettable Experiences</h1>
    </div>
  </header>

  <div class="search-section">
    <div class="search-form">
      <div class="search-field">
        <label for="query">What do you want to do?</label>
        <input
          id="query"
          type="text"
          bind:value={searchParams.query}
          placeholder="Kayaking, cooking class, yoga..."
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
        <label for="city">Location</label>
        <input
          id="city"
          type="text"
          bind:value={searchParams.city}
          placeholder="City or region"
        />
      </div>
      
      <button class="search-btn" on:click={handleSearch}>
        Find Experiences
      </button>
    </div>
  </div>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Finding amazing experiences...</p>
      </div>
    {:else if error}
      <div class="error">
        <p>{error}</p>
        <button class="retry-btn" on:click={loadExperiences}>Try Again</button>
      </div>
    {:else if experiences.length === 0}
      <div class="empty-state">
        <div class="empty-icon">✨</div>
        <h3>No experiences found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    {:else}
      <div class="results-header">
        <h2>{experiences.length} Experiences Found</h2>
      </div>
      
      <div class="experiences-grid">
        {#each experiences as experience}
          <div class="experience-card" on:click={() => viewExperienceDetails(experience.id)}>
            <div class="experience-image">
              {#if experience.images && experience.images.length > 0}
                <img src={experience.images[0]} alt={experience.title} />
              {:else}
                <div class="placeholder-image">{getCategoryIcon(experience.category)}</div>
              {/if}
              <div class="experience-category">{getCategoryIcon(experience.category)} {experience.category}</div>
              <div class="experience-rating">
                ⭐ {experience.rating.toFixed(1)} ({experience.reviewCount})
              </div>
            </div>
            
            <div class="experience-info">
              <h3 class="experience-title">{experience.title}</h3>
              <p class="experience-location">📍 {experience.location}</p>
              <p class="experience-host">👤 Hosted by {experience.hostName}</p>
              <p class="experience-description">{experience.description}</p>
              
              <div class="experience-highlights">
                {#each experience.highlights.slice(0, 3) as highlight}
                  <span class="highlight-tag">✓ {highlight}</span>
                {/each}
              </div>
              
              <div class="experience-footer">
                <div class="experience-meta">
                  <span class="duration">⏱️ {formatDuration(experience.duration)}</span>
                  <span class="group-size">👥 Up to {experience.maxParticipants}</span>
                </div>
                <div class="experience-price">
                  <span class="price-value">{formatPrice(experience.pricePerPerson)}</span>
                  <span class="price-unit">/person</span>
                </div>
                <button class="book-btn">Book Now</button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </main>
</div>

<style>
  .experiences-page {
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

  .experiences-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
  }

  .experience-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .experience-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  }

  .experience-image {
    position: relative;
    height: 220px;
    background: #e2e8f0;
  }

  .experience-image img {
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
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
  }

  .experience-category {
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

  .experience-rating {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
  }

  .experience-info {
    padding: 1.5rem;
  }

  .experience-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .experience-location {
    margin: 0 0 0.25rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .experience-host {
    margin: 0 0 0.75rem 0;
    color: #059669;
    font-size: 0.875rem;
  }

  .experience-description {
    margin: 0 0 1rem 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .experience-highlights {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .highlight-tag {
    font-size: 0.75rem;
    color: #7c3aed;
    background: #ede9fe;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
  }

  .experience-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .experience-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .experience-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }

  .price-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .price-unit {
    font-size: 0.875rem;
    color: #64748b;
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

    .experiences-grid {
      grid-template-columns: 1fr;
    }

    .experience-footer {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
