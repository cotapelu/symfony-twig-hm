<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { userStore } from '$lib/stores';

  let experience: any = null;
  let reviews: any[] = [];
  let loading = true;
  let error = '';
  let bookingLoading = false;
  
  let selectedDate: string | null = null;
  let participants = 1;
  let specialRequests = '';

  onMount(async () => {
    const experienceId = $page.params.id;
    if (experienceId) {
      await loadExperienceDetails(experienceId);
      await loadReviews(experienceId);
    }
  });

  async function loadExperienceDetails(experienceId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/experiences/${experienceId}`);
      if (!response.ok) throw new Error('Failed to load experience details');
      const data = await response.json();
      experience = data;
      loading = false;
    } catch (err) {
      error = (err as Error).message;
      loading = false;
    }
  }

  async function loadReviews(experienceId: string) {
    try {
      const response = await fetch(`http://localhost:9030/api/experiences/${experienceId}/reviews`);
      if (!response.ok) throw new Error('Failed to load reviews');
      const data = await response.json();
      reviews = data || [];
    } catch (err) {
      console.error('Failed to load reviews:', err);
    }
  }

  async function bookExperience() {
    if (!$userStore) {
      goto('/auth');
      return;
    }

    if (!selectedDate) {
      alert('Please select a date');
      return;
    }

    bookingLoading = true;
    try {
      const response = await fetch(`http://localhost:9030/api/experiences/${experience.id}/book`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          userId: $userStore.user?.id,
          date: selectedDate,
          time: '10:00',
          participants,
          specialRequests
        })
      });

      if (!response.ok) throw new Error('Booking failed');
      
      const data = await response.json();
      alert('Experience booked successfully! Booking ID: ' + data.id);
      goto('/bookings');
    } catch (err) {
      alert('Booking failed: ' + (err as Error).message);
    } finally {
      bookingLoading = false;
    }
  }

  function formatDuration(hours: number): string {
    if (hours < 24) return `${hours} hours`;
    const days = Math.floor(hours / 24);
    return `${days} days`;
  }

  function formatPrice(price: number): string {
    return `$${price.toFixed(0)}`;
  }

  function getCategoryIcon(category: string): string {
    const icons: { [key: string]: string } = {
      'ADVENTURE': '🏔️', 'CULTURAL': '🏛️', 'FOOD': '🍽️',
      'WELLNESS': '🧘', 'ARTS': '🎨', 'SPORTS': '⚽',
      'NATURE': '🌿', 'NIGHTLIFE': '🌃', 'FAMILY': '👨‍👩‍👧‍👦', 'EDUCATIONAL': '📚'
    };
    return icons[category] || '✨';
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
  <title>{experience?.title || 'Experience Details'} - Travel App</title>
</svelte:head>

<div class="experience-detail-page">
  <header class="header">
    <div class="header-content">
      <a href="/experiences" class="back-link">← Back to Experiences</a>
    </div>
  </header>

  {#if loading}
    <div class="loading">
      <div class="spinner"></div>
      <p>Loading experience details...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>{error}</p>
      <button class="retry-btn" on:click={() => loadExperienceDetails($page.params.id)}>Try Again</button>
    </div>
  {:else if experience}
    <div class="experience-hero">
      <div class="hero-image">
        {#if experience.images && experience.images.length > 0}
          <img src={experience.images[0]} alt={experience.title} />
        {:else}
          <div class="placeholder-image">{getCategoryIcon(experience.category)}</div>
        {/if}
        <div class="hero-overlay">
          <div class="hero-content">
            <span class="category">{getCategoryIcon(experience.category)} {experience.category}</span>
            <h1>{experience.title}</h1>
            <p class="location">📍 {experience.location}</p>
            <div class="hero-meta">
              <span class="rating">⭐ {experience.rating.toFixed(1)} ({experience.reviewCount})</span>
              <span class="duration">⏱️ {formatDuration(experience.duration)}</span>
              <span class="group-size">👥 Max {experience.maxParticipants}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <main class="main-content">
      <div class="content-grid">
        <div class="main-info">
          <section class="section">
            <h2>About This Experience</h2>
            <p class="description">{experience.description}</p>
          </section>

          <section class="section">
            <h2>Highlights</h2>
            <div class="highlights-list">
              {#each experience.highlights || [] as highlight}
                <div class="highlight-item">✦ {highlight}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>What's Included</h2>
            <div class="includes-list">
              {#each experience.includes || [] as item}
                <div class="include-item">✓ {item}</div>
              {/each}
            </div>
          </section>

          <section class="section">
            <h2>About Your Host</h2>
            <div class="host-info">
              <div class="host-avatar">👤</div>
              <div class="host-details">
                <h4>{experience.hostName}</h4>
                <p>{experience.hostBio}</p>
              </div>
            </div>
          </section>

          {#if reviews.length > 0}
            <section class="section">
              <h2>Reviews ({reviews.length})</h2>
              <div class="reviews-list">
                {#each reviews.slice(0, 3) as review}
                  <div class="review-card">
                    <div class="review-header">
                      <span class="review-rating">{'⭐'.repeat(review.rating)}</span>
                    </div>
                    <h4>{review.title}</h4>
                    <p>{review.comment}</p>
                  </div>
                {/each}
              </div>
            </section>
          {/if}
        </div>

        <aside class="booking-sidebar">
          <div class="booking-card">
            <h3>Book This Experience</h3>
            
            <div class="price-display">
              <span class="price">{formatPrice(experience.pricePerPerson)}</span>
              <span class="per-person">/person</span>
            </div>

            <div class="available-dates">
              <h4>Select Date</h4>
              <div class="dates-grid">
                {#each experience.availableDates || [] as date}
                  <button 
                    class="date-btn" 
                    class:selected={selectedDate === date}
                    on:click={() => selectedDate = date}
                  >
                    {formatDate(date)}
                  </button>
                {/each}
              </div>
            </div>

            <div class="input-group">
              <label>Number of Participants</label>
              <select bind:value={participants}>
                {#each Array.from({length: Math.min(experience.maxParticipants, 10)}, (_, i) => i + 1) as num}
                  <option value={num}>{num} {num === 1 ? 'Person' : 'People'}</option>
                {/each}
              </select>
            </div>

            <div class="input-group">
              <label>Special Requests</label>
              <textarea bind:value={specialRequests} placeholder="Dietary restrictions, accessibility needs, etc." rows="3"></textarea>
            </div>

            <div class="total-section">
              <div class="total-row">
                <span>{formatPrice(experience.pricePerPerson)} × {participants}</span>
                <span>{formatPrice(experience.pricePerPerson * participants)}</span>
              </div>
              <div class="total-row grand-total">
                <span>Total</span>
                <span>{formatPrice(experience.pricePerPerson * participants)}</span>
              </div>
            </div>

            <button 
              class="book-btn" 
              disabled={!selectedDate || bookingLoading}
              on:click={bookExperience}
            >
              {#if bookingLoading}
                <span class="btn-spinner"></span> Processing...
              {:else}
                Book Experience
              {/if}
            </button>
          </div>
        </aside>
      </div>
    </main>
  {/if}
</div>

<style>
  .experience-detail-page {
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

  .experience-hero {
    position: relative;
    height: 400px;
    overflow: hidden;
  }

  .hero-image {
    width: 100%;
    height: 100%;
  }

  .hero-image img {
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
    font-size: 8rem;
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
  }

  .hero-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    padding: 2rem;
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
    color: white;
  }

  .category {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .hero-content h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
  }

  .location {
    margin: 0.25rem 0;
    font-size: 1rem;
    opacity: 0.9;
  }

  .hero-meta {
    display: flex;
    gap: 1.5rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
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

  .description {
    color: #64748b;
    line-height: 1.6;
  }

  .highlights-list, .includes-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .highlight-item {
    color: #7c3aed;
    font-weight: 500;
  }

  .include-item {
    color: #059669;
  }

  .host-info {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }

  .host-avatar {
    font-size: 3rem;
  }

  .host-details h4 {
    margin: 0 0 0.25rem 0;
    color: #1e293b;
  }

  .host-details p {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .review-card {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .review-header {
    margin-bottom: 0.5rem;
  }

  .review-card h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .review-card p {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
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
    margin: 0 0 1rem 0;
    color: #1e293b;
  }

  .price-display {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .price {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
  }

  .per-person {
    color: #64748b;
    font-size: 1rem;
  }

  .available-dates {
    margin-bottom: 1rem;
  }

  .available-dates h4 {
    margin: 0 0 0.75rem 0;
    color: #1e293b;
    font-size: 0.875rem;
  }

  .dates-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .date-btn {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
  }

  .date-btn:hover {
    border-color: #667eea;
  }

  .date-btn.selected {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .input-group {
    margin-bottom: 1rem;
  }

  .input-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .input-group select,
  .input-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
  }

  .input-group textarea {
    resize: vertical;
  }

  .total-section {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
  }

  .total-row.grand-total {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    padding-top: 0.5rem;
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
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
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
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
  }

  @media (max-width: 968px) {
    .content-grid {
      grid-template-columns: 1fr;
    }

    .booking-sidebar {
      position: static;
    }

    .experience-hero {
      height: 300px;
    }

    .hero-content h1 {
      font-size: 1.75rem;
    }

    .hero-meta {
      flex-direction: column;
      gap: 0.5rem;
    }
  }
</style>
