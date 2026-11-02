<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  // Types
  interface DeliveryTimeline {
    status: string;
    timestamp: string;
    location?: string;
    description: string;
  }

  interface Delivery {
    id: string;
    trackingId: string;
    sender: string;
    senderAddress: string;
    recipient: string;
    recipientAddress: string;
    recipientPhone: string;
    packageDescription: string;
    weight: number;
    dimensions: string;
    status: 'pending' | 'picked_up' | 'in_transit' | 'out_for_delivery' | 'delivered' | 'cancelled' | 'returned';
    priority: 'standard' | 'express' | 'overnight';
    fee: number;
    estimatedDelivery: string;
    actualDelivery?: string;
    timeline: DeliveryTimeline[];
    createdAt: string;
    updatedAt: string;
  }

  // State
  let loading = true;
  let activeTab: 'active' | 'history' | 'track' = 'active';
  let deliveries: Delivery[] = [];
  let selectedDelivery: Delivery | null = null;
  let showDetailModal = false;
  let trackingInput = '';
  let trackingResult: Delivery | null = null;
  let trackingError = '';

  // Filters
  let filterStatus = 'all';
  let filterDate = 'all';
  let searchQuery = '';

  // Storage key
  const STORAGE_KEY = 'delivery_deliveries';

  onMount(() => {
    loadFromStorage();
  });

  function loadFromStorage() {
    loading = true;
    const stored = localStorage.getItem(STORAGE_KEY);

    if (stored) {
      deliveries = JSON.parse(stored);
    } else {
      // Initialize with sample deliveries
      deliveries = [
        {
          id: 'del_001',
          trackingId: 'PKG20260212001',
          sender: 'TechMart Electronics',
          senderAddress: '123 Nguyen Trai St, District 1, HCMC',
          recipient: 'John Doe',
          recipientAddress: '456 Le Van Sy St, District 3, HCMC',
          recipientPhone: '+84 912 345 678',
          packageDescription: 'Electronics - Laptop',
          weight: 2.5,
          dimensions: '35x25x5 cm',
          status: 'in_transit',
          priority: 'express',
          fee: 85000,
          estimatedDelivery: '2026-02-14',
          timeline: [
            { status: 'pending', timestamp: '2026-02-10T08:30:00', description: 'Shipment created' },
            { status: 'picked_up', timestamp: '2026-02-10T14:20:00', location: 'TechMart Electronics', description: 'Package picked up' },
            { status: 'in_transit', timestamp: '2026-02-11T09:15:00', location: 'HCMC Distribution Center', description: 'In transit to destination' },
            { status: 'in_transit', timestamp: '2026-02-12T06:45:00', location: 'District 3 Hub', description: 'Arrived at local hub' },
          ],
          createdAt: '2026-02-10T08:30:00',
          updatedAt: '2026-02-12T06:45:00'
        },
        {
          id: 'del_002',
          trackingId: 'PKG20260210002',
          sender: 'Fashion Outlet',
          senderAddress: '78 Dong Khoi St, District 1, HCMC',
          recipient: 'Jane Smith',
          recipientAddress: '102 Pasteur St, District 1, HCMC',
          recipientPhone: '+84 987 654 321',
          packageDescription: 'Clothing - Dresses',
          weight: 0.8,
          dimensions: '30x20x5 cm',
          status: 'out_for_delivery',
          priority: 'standard',
          fee: 45000,
          estimatedDelivery: '2026-02-12',
          timeline: [
            { status: 'pending', timestamp: '2026-02-08T10:00:00', description: 'Shipment created' },
            { status: 'picked_up', timestamp: '2026-02-08T16:00:00', location: 'Fashion Outlet', description: 'Package picked up' },
            { status: 'in_transit', timestamp: '2026-02-09T08:30:00', location: 'HCMC Distribution Center', description: 'In transit' },
            { status: 'in_transit', timestamp: '2026-02-11T14:00:00', location: 'District 1 Hub', description: 'Arrived at local hub' },
            { status: 'out_for_delivery', timestamp: '2026-02-12T07:00:00', location: 'District 1 Hub', description: 'Out for delivery' },
          ],
          createdAt: '2026-02-08T10:00:00',
          updatedAt: '2026-02-12T07:00:00'
        },
        {
          id: 'del_003',
          trackingId: 'PKG20260205003',
          sender: 'BookStore Co',
          senderAddress: '200 Ly Tu Trong St, District 1, HCMC',
          recipient: 'Mike Johnson',
          recipientAddress: '88 Vo Van Kiet St, District 5, HCMC',
          recipientPhone: '+84 933 111 222',
          packageDescription: 'Books - Novels',
          weight: 1.2,
          dimensions: '20x15x10 cm',
          status: 'delivered',
          priority: 'standard',
          fee: 35000,
          estimatedDelivery: '2026-02-08',
          actualDelivery: '2026-02-07T15:30:00',
          timeline: [
            { status: 'pending', timestamp: '2026-02-05T09:00:00', description: 'Shipment created' },
            { status: 'picked_up', timestamp: '2026-02-05T13:00:00', location: 'BookStore Co', description: 'Package picked up' },
            { status: 'in_transit', timestamp: '2026-02-06T08:00:00', location: 'HCMC Distribution Center', description: 'In transit' },
            { status: 'out_for_delivery', timestamp: '2026-02-07T06:30:00', location: 'District 5 Hub', description: 'Out for delivery' },
            { status: 'delivered', timestamp: '2026-02-07T15:30:00', location: 'Delivery Address', description: 'Delivered - Signed by recipient' },
          ],
          createdAt: '2026-02-05T09:00:00',
          updatedAt: '2026-02-07T15:30:00'
        },
        {
          id: 'del_004',
          trackingId: 'PKG20260212004',
          sender: 'FreshFood Market',
          senderAddress: '45 Co Bac St, District 1, HCMC',
          recipient: 'Sarah Lee',
          recipientAddress: '77 Nguyen Hue St, District 1, HCMC',
          recipientPhone: '+84 944 555 666',
          packageDescription: 'Fresh Food - Fruits',
          weight: 3.0,
          dimensions: '25x20x15 cm',
          status: 'pending',
          priority: 'overnight',
          fee: 120000,
          estimatedDelivery: '2026-02-13',
          timeline: [
            { status: 'pending', timestamp: '2026-02-12T18:00:00', description: 'Shipment created - Awaiting pickup' },
          ],
          createdAt: '2026-02-12T18:00:00',
          updatedAt: '2026-02-12T18:00:00'
        },
        {
          id: 'del_005',
          trackingId: 'PKG20260128005',
          sender: 'HomeDecor Store',
          senderAddress: '300 Nguyen Van Linh St, District 7, HCMC',
          recipient: 'Tom Wilson',
          recipientAddress: '55 Hai Ba Trung St, District 1, HCMC',
          recipientPhone: '+84 955 777 888',
          packageDescription: 'Home Decor - Vase',
          weight: 4.5,
          dimensions: '40x30x30 cm',
          status: 'returned',
          priority: 'standard',
          fee: 95000,
          estimatedDelivery: '2026-02-02',
          actualDelivery: '2026-02-05T10:00:00',
          timeline: [
            { status: 'pending', timestamp: '2026-01-28T11:00:00', description: 'Shipment created' },
            { status: 'picked_up', timestamp: '2026-01-28T15:00:00', location: 'HomeDecor Store', description: 'Package picked up' },
            { status: 'in_transit', timestamp: '2026-01-29T09:00:00', location: 'HCMC Distribution Center', description: 'In transit' },
            { status: 'delivered', timestamp: '2026-02-01T14:00:00', location: 'Delivery Address', description: 'Delivered' },
            { status: 'returned', timestamp: '2026-02-05T10:00:00', location: 'HCMC Distribution Center', description: 'Returned by recipient' },
          ],
          createdAt: '2026-01-28T11:00:00',
          updatedAt: '2026-02-05T10:00:00'
        }
      ];
      saveToStorage();
    }

    loading = false;
  }

  function saveToStorage() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(deliveries));
  }

  // Computed values
  $: activeDeliveries = deliveries.filter(d => 
    ['pending', 'picked_up', 'in_transit', 'out_for_delivery'].includes(d.status)
  );

  $: deliveryHistory = deliveries.filter(d => 
    ['delivered', 'cancelled', 'returned'].includes(d.status)
  );

  $: filteredActiveDeliveries = activeDeliveries.filter(d => {
    if (filterStatus !== 'all' && d.status !== filterStatus) return false;
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return d.trackingId.toLowerCase().includes(query) ||
             d.recipient.toLowerCase().includes(query) ||
             d.packageDescription.toLowerCase().includes(query);
    }
    return true;
  });

  $: filteredHistory = deliveryHistory.filter(d => {
    if (filterStatus !== 'all' && d.status !== filterStatus) return false;
    if (filterDate !== 'all') {
      const created = new Date(d.createdAt);
      const now = new Date();
      if (filterDate === 'week') {
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        if (created < weekAgo) return false;
      } else if (filterDate === 'month') {
        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        if (created < monthAgo) return false;
      }
    }
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return d.trackingId.toLowerCase().includes(query) ||
             d.recipient.toLowerCase().includes(query) ||
             d.packageDescription.toLowerCase().includes(query);
    }
    return true;
  });

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

  function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      pending: 'Pending',
      picked_up: 'Picked Up',
      in_transit: 'In Transit',
      out_for_delivery: 'Out for Delivery',
      delivered: 'Delivered',
      cancelled: 'Cancelled',
      returned: 'Returned'
    };
    return labels[status] || status;
  }

  function getStatusClass(status: string): string {
    const classes: Record<string, string> = {
      pending: 'status-pending',
      picked_up: 'status-picked',
      in_transit: 'status-transit',
      out_for_delivery: 'status-pending-delivery',
      delivered: 'status-delivered',
      cancelled: 'status-cancelled',
      returned: 'status-returned'
    };
    return classes[status] || 'status-default';
  }

  function getPriorityIcon(priority: string): string {
    const icons: Record<string, string> = {
      standard: '📦',
      express: '⚡',
      overnight: '🚀'
    };
    return icons[priority] || '📦';
  }

  function getPriorityClass(priority: string): string {
    const classes: Record<string, string> = {
      standard: 'priority-standard',
      express: 'priority-express',
      overnight: 'priority-overnight'
    };
    return classes[priority] || 'priority-standard';
  }

  // Actions
  function openDetail(delivery: Delivery) {
    selectedDelivery = delivery;
    showDetailModal = true;
  }

  function closeDetail() {
    showDetailModal = false;
    selectedDelivery = null;
  }

  function trackPackage() {
    trackingError = '';
    trackingResult = null;

    if (!trackingInput.trim()) {
      trackingError = 'Please enter a tracking number';
      return;
    }

    const found = deliveries.find(d => 
      d.trackingId.toLowerCase() === trackingInput.trim().toLowerCase()
    );

    if (found) {
      trackingResult = found;
    } else {
      trackingError = 'Package not found. Please check the tracking number.';
    }
  }

  function simulateStatusUpdate(delivery: Delivery) {
    const statusFlow = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
    const currentIndex = statusFlow.indexOf(delivery.status);

    if (currentIndex < statusFlow.length - 1 && currentIndex >= 0) {
      const newStatus = statusFlow[currentIndex + 1] as Delivery['status'];
      const newTimeline: DeliveryTimeline = {
        status: newStatus,
        timestamp: new Date().toISOString(),
        location: 'HCMC Distribution Center',
        description: `Package ${newStatus.replace('_', ' ')}`
      };

      delivery.status = newStatus;
      delivery.timeline = [...delivery.timeline, newTimeline];
      delivery.updatedAt = new Date().toISOString();

      if (newStatus === 'delivered') {
        delivery.actualDelivery = new Date().toISOString();
      }

      saveToStorage();
      deliveries = [...deliveries];
    }
  }

  function getTimelineIcon(timelineItem: DeliveryTimeline, index: number, total: number): string {
    if (index === total - 1) {
      return '📍';
    }
    const icons: Record<string, string> = {
      pending: '⏳',
      picked_up: '📦',
      in_transit: '🚚',
      out_for_delivery: '🏃',
      delivered: '✅',
      cancelled: '❌',
      returned: '↩️'
    };
    return icons[timelineItem.status] || '📌';
  }
