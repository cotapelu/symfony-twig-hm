<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';
  import { bookingService } from '$lib/services/booking';
  import type { Booking } from '$lib/services/booking';

  let loading = true;
  let stats = {
    totalBookings: 0,
    upcomingTrips: 0,
    savedHotels: 0,
    totalSpent: 0
  };

  let recentBookings: Booking[] = [];

  onMount(() => {
    const unsubscribe = userStore.subscribe(state => {
      if (!state.isAuthenticated || !state.user) {
        goto('/auth');
        return;
      }
    });

    // Async body in an inner IIFE: onMount must return the cleanup synchronously
    // (an async callback would return Promise<cleanup> and break the contract).
    void (async () => {
      loading = true;
      try {
        const data = await bookingService.getMyBookings(1, 10);
        recentBookings = data.bookings?.slice(0, 5) || [];
        stats.totalBookings = data.bookings?.length || 0;
        stats.upcomingTrips = data.bookings?.filter((b: Booking) => 
          b.checkInDate && new Date(b.checkInDate) > new Date()
        ).length || 0;
      } catch (err) {
        console.error('Failed to load bookings:', err);
      } finally {
        loading = false;
      }
    })();

    return () => unsubscribe();
  });

  function logout() {
    userStore.logout();
    goto('/auth');
  }

  function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function getStatusColor(status: string) {
    switch (status) {
      case 'CONFIRMED': return 'status-confirmed';
      case 'PENDING': return 'status-pending';
      case 'CANCELLED': return 'status-cancelled';
      default: return 'status-default';
    }
  }
</script>

<svelte:head>
  <title>Dashboard - Hotel Booking App</title>
</svelte:head>

<div class="dashboard">
  <header class="header">
    <div class="header-content">
      <h1>Dashboard</h1>
      <div class="user-menu">
        <span class="user-name">{$userStore?.user?.name || 'User'}</span>
        <button class="logout-btn" on:click={logout}>Logout</button>
      </div>
    </div>
  </header>

  <main class="main-content">
    <div class="welcome-section">
      <h2>Welcome back, {$userStore?.user?.name || 'User'}!</h2>
      <p>Here's what's happening with your bookings</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">🏨</div>
        <div class="stat-value">{stats.totalBookings}</div>
        <div class="stat-label">Total Bookings</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✈️</div>
        <div class="stat-value">{stats.upcomingTrips}</div>
        <div class="stat-label">Upcoming Trips</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">❤️</div>
        <div class="stat-value">{stats.savedHotels}</div>
        <div class="stat-label">Saved Hotels</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value">${stats.totalSpent}</div>
        <div class="stat-label">Total Spent</div>
      </div>
    </div>

    <div class="actions-grid">
      <a href="/hotels" class="action-card">
        <div class="action-icon">🏨</div>
        <h3>Hotels</h3>
        <p>Find and book accommodation</p>
      </a>
      <a href="/flights" class="action-card">
        <div class="action-icon">✈️</div>
        <h3>Flights</h3>
        <p>Search and book flights</p>
      </a>
      <a href="/cars" class="action-card">
        <div class="action-icon">🚗</div>
        <h3>Car Rental</h3>
        <p>Rent a car for your journey</p>
      </a>
      <a href="/restaurants" class="action-card">
        <div class="action-icon">🍽️</div>
        <h3>Restaurants</h3>
        <p>Book a table for dining</p>
      </a>
      <a href="/packages" class="action-card">
        <div class="action-icon">🎒</div>
        <h3>Travel Packages</h3>
        <p>All-inclusive vacation packages</p>
      </a>
      <a href="/events" class="action-card">
        <div class="action-icon">🎫</div>
        <h3>Events & Tickets</h3>
        <p>Concerts, shows & more</p>
      </a>
      <a href="/experiences" class="action-card">
        <div class="action-icon">✨</div>
        <h3>Experiences</h3>
        <p>Unique local activities</p>
      </a>
      <a href="/bookings" class="action-card">
        <div class="action-icon">📋</div>
        <h3>My Bookings</h3>
        <p>View all your reservations</p>
      </a>
      <a href="/profile" class="action-card">
        <div class="action-icon">👤</div>
        <h3>Profile</h3>
        <p>Manage your account settings</p>
      </a>
    </div>

    <div class="recent-bookings">
      <div class="section-header">
        <h3>Recent Bookings</h3>
        <a href="/bookings" class="view-all">View All</a>
      </div>

      {#if loading}
        <div class="loading">Loading...</div>
      {:else if recentBookings.length === 0}
        <div class="empty-state">
          <div class="empty-icon">🏨</div>
          <p>No bookings yet</p>
          <a href="/hotels" class="btn-primary">Book Your First Hotel</a>
        </div>
      {:else}
        <div class="bookings-list">
          {#each recentBookings as booking}
            <div class="booking-card">
              <div class="booking-info">
                <h4>Hotel #{booking.hotelId}</h4>
                <div class="booking-dates">
                  {formatDate(booking.checkInDate)} - {formatDate(booking.checkOutDate)}
                </div>
                <div class="booking-guests">{booking.guestCount} guests</div>
              </div>
              <div class="booking-status">
                <span class="status-badge {getStatusColor(booking.bookingStatus)}">
                  {booking.bookingStatus}
                </span>
                <div class="booking-price">${booking.finalPrice}</div>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  </main>
</div>

<style>
  .dashboard {
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
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
  }

  .user-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .user-name {
    color: #64748b;
  }

  .logout-btn {
    padding: 0.5rem 1rem;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .logout-btn:hover {
    background: #dc2626;
  }

  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .welcome-section {
    margin-bottom: 2rem;
  }

  .welcome-section h2 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .welcome-section p {
    margin: 0;
    color: #64748b;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  .stat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .stat-label {
    color: #64748b;
    font-size: 0.875rem;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .action-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .action-icon {
    font-size: 2rem;
    margin-bottom: 0.75rem;
  }

  .action-card h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .action-card p {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .recent-bookings {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .section-header h3 {
    margin: 0;
    color: #1e293b;
  }

  .view-all {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
  }

  .view-all:hover {
    text-decoration: underline;
  }

  .loading {
    text-align: center;
    padding: 2rem;
    color: #64748b;
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .empty-state p {
    color: #64748b;
    margin-bottom: 1rem;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }

  .booking-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .booking-dates {
    color: #64748b;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
  }

  .booking-guests {
    color: #94a3b8;
    font-size: 0.75rem;
  }

  .booking-status {
    text-align: right;
  }

  .status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
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

  .status-default {
    background: #f1f5f9;
    color: #475569;
  }

  .booking-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 1rem;
    }

    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .booking-card {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .booking-status {
      text-align: left;
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  }
</style>