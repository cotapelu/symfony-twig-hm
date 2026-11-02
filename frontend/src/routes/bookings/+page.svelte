<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';
  import { bookingService, type Booking } from '$lib/services/booking';

  let bookings: Booking[] = [];
  let loading = true;
  let error = '';
  let activeTab: 'upcoming' | 'past' | 'cancelled' = 'upcoming';

  onMount(async () => {
    if (!$userStore) {
      goto('/auth');
      return;
    }
    await loadBookings();
  });

  async function loadBookings() {
    loading = true;
    error = '';
    
    try {
      const data = await bookingService.getMyBookings();
      bookings = data.bookings || [];
    } catch (err) {
      error = 'Failed to load bookings';
      console.error(err);
    }
    loading = false;
  }

  async function cancelBooking(bookingId: string) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;
    
    try {
      await bookingService.cancelBooking(bookingId);
      await loadBookings();
    } catch (err) {
      alert('Failed to cancel booking');
    }
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function getFilteredBookings() {
    const now = new Date();
    switch (activeTab) {
      case 'upcoming':
        return bookings.filter(b => new Date(b.checkInDate) >= now && b.bookingStatus !== 'CANCELLED');
      case 'past':
        return bookings.filter(b => new Date(b.checkOutDate) < now && b.bookingStatus !== 'CANCELLED');
      case 'cancelled':
        return bookings.filter(b => b.bookingStatus === 'CANCELLED');
      default:
        return bookings;
    }
  }

  function getStatusColor(status: string): string {
    switch (status) {
      case 'CONFIRMED': return 'status-confirmed';
      case 'PENDING': return 'status-pending';
      case 'CANCELLED': return 'status-cancelled';
      case 'COMPLETED': return 'status-completed';
      default: return 'status-default';
    }
  }
</script>

<svelte:head>
  <title>My Bookings - Hotel Booking App</title>
</svelte:head>

<div class="bookings-page">
  <header class="header">
    <div class="header-content">
      <a href="/dashboard" class="back-link">← Back to Dashboard</a>
      <h1>My Bookings</h1>
    </div>
  </header>

  <main class="main-content">
    <div class="tabs">
      <button 
        class="tab" 
        class:active={activeTab === 'upcoming'}
        on:click={() => activeTab = 'upcoming'}
      >
        Upcoming
      </button>
      <button 
        class="tab" 
        class:active={activeTab === 'past'}
        on:click={() => activeTab = 'past'}
      >
        Past
      </button>
      <button 
        class="tab" 
        class:active={activeTab === 'cancelled'}
        on:click={() => activeTab = 'cancelled'}
      >
        Cancelled
      </button>
    </div>

    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading your bookings...</p>
      </div>
    {:else if error}
      <div class="error">
        <p>{error}</p>
        <button class="retry-btn" on:click={loadBookings}>Try Again</button>
      </div>
    {:else if getFilteredBookings().length === 0}
      <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No {activeTab} bookings</h3>
        <p>
          {#if activeTab === 'upcoming'}
            You don't have any upcoming trips. Time to plan one!
          {:else if activeTab === 'past'}
            No past bookings yet.
          {:else}
            No cancelled bookings.
          {/if}
        </p>
        {#if activeTab === 'upcoming'}
          <a href="/hotels" class="btn-primary">Explore Hotels</a>
        {/if}
      </div>
    {:else}
      <div class="bookings-list">
        {#each getFilteredBookings() as booking}
          <div class="booking-card">
            <div class="booking-header">
              <div class="booking-dates">
                <div class="date-range">
                  <span class="check-in">{formatDate(booking.checkInDate)}</span>
                  <span class="arrow">→</span>
                  <span class="check-out">{formatDate(booking.checkOutDate)}</span>
                </div>
                <div class="nights">
                  {Math.ceil((new Date(booking.checkOutDate).getTime() - new Date(booking.checkInDate).getTime()) / (1000 * 60 * 60 * 24))} nights
                </div>
              </div>
              <span class="status-badge {getStatusColor(booking.bookingStatus)}">
                {booking.bookingStatus}
              </span>
            </div>

            <div class="booking-details">
              <div class="hotel-info">
                <h3>Hotel #{booking.hotelId}</h3>
                <p class="room-type">Room Type: {booking.roomTypeId}</p>
                <p class="guests">{booking.guestCount} guests</p>
                {#if booking.specialRequests}
                  <p class="special-requests">Note: {booking.specialRequests}</p>
                {/if}
              </div>
              <div class="price-info">
                <div class="total-price">${booking.finalPrice}</div>
                <div class="price-label">Total paid</div>
              </div>
            </div>

            {#if booking.bookingStatus !== 'CANCELLED' && booking.bookingStatus !== 'COMPLETED'}
              <div class="booking-actions">
                <button class="action-btn secondary" on:click={() => cancelBooking(booking.id)}>
                  Cancel Booking
                </button>
              </div>
            {/if}
          </div>
        {/each}
      </div>
    {/if}
  </main>
</div>

<style>
  .bookings-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem 2rem;
  }

  .header-content {
    max-width: 1000px;
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

  .main-content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
  }

  .tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: white;
    padding: 0.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .tab {
    flex: 1;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    color: #64748b;
    transition: all 0.2s;
  }

  .tab:hover {
    color: #1e293b;
    background: #f1f5f9;
  }

  .tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
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
    background: white;
    border-radius: 12px;
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
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    margin-bottom: 1.5rem;
  }

  .btn-primary {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
  }

  .bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .booking-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .booking-dates {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .date-range {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
  }

  .arrow {
    color: #667eea;
  }

  .nights {
    font-size: 0.875rem;
    color: #64748b;
  }

  .status-badge {
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .status-confirmed {
    background: #d1fae5;
    color: #065f46;
  }

  .status-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .status-cancelled {
    background: #fee2e2;
    color: #991b1b;
  }

  .status-completed {
    background: #e0e7ff;
    color: #3730a3;
  }

  .status-default {
    background: #f1f5f9;
    color: #475569;
  }

  .booking-details {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
  }

  .hotel-info h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .hotel-info p {
    margin: 0 0 0.25rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .special-requests {
    font-style: italic;
    color: #94a3b8;
  }

  .price-info {
    text-align: right;
  }

  .total-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .price-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .booking-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .action-btn {
    padding: 0.625rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .action-btn.secondary {
    background: white;
    border: 1px solid #e2e8f0;
    color: #64748b;
  }

  .action-btn.secondary:hover {
    border-color: #ef4444;
    color: #ef4444;
  }

  @media (max-width: 640px) {
    .main-content {
      padding: 1rem;
    }

    .booking-header {
      flex-direction: column;
      gap: 1rem;
    }

    .booking-details {
      flex-direction: column;
      gap: 1rem;
    }

    .price-info {
      text-align: left;
    }
  }
</style>