</script>

<svelte:head>
  <title>Delivery - Hotel Booking App</title>
</svelte:head>

<div class="delivery-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>🚚 Delivery</h1>
        <p>Track and manage your packages</p>
      </div>
    </div>
  </header>

  <!-- Tabs -->
  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'active'} 
      on:click={() => activeTab = 'active'}
    >
      Active Deliveries
      {#if activeDeliveries.length > 0}
        <span class="badge">{activeDeliveries.length}</span>
      {/if}
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'history'} 
      on:click={() => activeTab = 'history'}
    >
      History
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'track'} 
      on:click={() => activeTab = 'track'}
    >
      Track Package
    </button>
  </nav>

  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading deliveries...</p>
      </div>
    {:else}
      <!-- Active Deliveries Tab -->
      {#if activeTab === 'active'}
        <div class="active-section" in:fade>
          <!-- Stats -->
          <div class="stats-row">
            <div class="stat-card">
              <div class="stat-icon">📦</div>
              <div class="stat-value">{activeDeliveries.length}</div>
              <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">🚚</div>
              <div class="stat-value">{activeDeliveries.filter(d => d.status === 'in_transit').length}</div>
              <div class="stat-label">In Transit</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">🏃</div>
              <div class="stat-value">{activeDeliveries.filter(d => d.status === 'out_for_delivery').length}</div>
              <div class="stat-label">Out for Delivery</div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">⏳</div>
              <div class="stat-value">{activeDeliveries.filter(d => d.status === 'pending').length}</div>
              <div class="stat-label">Pending</div>
            </div>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="search-box">
              <input 
                type="text" 
                placeholder="Search by tracking ID, recipient..." 
                bind:value={searchQuery}
              />
            </div>
            <div class="filter-group">
              <select bind:value={filterStatus}>
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="picked_up">Picked Up</option>
                <option value="in_transit">In Transit</option>
                <option value="out_for_delivery">Out for Delivery</option>
              </select>
            </div>
          </div>

          <!-- Active Deliveries List -->
          {#if filteredActiveDeliveries.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📦</div>
              <h3>No active deliveries</h3>
              <p>Your active shipments will appear here</p>
            </div>
          {:else}
            <div class="deliveries-grid">
              {#each filteredActiveDeliveries as delivery}
                <div class="delivery-card" on:click={() => openDetail(delivery)}>
                  <div class="card-header">
                    <div class="tracking-id">{delivery.trackingId}</div>
                    <span class="priority-badge {getPriorityClass(delivery.priority)}">
                      {getPriorityIcon(delivery.priority)} {delivery.priority}
                    </span>
                  </div>
                  
                  <div class="card-route">
                    <div class="route-point">
                      <span class="point-icon">📤</span>
                      <div class="point-info">
                        <span class="point-label">From</span>
                        <span class="point-value">{delivery.sender}</span>
                      </div>
                    </div>
                    <div class="route-line"></div>
                    <div class="route-point">
                      <span class="point-icon">📥</span>
                      <div class="point-info">
                        <span class="point-label">To</span>
                        <span class="point-value">{delivery.recipient}</span>
                      </div>
                    </div>
                  </div>

                  <div class="card-footer">
                    <div class="status-info">
                      <span class="status-badge {getStatusClass(delivery.status)}">
                        {getStatusLabel(delivery.status)}
                      </span>
                      <span class="eta">ETA: {formatDate(delivery.estimatedDelivery)}</span>
                    </div>
                    <div class="fee">₫{formatCurrency(delivery.fee)}</div>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {/if}

      <!-- History Tab -->
      {#if activeTab === 'history'}
        <div class="history-section" in:fade>
          <!-- Filters -->
          <div class="filters-bar">
            <div class="search-box">
              <input 
                type="text" 
                placeholder="Search history..." 
                bind:value={searchQuery}
              />
            </div>
            <div class="filter-group">
              <select bind:value={filterStatus}>
                <option value="all">All Status</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="returned">Returned</option>
              </select>
              <select bind:value={filterDate}>
                <option value="all">All Time</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
              </select>
            </div>
          </div>

          <!-- History List -->
          {#if filteredHistory.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📋</div>
              <h3>No delivery history</h3>
              <p>Completed deliveries will appear here</p>
            </div>
          {:else}
            <div class="history-list">
              {#each filteredHistory as delivery}
                <div class="history-item" on:click={() => openDetail(delivery)}>
                  <div class="history-icon">
                    {#if delivery.status === 'delivered'}✅{:else if delivery.status === 'cancelled'}❌{:else}↩️{/if}
                  </div>
                  <div class="history-info">
                    <div class="history-tracking">{delivery.trackingId}</div>
                    <div class="history-desc">{delivery.packageDescription}</div>
                    <div class="history-meta">
                      To: {delivery.recipient} • {formatDate(delivery.createdAt)}
                    </div>
                  </div>
                  <div class="history-right">
                    <span class="status-badge {getStatusClass(delivery.status)}">
                      {getStatusLabel(delivery.status)}
                    </span>
                    <div class="history-amount">₫{formatCurrency(delivery.fee)}</div>
                  </div>
                </div>
              {/each}
            </div>
          {/if}
        </div>
      {/if}

      <!-- Track Package Tab -->
      {#if activeTab === 'track'}
        <div class="track-section" in:fade>
          <div class="track-card">
            <h2>Track Your Package</h2>
            <p>Enter your tracking number to get real-time updates</p>
            
            <div class="track-input-group">
              <input 
                type="text" 
                placeholder="Enter tracking number (e.g., PKG20260212001)" 
                bind:value={trackingInput}
                on:keydown={(e) => e.key === 'Enter' && trackPackage()}
              />
              <button class="track-btn" on:click={trackPackage}>
                🔍 Track
              </button>
            </div>

            {#if trackingError}
              <div class="track-error">{trackingError}</div>
            {/if}

            {#if trackingResult}
              <div class="track-result" in:slide>
                <div class="result-header">
                  <div class="result-id">{trackingResult.trackingId}</div>
                  <span class="status-badge {getStatusClass(trackingResult.status)}">
                    {getStatusLabel(trackingResult.status)}
                  </span>
                </div>

                <div class="result-route">
                  <div class="route-end">
                    <span class="route-label">From</span>
                    <span class="route-value">{trackingResult.sender}</span>
                    <span class="route-address">{trackingResult.senderAddress}</span>
                  </div>
                  <div class="route-divider">→</div>
                  <div class="route-end">
                    <span class="route-label">To</span>
                    <span class="route-value">{trackingResult.recipient}</span>
                    <span class="route-address">{trackingResult.recipientAddress}</span>
                  </div>
                </div>

                <div class="result-timeline">
                  <h4>Tracking Timeline</h4>
                  <div class="timeline-list">
                    {#each trackingResult.timeline.slice().reverse() as item, i}
                      <div class="timeline-item" class:latest={i === 0}>
                        <div class="timeline-icon">{getTimelineIcon(item, i, trackingResult.timeline.length)}</div>
                        <div class="timeline-content">
                          <div class="timeline-desc">{item.description}</div>
                          <div class="timeline-meta">
                            {#if item.location}
                              <span class="timeline-location">📍 {item.location}</span>
                            {/if}
                            <span class="timeline-time">{formatDateTime(item.timestamp)}</span>
                          </div>
                        </div>
                      </div>
                    {/each}
                  </div>
                </div>

                <button class="view-detail-btn" on:click={() => openDetail(trackingResult)}>
                  View Full Details →
                </button>
              </div>
            {/if}
          </div>
        </div>
      {/if}
    {/if}
  </main>
</div>

<!-- Delivery Detail Modal -->
{#if showDetailModal && selectedDelivery}
  <div class="modal-overlay" on:click={closeDetail}>
    <div class="modal modal-large" on:click|stopPropagation>
      <div class="modal-header">
        <h2>📦 Delivery Details</h2>
        <button class="close-btn" on:click={closeDetail}>×</button>
      </div>
      
      <div class="modal-body">
        <!-- Header Info -->
        <div class="detail-header">
          <div class="detail-tracking">
            <span class="tracking-label">Tracking ID</span>
            <span class="tracking-value">{selectedDelivery.trackingId}</span>
          </div>
          <span class="status-badge {getStatusClass(selectedDelivery.status)} large">
            {getStatusLabel(selectedDelivery.status)}
          </span>
        </div>

        <!-- Route -->
        <div class="detail-section">
          <h3>Shipment Route</h3>
          <div class="detail-route">
            <div class="route-block">
              <div class="route-icon">📤</div>
              <div class="route-details">
                <span class="route-label">Sender</span>
                <span class="route-name">{selectedDelivery.sender}</span>
                <span class="route-address">{selectedDelivery.senderAddress}</span>
              </div>
            </div>
            <div class="route-connector">
              <div class="connector-line"></div>
              <span class="connector-icon">↓</span>
            </div>
            <div class="route-block">
              <div class="route-icon">📥</div>
              <div class="route-details">
                <span class="route-label">Recipient</span>
                <span class="route-name">{selectedDelivery.recipient}</span>
                <span class="route-address">{selectedDelivery.recipientAddress}</span>
                <span class="route-phone">📞 {selectedDelivery.recipientPhone}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Package Info -->
        <div class="detail-section">
          <h3>Package Information</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Description</span>
              <span class="info-value">{selectedDelivery.packageDescription}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Weight</span>
              <span class="info-value">{selectedDelivery.weight} kg</span>
            </div>
            <div class="info-item">
              <span class="info-label">Dimensions</span>
              <span class="info-value">{selectedDelivery.dimensions}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Priority</span>
              <span class="info-value priority-{selectedDelivery.priority}">
                {getPriorityIcon(selectedDelivery.priority)} {selectedDelivery.priority}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Fee</span>
              <span class="info-value fee">₫{formatCurrency(selectedDelivery.fee)}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Estimated Delivery</span>
              <span class="info-value">{formatDate(selectedDelivery.estimatedDelivery)}</span>
            </div>
            {#if selectedDelivery.actualDelivery}
              <div class="info-item">
                <span class="info-label">Actual Delivery</span>
                <span class="info-value delivered">{formatDate(selectedDelivery.actualDelivery)}</span>
              </div>
            {/if}
          </div>
        </div>

        <!-- Timeline -->
        <div class="detail-section">
          <h3>Tracking Timeline</h3>
          <div class="timeline-container">
            {#each selectedDelivery.timeline as item, i}
              <div class="timeline-step" class:completed={i < selectedDelivery.timeline.length}>
                <div class="step-marker" class:active={i === selectedDelivery.timeline.length - 1}>
                  <span class="step-dot">{getTimelineIcon(item, i, selectedDelivery.timeline.length)}</span>
                </div>
                <div class="step-content">
                  <div class="step-title">{item.description}</div>
                  <div class="step-meta">
                    {#if item.location}
                      <span class="step-location">📍 {item.location}</span>
                    {/if}
                    <span class="step-time">{formatDateTime(item.timestamp)}</span>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>
      </div>

      <div class="modal-footer">
        {#if ['pending', 'picked_up', 'in_transit', 'out_for_delivery'].includes(selectedDelivery.status)}
          <button class="btn-update" on:click={() => simulateStatusUpdate(selectedDelivery)}>
            📍 Simulate Next Status
          </button>
        {/if}
        <button class="btn-secondary" on:click={closeDetail}>Close</button>
      </div>
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .delivery-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
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
    color: #0891b2;
  }

  .tab.active {
    color: #0891b2;
    border-bottom-color: #0891b2;
  }

  .badge {
    background: #0891b2;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
  }

  /* Main Content */
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
    border-top-color: #0891b2;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Stats Row */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.8rem;
    color: #64748b;
  }

  /* Filters */
  .filters-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .search-box input {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 280px;
    font-size: 0.95rem;
  }

  .search-box input:focus {
    outline: none;
    border-color: #0891b2;
  }

  .filter-group {
    display: flex;
    gap: 0.75rem;
  }

  .filter-group select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
  }

  /* Delivery Cards Grid */
  .deliveries-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .delivery-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .delivery-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .tracking-id {
    font-weight: 700;
    color: #1e293b;
    font-size: 1rem;
  }

  .priority-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    text-transform: capitalize;
  }

  .priority-standard { background: #f1f5f9; color: #475569; }
  .priority-express { background: #fef3c7; color: #92400e; }
  .priority-overnight { background: #fee2e2; color: #991b1b; }

  .card-route {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .route-point {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
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
    font-size: 0.9rem;
  }

  .route-line {
    width: 2px;
    height: 24px;
    background: #e2e8f0;
    margin: 0.5rem 0 0.5rem 0.7rem;
  }

  .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .status-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
  }

  .status-badge.large {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  .status-pending { background: #fef3c7; color: #92400e; }
  .status-picked { background: #dbeafe; color: #1d4ed8; }
  .status-transit { background: #dbeafe; color: #1d4ed8; }
  .status-pending-delivery { background: #fef3c7; color: #92400e; }
  .status-delivered { background: #d1fae5; color: #065f46; }
  .status-cancelled { background: #fee2e2; color: #991b1b; }
  .status-returned { background: #e0e7ff; color: #4338ca; }

  .eta {
    font-size: 0.75rem;
    color: #64748b;
  }

  .fee {
    font-weight: 700;
    color: #0891b2;
    font-size: 1.1rem;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
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
    padding: 1rem 1.5rem;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
  }

  .history-item:hover {
    background: #f8fafc;
  }

  .history-icon {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 10px;
  }

  .history-info {
    flex: 1;
  }

  .history-tracking {
    font-weight: 600;
    color: #1e293b;
  }

  .history-desc {
    font-size: 0.9rem;
    color: #475569;
    margin: 0.25rem 0;
  }

  .history-meta {
    font-size: 0.8rem;
    color: #64748b;
  }

  .history-right {
    text-align: right;
  }

  .history-amount {
    font-weight: 600;
    color: #1e293b;
    margin-top: 0.5rem;
  }

  /* Track Section */
  .track-section {
    display: flex;
    justify-content: center;
    padding: 2rem 0;
  }

  .track-card {
    background: white;
    border-radius: 16px;
    padding: 2.5rem;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  .track-card h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .track-card > p {
    color: #64748b;
    margin: 0 0 2rem;
  }

  .track-input-group {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .track-input-group input {
    flex: 1;
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
  }

  .track-input-group input:focus {
    outline: none;
    border-color: #0891b2;
  }

  .track-btn {
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .track-btn:hover {
    transform: translateY(-1px);
  }

  .track-error {
    padding: 0.75rem;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 8px;
    font-size: 0.9rem;
  }

  .track-result {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
    text-align: left;
  }

  .result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .result-id {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .result-route {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 1.5rem;
  }

  .route-end {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .route-end .route-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .route-end .route-value {
    font-weight: 600;
    color: #1e293b;
  }

  .route-end .route-address {
    font-size: 0.85rem;
    color: #64748b;
  }

  .route-divider {
    font-size: 1.5rem;
    color: #94a3b8;
  }

  .result-timeline h4 {
    margin: 0 0 1rem;
    color: #1e293b;
  }

  .timeline-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .timeline-item {
    display: flex;
    gap: 1rem;
  }

  .timeline-icon {
    font-size: 1.25rem;
  }

  .timeline-content {
    flex: 1;
  }

  .timeline-desc {
    font-weight: 500;
    color: #1e293b;
  }

  .timeline-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .view-detail-btn {
    width: 100%;
    margin-top: 1.5rem;
    padding: 0.875rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    color: #0891b2;
    cursor: pointer;
    transition: background 0.2s;
  }

  .view-detail-btn:hover {
    background: #e2e8f0;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s;
  }

  .modal-large {
    max-width: 600px;
  }

  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #64748b;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .close-btn:hover {
    color: #1e293b;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .detail-tracking {
    display: flex;
    flex-direction: column;
  }

  .tracking-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .tracking-value {
    font-weight: 700;
    font-size: 1.25rem;
    color: #1e293b;
  }

  .detail-section {
    margin-bottom: 1.5rem;
  }

  .detail-section h3 {
    font-size: 0.9rem;
    color: #64748b;
    text-transform: uppercase;
    margin: 0 0 1rem;
  }

  .detail-route {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
  }

  .route-block {
    display: flex;
    gap: 1rem;
  }

  .route-icon {
    font-size: 1.5rem;
  }

  .route-details {
    display: flex;
    flex-direction: column;
  }

  .route-details .route-label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
  }

  .route-details .route-name {
    font-weight: 600;
    color: #1e293b;
  }

  .route-details .route-address {
    font-size: 0.85rem;
    color: #64748b;
  }

  .route-details .route-phone {
    font-size: 0.85rem;
    color: #0891b2;
    margin-top: 0.25rem;
  }

  .route-connector {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0;
  }

  .connector-line {
    width: 2px;
    height: 20px;
    background: #e2e8f0;
  }

  .connector-icon {
    color: #94a3b8;
    margin-left: -1px;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .info-item {
    display: flex;
    flex-direction: column;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
  }

  .info-label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
  }

  .info-value {
    font-weight: 500;
    color: #1e293b;
  }

  .info-value.priority-standard { color: #475569; }
  .info-value.priority-express { color: #d97706; }
  .info-value.priority-overnight { color: #dc2626; }
  .info-value.fee { color: #0891b2; font-size: 1.1rem; }
  .info-value.delivered { color: #059669; }

  .timeline-container {
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .timeline-step {
    display: flex;
    gap: 1rem;
    position: relative;
  }

  .timeline-step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 32px;
    bottom: -12px;
    width: 2px;
    background: #e2e8f0;
  }

  .step-marker {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .step-marker.active {
    background: #0891b2;
  }

  .step-dot {
    font-size: 0.9rem;
  }

  .step-content {
    flex: 1;
    padding-bottom: 1.5rem;
  }

  .step-title {
    font-weight: 500;
    color: #1e293b;
  }

  .step-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-update {
    flex: 1;
    padding: 0.875rem;
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
  }

  .btn-update:hover {
    transform: translateY(-1px);
  }

  .btn-secondary {
    padding: 0.875rem 1.5rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
  }

  .btn-secondary:hover {
    background: #e2e8f0;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 1rem;
    }

    .tabs {
      overflow-x: auto;
      padding: 0 1rem;
    }

    .deliveries-grid {
      grid-template-columns: 1fr;
    }

    .filters-bar {
      flex-direction: column;
    }

    .search-box input,
    .filter-group {
      width: 100%;
    }

    .filter-group select {
      flex: 1;
    }

    .track-input-group {
      flex-direction: column;
    }

    .info-grid {
      grid-template-columns: 1fr;
    }

    .result-route {
      flex-direction: column;
      text-align: center;
    }

    .route-divider {
      transform: rotate(90deg);
    }
  }
</style>
