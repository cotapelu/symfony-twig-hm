<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto } from '$app/navigation';

  interface BookingDetails {
    id: string;
    type: 'hotel' | 'flight' | 'car' | 'restaurant' | 'package' | 'event' | 'experience';
    name: string;
    location: string;
    image: string;
    date: string;
    time?: string;
    confirmationCode: string;
    status: 'confirmed' | 'pending' | 'cancelled';
    totalPrice: number;
    guestName: string;
    guestEmail: string;
    details: Record<string, string>;
  }

  let booking: BookingDetails | null = null;
  let loading = true;
  let error = '';

  onMount(() => {
    loadBookingDetails();
  });

  function loadBookingDetails() {
    loading = true;
    try {
      // Get booking data from URL params or localStorage
      const bookingId = $page.url.searchParams.get('id');
      const type = $page.url.searchParams.get('type');
      
      // For demo, create sample confirmation
      booking = {
        id: bookingId || 'BK-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
        type: (type as BookingDetails['type']) || 'hotel',
        name: 'Grand Plaza Hotel & Suites',
        location: '123 Main Street, New York, NY 10001',
        image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
        date: new Date().toISOString().split('T')[0],
        time: '3:00 PM',
        confirmationCode: 'CP-' + Math.random().toString(36).substr(2, 6).toUpperCase(),
        status: 'confirmed',
        totalPrice: 456.00,
        guestName: 'John Doe',
        guestEmail: 'john.doe@email.com',
        details: {
          'Check-in': 'Dec 15, 2024',
          'Check-out': 'Dec 18, 2024',
          'Room Type': 'Deluxe Suite',
          'Guests': '2 Adults',
          'Nights': '3'
        }
      };
    } catch (err) {
      error = 'Failed to load booking confirmation';
    } finally {
      loading = false;
    }
  }

  function getTypeIcon(type: string): string {
    const icons: Record<string, string> = {
      hotel: '🏨',
      flight: '✈️',
      car: '🚗',
      restaurant: '🍽️',
      package: '📦',
      event: '🎫',
      experience: '🎯'
    };
    return icons[type] || '✅';
  }

  function getTypeLabel(type: string): string {
    const labels: Record<string, string> = {
      hotel: 'Hotel Booking',
      flight: 'Flight Booking',
      car: 'Car Rental',
      restaurant: 'Restaurant Reservation',
      package: 'Travel Package',
      event: 'Event Ticket',
      experience: 'Experience Booking'
    };
    return labels[type] || 'Booking';
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function formatTime(timeStr: string): string {
    return timeStr;
  }

  function getPriceDisplay(price: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(price);
  }

  function downloadConfirmation() {
    window.print();
  }

  function addToCalendar() {
    // Create calendar event
    const event = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'BEGIN:VEVENT',
      `SUMMARY:Your ${getTypeLabel(booking?.type || '')} - ${booking?.confirmationCode}`,
      `DTSTART:${booking?.date?.replace(/-/g, '')}`,
      `DTEND:${booking?.date?.replace(/-/g, '')}`,
      `DESCRIPTION:Booking Confirmation: ${booking?.confirmationCode}\\n${booking?.name}`,
      `LOCATION:${booking?.location}`,
      'END:VEVENT',
      'END:VCALENDAR'
    ].join('\n');

    const blob = new Blob([event], { type: 'text/calendar' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `booking-${booking?.confirmationCode}.ics`;
    a.click();
    URL.revokeObjectURL(url);
  }
</script>

<svelte:head>
  <title>Booking Confirmed - Travel App</title>
</svelte:head>

<div class="confirmation-page">
  {#if loading}
    <div class="loading-state">
      <div class="spinner"></div>
      <p>Loading confirmation...</p>
    </div>
  {:else if booking}
    <div class="confirmation-container">
      <!-- Success Header -->
      <div class="success-header">
        <div class="success-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        <p>Your {getTypeLabel(booking.type)} has been successfully booked</p>
      </div>

      <!-- Confirmation Code -->
      <div class="confirmation-code-card">
        <div class="code-label">Confirmation Code</div>
        <div class="code-value">{booking.confirmationCode}</div>
        <p>Please save this code for your records</p>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <!-- Booking Details Card -->
        <div class="booking-card">
          <div class="card-image" style="background-image: url({booking.image})">
            <span class="type-badge">
              {getTypeIcon(booking.type)} {getTypeLabel(booking.type)}
            </span>
            <span class="status-badge" class:confirmed={booking.status === 'confirmed'}>
              {booking.status.toUpperCase()}
            </span>
          </div>
          
          <div class="card-content">
            <h2>{booking.name}</h2>
            <p class="location">📍 {booking.location}</p>
            
            <div class="datetime">
              <div class="datetime-item">
                <span class="label">Date</span>
                <span class="value">{formatDate(booking.date)}</span>
              </div>
              {#if booking.time}
                <div class="datetime-item">
                  <span class="label">Time</span>
                  <span class="value">{formatTime(booking.time)}</span>
                </div>
              {/if}
            </div>

            <!-- Additional Details -->
            <div class="details-list">
              {#each Object.entries(booking.details) as [key, value]}
                <div class="detail-item">
                  <span class="detail-label">{key}</span>
                  <span class="detail-value">{value}</span>
                </div>
              {/each}
            </div>
          </div>
        </div>

        <!-- Guest Info Card -->
        <div class="guest-card">
          <h3>Guest Information</h3>
          <div class="guest-info">
            <div class="info-item">
              <span class="label">Name</span>
              <span class="value">{booking.guestName}</span>
            </div>
            <div class="info-item">
              <span class="label">Email</span>
              <span class="value">{booking.guestEmail}</span>
            </div>
          </div>
        </div>

        <!-- Price Card -->
        <div class="price-card">
          <h3>Payment Summary</h3>
          <div class="price-breakdown">
            <div class="price-row">
              <span>Base Price</span>
              <span>{getPriceDisplay(booking.totalPrice * 0.9)}</span>
            </div>
            <div class="price-row">
              <span>Taxes & Fees</span>
              <span>{getPriceDisplay(booking.totalPrice * 0.1)}</span>
            </div>
            <div class="price-row total">
              <span>Total Paid</span>
              <span>{getPriceDisplay(booking.totalPrice)}</span>
            </div>
          </div>
          <div class="payment-status">
            <span class="label">Payment Status</span>
            <span class="value status-paid">✓ Paid</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button class="secondary-btn" onclick={addToCalendar}>
          📅 Add to Calendar
        </button>
        <button class="secondary-btn" onclick={downloadConfirmation}>
          📄 Download Confirmation
        </button>
        <button class="primary-btn" onclick={() => goto('/bookings')}>
          View My Bookings
        </button>
      </div>

      <!-- Additional Info -->
      <div class="additional-info">
        <div class="info-section">
          <h4>Need Help?</h4>
          <p>Contact our support team for any questions about your booking</p>
          <button class="support-btn">Contact Support</button>
        </div>
        
        <div class="info-section">
          <h4>Modification Policy</h4>
          <p>Free cancellation up to 24 hours before your booking date</p>
          <button class="policy-btn" onclick={() => goto('/help#cancellation')}>
            View Cancellation Policy
          </button>
        </div>
      </div>
    </div>
  {:else}
    <div class="error-state">
      <div class="error-icon">⚠️</div>
      <h2>Booking Not Found</h2>
      <p>{error || 'We could not find the booking you are looking for'}</p>
      <button class="primary-btn" onclick={() => goto('/dashboard')}>
        Go to Dashboard
      </button>
    </div>
  {/if}
</div>

<style>
  .confirmation-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem 1rem;
  }

  .loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 50vh;
    color: white;
  }

  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .confirmation-container {
    max-width: 800px;
    margin: 0 auto;
  }

  .success-header {
    text-align: center;
    color: white;
    margin-bottom: 2rem;
  }

  .success-icon {
    width: 80px;
    height: 80px;
    background: white;
    color: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 1rem;
  }

  .success-header h1 {
    font-size: 2rem;
    margin: 0 0 0.5rem;
  }

  .success-header p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0;
  }

  .confirmation-code-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .code-label {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .code-value {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    letter-spacing: 2px;
    margin-bottom: 0.5rem;
  }

  .confirmation-code-card p {
    font-size: 0.875rem;
    color: #999;
    margin: 0;
  }

  .main-content {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .booking-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .card-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
  }

  .type-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
  }

  .status-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ffc107;
    color: #333;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge.confirmed {
    background: #28a745;
    color: white;
  }

  .card-content {
    padding: 1.5rem;
  }

  .card-content h2 {
    font-size: 1.5rem;
    color: #1a1a2e;
    margin: 0 0 0.5rem;
  }

  .location {
    color: #666;
    margin: 0 0 1.5rem;
  }

  .datetime {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
  }

  .datetime-item .label {
    display: block;
    font-size: 0.75rem;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
  }

  .datetime-item .value {
    font-weight: 600;
    color: #1a1a2e;
  }

  .details-list {
    display: grid;
    gap: 0.75rem;
  }

  .detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
  }

  .detail-label {
    color: #666;
  }

  .detail-value {
    font-weight: 500;
    color: #1a1a2e;
  }

  .guest-card, .price-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .guest-card h3, .price-card h3 {
    font-size: 1.125rem;
    color: #1a1a2e;
    margin: 0 0 1rem;
  }

  .guest-info {
    display: grid;
    gap: 0.75rem;
  }

  .info-item {
    display: flex;
    justify-content: space-between;
  }

  .info-item .label {
    color: #666;
  }

  .info-item .value {
    font-weight: 500;
    color: #1a1a2e;
  }

  .price-breakdown {
    margin-bottom: 1rem;
  }

  .price-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: #666;
  }

  .price-row.total {
    border-top: 2px solid #eee;
    padding-top: 1rem;
    margin-top: 0.5rem;
    font-weight: 600;
    font-size: 1.125rem;
    color: #1a1a2e;
  }

  .payment-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #eee;
  }

  .status-paid {
    color: #28a745;
    font-weight: 600;
  }

  .action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .primary-btn, .secondary-btn {
    flex: 1;
    padding: 1rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .primary-btn {
    background: white;
    color: #667eea;
    border: none;
  }

  .secondary-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
  }

  .primary-btn:hover, .secondary-btn:hover {
    transform: translateY(-2px);
  }

  .additional-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .info-section {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 1.5rem;
    color: white;
  }

  .info-section h4 {
    font-size: 1rem;
    margin: 0 0 0.5rem;
  }

  .info-section p {
    font-size: 0.875rem;
    opacity: 0.9;
    margin: 0 0 1rem;
  }

  .support-btn, .policy-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 0.625rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background 0.2s;
  }

  .support-btn:hover, .policy-btn:hover {
    background: rgba(255,255,255,0.3);
  }

  .error-state {
    text-align: center;
    color: white;
    padding: 4rem 2rem;
  }

  .error-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .error-state h2 {
    margin-bottom: 0.5rem;
  }

  .error-state p {
    opacity: 0.9;
    margin-bottom: 2rem;
  }

  @media (max-width: 640px) {
    .action-buttons {
      flex-direction: column;
    }
    
    .additional-info {
      grid-template-columns: 1fr;
    }
    
    .datetime {
      flex-direction: column;
      gap: 1rem;
    }
  }
</style>
