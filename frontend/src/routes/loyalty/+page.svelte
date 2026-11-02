<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, slide } from 'svelte/transition';

  // Types
  interface LoyaltyData {
    id: number;
    user_id: number;
    total_points: number;
    tier: string;
    lifetime_points: number;
    next_tier_points: number;
    next_tier_name: string;
  }

  interface Transaction {
    id: string;
    type: 'earn' | 'redeem' | 'bonus' | 'expire' | 'adjustment';
    points: number;
    description: string;
    created_at: string;
    status: 'completed' | 'pending' | 'expired';
  }

  interface Reward {
    id: string;
    name: string;
    description: string;
    points_cost: number;
    category: 'discount' | 'voucher' | 'product' | 'experience';
    image: string;
    available: boolean;
    stock?: number;
  }

  interface TierInfo {
    name: string;
    color: string;
    gradient: string;
    icon: string;
    benefits: string[];
    minPoints: number;
  }

  // State
  let loading = true;
  let loyalty: LoyaltyData | null = null;
  let transactions: Transaction[] = [];
  let rewards: Reward[] = [];
  let activeTab = 'overview';

  // Filter state
  let filterType = 'all';
  let filterDateRange = 'all';
  let searchQuery = '';

  // Modal states
  let showRedeemModal = false;
  let showRewardDetailModal = false;
  let selectedReward: Reward | null = null;
  let redeemSuccess = false;

  // localStorage keys
  const STORAGE_KEYS = {
    loyalty: 'loyalty_data',
    transactions: 'loyalty_transactions',
    rewards: 'loyalty_rewards'
  };

  // Tier definitions
  const tiers: TierInfo[] = [
    {
      name: 'Bronze',
      color: '#cd7f32',
      gradient: 'linear-gradient(135deg, #cd7f32 0%, #8b4513 100%)',
      icon: '🥉',
      minPoints: 0,
      benefits: ['Earn 1 point per 10,000 VND', 'Birthday bonus points', 'Member-only deals']
    },
    {
      name: 'Silver',
      color: '#c0c0c0',
      gradient: 'linear-gradient(135deg, #c0c0c0 0%, #808080 100%)',
      icon: '🥈',
      minPoints: 5000,
      benefits: ['Earn 1.25 points per 10,000 VND', 'Priority booking', 'Free upgrades', 'Exclusive Silver events']
    },
    {
      name: 'Gold',
      color: '#ffd700',
      gradient: 'linear-gradient(135deg, #ffd700 0%, #daa520 100%)',
      icon: '🥇',
      minPoints: 20000,
      benefits: ['Earn 1.5 points per 10,000 VND', 'Guaranteed upgrades', 'Late checkout', 'Dedicated support']
    },
    {
      name: 'Platinum',
      color: '#e5e4e2',
      gradient: 'linear-gradient(135deg, #e5e4e2 0%, #a9a9a9 100%)',
      icon: '💎',
      minPoints: 50000,
      benefits: ['Earn 2 points per 10,000 VND', 'Suite upgrades', 'Free breakfast', 'Airport lounge access', 'Personal concierge']
    }
  ];

  onMount(() => {
    loadData();
  });

  function loadData() {
    loading = true;
    
    // Load from localStorage or initialize with defaults
    const storedLoyalty = localStorage.getItem(STORAGE_KEYS.loyalty);
    const storedTransactions = localStorage.getItem(STORAGE_KEYS.transactions);
    const storedRewards = localStorage.getItem(STORAGE_KEYS.rewards);

    if (storedLoyalty) {
      loyalty = JSON.parse(storedLoyalty);
    } else {
      // Initialize with sample loyalty data
      loyalty = {
        id: 1,
        user_id: 1,
        total_points: 12450,
        tier: 'Gold',
        lifetime_points: 45800,
        next_tier_points: 50000,
        next_tier_name: 'Platinum'
      };
      saveLoyalty();
    }

    if (storedTransactions) {
      transactions = JSON.parse(storedTransactions);
    } else {
      // Initialize with sample transactions
      transactions = [
        { id: 'tx_001', type: 'earn', points: 450, description: 'Hotel Booking #H2024001', created_at: '2026-02-12T10:30:00Z', status: 'completed' },
        { id: 'tx_002', type: 'redeem', points: -2000, description: 'Discount Voucher Redeemed', created_at: '2026-02-10T15:45:00Z', status: 'completed' },
        { id: 'tx_003', type: 'earn', points: 320, description: 'Restaurant Dinner', created_at: '2026-02-08T19:00:00Z', status: 'completed' },
        { id: 'tx_004', type: 'bonus', points: 500, description: 'Referral Bonus - New Member', created_at: '2026-02-05T09:00:00Z', status: 'completed' },
        { id: 'tx_005', type: 'earn', points: 680, description: 'Flight Booking #F2024032', created_at: '2026-02-01T14:20:00Z', status: 'completed' },
        { id: 'tx_006', type: 'earn', points: 150, description: 'Spa Service', created_at: '2026-01-28T16:00:00Z', status: 'completed' },
        { id: 'tx_007', type: 'expire', points: -200, description: 'Points Expired', created_at: '2026-01-25T00:00:00Z', status: 'expired' },
        { id: 'tx_008', type: 'earn', points: 890, description: 'Hotel Booking #H2023056', created_at: '2026-01-20T11:30:00Z', status: 'completed' },
        { id: 'tx_009', type: 'adjustment', points: 1000, description: 'Account Adjustment - Bonus', created_at: '2026-01-15T10:00:00Z', status: 'completed' },
        { id: 'tx_010', type: 'earn', points: 420, description: 'Car Rental', created_at: '2026-01-10T08:00:00Z', status: 'completed' },
        { id: 'tx_011', type: 'redeem', points: -5000, description: 'Free Night Redemption', created_at: '2026-01-05T20:00:00Z', status: 'completed' },
        { id: 'tx_012', type: 'earn', points: 1250, description: 'Hotel Booking #H2023045', created_at: '2025-12-28T15:00:00Z', status: 'completed' }
      ];
      saveTransactions();
    }

    if (storedRewards) {
      rewards = JSON.parse(storedRewards);
    } else {
      // Initialize with sample rewards
      rewards = [
        { id: 'r001', name: '10% Off Next Booking', description: 'Get 10% discount on your next hotel booking', points_cost: 1000, category: 'discount', image: '🏨', available: true, stock: 50 },
        { id: 'r002', name: 'Free Breakfast', description: 'Enjoy complimentary breakfast during your stay', points_cost: 1500, category: 'voucher', image: '🍳', available: true, stock: 30 },
        { id: 'r003', name: 'Room Upgrade', description: 'Upgrade to next room category subject to availability', points_cost: 3000, category: 'voucher', image: '🛏️', available: true, stock: 15 },
        { id: 'r004', name: 'Spa Voucher $50', description: '$50 credit at our spa partner locations', points_cost: 5000, category: 'voucher', image: '💆', available: true, stock: 20 },
        { id: 'r005', name: 'Free Night', description: 'Redeem one free night at select properties', points_cost: 15000, category: 'experience', image: '🌙', available: true, stock: 5 },
        { id: 'r006', name: 'Airport Lounge Access', description: 'Access to VIP lounge at major airports', points_cost: 2500, category: 'voucher', image: '✈️', available: true, stock: 25 },
        { id: 'r007', name: 'Late Checkout', description: 'Enjoy late checkout until 4 PM', points_cost: 800, category: 'voucher', image: '🕐', available: true, stock: 100 },
        { id: 'r008', name: 'Welcome Amenity', description: 'Complimentary welcome gift upon arrival', points_cost: 500, category: 'product', image: '🎁', available: true, stock: 200 },
        { id: 'r009', name: 'Dining Credit $100', description: '$100 credit at hotel restaurants', points_cost: 8000, category: 'voucher', image: '🍽️', available: true, stock: 10 },
        { id: 'r010', name: 'Concert Tickets', description: 'Two tickets to exclusive member events', points_cost: 20000, category: 'experience', image: '🎵', available: false, stock: 0 },
        { id: 'r011', name: 'Car Rental Day', description: 'One day free car rental', points_cost: 6000, category: 'voucher', image: '🚗', available: true, stock: 12 },
        { id: 'r012', name: 'Insurance Discount', description: '10% off travel insurance', points_cost: 300, category: 'discount', image: '🛡️', available: true, stock: 500 }
      ];
      saveRewards();
    }

    loading = false;
  }

  function saveLoyalty() {
    if (loyalty) {
      localStorage.setItem(STORAGE_KEYS.loyalty, JSON.stringify(loyalty));
    }
  }

  function saveTransactions() {
    localStorage.setItem(STORAGE_KEYS.transactions, JSON.stringify(transactions));
  }

  function saveRewards() {
    localStorage.setItem(STORAGE_KEYS.rewards, JSON.stringify(rewards));
  }

  // Computed values
  $: currentTier = tiers.find(t => t.name === loyalty?.tier) || tiers[0];
  $: nextTier = tiers.find(t => t.minPoints > (loyalty?.lifetime_points || 0)) || null;
  $: tierProgress = nextTier 
    ? ((loyalty?.lifetime_points || 0) - currentTier.minPoints) / (nextTier.minPoints - currentTier.minPoints) * 100
    : 100;

  $: filteredTransactions = transactions.filter(tx => {
    // Type filter
    if (filterType !== 'all' && tx.type !== filterType) return false;
    
    // Date filter
    if (filterDateRange !== 'all') {
      const txDate = new Date(tx.created_at);
      const now = new Date();
      if (filterDateRange === 'week') {
        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        if (txDate < weekAgo) return false;
      } else if (filterDateRange === 'month') {
        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        if (txDate < monthAgo) return false;
      } else if (filterDateRange === '3months') {
        const threeMonthsAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
        if (txDate < threeMonthsAgo) return false;
      }
    }
    
    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      return tx.description.toLowerCase().includes(query) || 
             tx.type.toLowerCase().includes(query);
    }
    
    return true;
  });

  $: availableRewards = rewards.filter(r => r.available);
  $: earnedThisMonth = transactions
    .filter(tx => tx.type === 'earn' && tx.points > 0)
    .reduce((sum, tx) => sum + tx.points, 0);

  // Helper functions
  function formatPoints(points: number): string {
    return new Intl.NumberFormat('vi-VN').format(points);
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function formatDateTime(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getTransactionIcon(type: string): string {
    switch (type) {
      case 'earn': return '💰';
      case 'redeem': return '🎁';
      case 'bonus': return '🎉';
      case 'expire': return '⏰';
      case 'adjustment': return '✏️';
      default: return '💵';
    }
  }

  function getTransactionLabel(type: string): string {
    switch (type) {
      case 'earn': return 'Earned';
      case 'redeem': return 'Redeemed';
      case 'bonus': return 'Bonus';
      case 'expire': return 'Expired';
      case 'adjustment': return 'Adjustment';
      default: return type;
    }
  }

  function getCategoryIcon(category: string): string {
    switch (category) {
      case 'discount': return '💲';
      case 'voucher': return '🎫';
      case 'product': return '📦';
      case 'experience': return '🌟';
      default: return '🎁';
    }
  }

  function getCategoryLabel(category: string): string {
    return category.charAt(0).toUpperCase() + category.slice(1);
  }

  // Actions
  function openRedeemModal(reward: Reward) {
    selectedReward = reward;
    showRedeemModal = true;
    redeemSuccess = false;
  }

  function closeRedeemModal() {
    showRedeemModal = false;
    selectedReward = null;
    redeemSuccess = false;
  }

  function handleRedeem() {
    if (!selectedReward || !loyalty) return;
    
    if (loyalty.total_points < selectedReward.points_cost) {
      alert('Insufficient points');
      return;
    }

    // Deduct points
    loyalty.total_points -= selectedReward.points_cost;
    
    // Add transaction
    const newTx: Transaction = {
      id: `tx_${Date.now()}`,
      type: 'redeem',
      points: -selectedReward.points_cost,
      description: `${selectedReward.name} Redeemed`,
      created_at: new Date().toISOString(),
      status: 'completed'
    };
    
    transactions = [newTx, ...transactions];
    
    // Update reward stock
    if (selectedReward.stock !== undefined) {
      selectedReward.stock--;
      if (selectedReward.stock <= 0) {
        selectedReward.available = false;
      }
      rewards = rewards.map(r => r.id === selectedReward!.id ? selectedReward! : r);
      saveRewards();
    }
    
    saveLoyalty();
    saveTransactions();
    
    redeemSuccess = true;
    
    setTimeout(() => {
      closeRedeemModal();
    }, 1500);
  }

  function openRewardDetail(reward: Reward) {
    selectedReward = reward;
    showRewardDetailModal = true;
  }

  function closeRewardDetail() {
    showRewardDetailModal = false;
    selectedReward = null;
  }
</script>

<svelte:head>
  <title>Loyalty Program - Hotel Booking App</title>
</svelte:head>

<div class="loyalty-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>⭐ Loyalty Program</h1>
        <p>Earn points, unlock rewards, climb the tiers</p>
      </div>
    </div>
  </header>

  <!-- Tabs -->
  <nav class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'overview'} 
      on:click={() => activeTab = 'overview'}
    >
      Overview
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'rewards'} 
      on:click={() => activeTab = 'rewards'}
    >
      Rewards Catalog
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
        <p>Loading your loyalty data...</p>
      </div>
    {:else if loyalty}
      <!-- Overview Tab -->
      {#if activeTab === 'overview'}
        <div class="overview" in:fade>
          <!-- Points Card -->
          <section class="points-card" style="background: {currentTier.gradient}">
            <div class="points-card-header">
              <div class="tier-badge">
                <span class="tier-icon">{currentTier.icon}</span>
                <span class="tier-name">{loyalty.tier} Member</span>
              </div>
              {#if nextTier}
                <div class="next-tier-hint">
                  {formatPoints(nextTier.minPoints - loyalty.lifetime_points)} pts to {nextTier.name}
                </div>
              {/if}
            </div>
            
            <div class="points-balance">
              <span class="points-label">Available Points</span>
              <span class="points-value">{formatPoints(loyalty.total_points)}</span>
            </div>
            
            <div class="points-stats">
              <div class="stat">
                <span class="stat-value">{formatPoints(loyalty.lifetime_points)}</span>
                <span class="stat-label">Lifetime Points</span>
              </div>
              <div class="stat-divider"></div>
              <div class="stat">
                <span class="stat-value">{formatPoints(earnedThisMonth)}</span>
                <span class="stat-label">Earned This Month</span>
              </div>
            </div>

            {#if nextTier}
              <div class="tier-progress">
                <div class="progress-header">
                  <span>Progress to {nextTier.name}</span>
                  <span>{Math.round(tierProgress)}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: {Math.min(tierProgress, 100)}%"></div>
                </div>
                <div class="progress-amounts">
                  <span>{formatPoints(loyalty.lifetime_points)}</span>
                  <span>{formatPoints(nextTier.minPoints)}</span>
                </div>
              </div>
            {/if}
          </section>

          <!-- Quick Actions -->
          <section class="quick-actions">
            <button class="action-card" on:click={() => activeTab = 'rewards'}>
              <div class="action-icon">🎁</div>
              <div class="action-text">
                <span class="action-title">Browse Rewards</span>
                <span class="action-desc">Redeem your points</span>
              </div>
            </button>
            <button class="action-card" on:click={() => activeTab = 'history'}>
              <div class="action-icon">📜</div>
              <div class="action-text">
                <span class="action-title">View History</span>
                <span class="action-desc">Track your points</span>
              </div>
            </button>
          </section>

          <!-- Current Tier Benefits -->
          <section class="section">
            <div class="section-header">
              <h3>Your {loyalty.tier} Benefits</h3>
            </div>
            <div class="benefits-grid">
              {#each currentTier.benefits as benefit}
                <div class="benefit-item">
                  <span class="benefit-check">✓</span>
                  <span>{benefit}</span>
                </div>
              {/each}
            </div>
          </section>

          <!-- Recent Transactions -->
          <section class="section">
            <div class="section-header">
              <h3>Recent Activity</h3>
              <button class="view-all" on:click={() => activeTab = 'history'}>View All</button>
            </div>
            <div class="transactions-list">
              {#each transactions.slice(0, 5) as tx}
                <div class="transaction-item">
                  <div class="tx-icon">{getTransactionIcon(tx.type)}</div>
                  <div class="tx-info">
                    <div class="tx-description">{tx.description}</div>
                    <div class="tx-meta">{formatDateTime(tx.created_at)}</div>
                  </div>
                  <div class="tx-amount" class:positive={tx.points > 0} class:negative={tx.points < 0}>
                    {tx.points > 0 ? '+' : ''}{formatPoints(tx.points)}
                  </div>
                </div>
              {/each}
            </div>
          </section>

          <!-- Available Rewards Preview -->
          <section class="section">
            <div class="section-header">
              <h3>Popular Rewards</h3>
              <button class="view-all" on:click={() => activeTab = 'rewards'}>View All</button>
            </div>
            <div class="rewards-preview-grid">
              {#each availableRewards.slice(0, 4) as reward}
                <div class="reward-preview-card" on:click={() => openRedeemModal(reward)}>
                  <div class="reward-image">{reward.image}</div>
                  <div class="reward-name">{reward.name}</div>
                  <div class="reward-cost">{formatPoints(reward.points_cost)} pts</div>
                </div>
              {/each}
            </div>
          </section>
        </div>
      {/if}

      <!-- Rewards Catalog Tab -->
      {#if activeTab === 'rewards'}
        <div class="rewards-page" in:fade>
          <!-- Rewards Stats -->
          <div class="rewards-stats">
            <div class="reward-stat-card">
              <div class="reward-stat-value">{formatPoints(loyalty.total_points)}</div>
              <div class="reward-stat-label">Your Points</div>
            </div>
            <div class="reward-stat-card">
              <div class="reward-stat-value">{availableRewards.length}</div>
              <div class="reward-stat-label">Available Rewards</div>
            </div>
          </div>

          <!-- Category Filter -->
          <div class="category-filter">
            <button class="category-btn active">All</button>
            <button class="category-btn">Discounts</button>
            <button class="category-btn">Vouchers</button>
            <button class="category-btn">Products</button>
            <button class="category-btn">Experiences</button>
          </div>

          <!-- Rewards Grid -->
          <div class="rewards-grid">
            {#each rewards as reward}
              <div class="reward-card" class:unavailable={!reward.available}>
                <div class="reward-card-image">{reward.image}</div>
                <div class="reward-card-content">
                  <div class="reward-card-category">
                    {getCategoryIcon(reward.category)} {getCategoryLabel(reward.category)}
                  </div>
                  <h4 class="reward-card-title">{reward.name}</h4>
                  <p class="reward-card-desc">{reward.description}</p>
                  <div class="reward-card-footer">
                    <div class="reward-card-cost">
                      <span class="cost-value">{formatPoints(reward.points_cost)}</span>
                      <span class="cost-label">points</span>
                    </div>
                    {#if reward.stock !== undefined}
                      <div class="reward-stock" class:low-stock={reward.stock <= 5}>
                        {reward.stock > 0 ? `${reward.stock} left` : 'Sold out'}
                      </div>
                    {/if}
                  </div>
                  {#if reward.available}
                    <button class="redeem-btn" on:click={() => openRedeemModal(reward)}>
                      Redeem Now
                    </button>
                  {:else}
                    <button class="redeem-btn disabled" disabled>
                      Unavailable
                    </button>
                  {/if}
                </div>
              </div>
            {/each}
          </div>
        </div>
      {/if}

      <!-- History Tab -->
      {#if activeTab === 'history'}
        <div class="history-page" in:fade>
          <!-- Filters -->
          <div class="filters-bar">
            <div class="search-box">
              <input 
                type="text" 
                placeholder="Search transactions..." 
                bind:value={searchQuery}
              />
            </div>
            <div class="filter-group">
              <select bind:value={filterType}>
                <option value="all">All Types</option>
                <option value="earn">Earned</option>
                <option value="redeem">Redeemed</option>
                <option value="bonus">Bonus</option>
                <option value="expire">Expired</option>
                <option value="adjustment">Adjustment</option>
              </select>
              <select bind:value={filterDateRange}>
                <option value="all">All Time</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="3months">Last 3 Months</option>
              </select>
            </div>
          </div>

          <!-- Transactions Summary -->
          <div class="history-summary">
            <div class="summary-item">
              <span class="summary-label">Total Transactions</span>
              <span class="summary-value">{filteredTransactions.length}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Points Earned</span>
              <span class="summary-value positive">+{formatPoints(filteredTransactions.filter(t => t.points > 0).reduce((s, t) => s + t.points, 0))}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Points Redeemed</span>
              <span class="summary-value negative">{formatPoints(filteredTransactions.filter(t => t.points < 0).reduce((s, t) => s + t.points, 0))}</span>
            </div>
          </div>

          <!-- Transactions List -->
          {#if filteredTransactions.length === 0}
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <h3>No transactions found</h3>
              <p>Your transaction history will appear here</p>
            </div>
          {:else}
            <div class="transactions-full-list">
              {#each filteredTransactions as tx}
                <div class="transaction-row">
                  <div class="tx-icon-large">{getTransactionIcon(tx.type)}</div>
                  <div class="tx-details">
                    <div class="tx-description">{tx.description}</div>
                    <div class="tx-meta">
                      <span class="tx-type">{getTransactionLabel(tx.type)}</span>
                      <span class="tx-date">{formatDate(tx.created_at)}</span>
                    </div>
                  </div>
                  <div class="tx-amount-wrapper">
                    <div class="tx-amount-large" class:positive={tx.points > 0} class:negative={tx.points < 0}>
                      {tx.points > 0 ? '+' : ''}{formatPoints(tx.points)}
                    </div>
                    <span class="tx-status {tx.status}">{tx.status}</span>
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

<!-- Redeem Modal -->
{#if showRedeemModal && selectedReward && loyalty}
  <div class="modal-overlay" on:click={closeRedeemModal}>
    <div class="modal" on:click|stopPropagation>
      <div class="modal-header">
        <h2>🎁 Redeem Reward</h2>
        <button class="close-btn" on:click={closeRedeemModal}>×</button>
      </div>
      
      {#if redeemSuccess}
        <div class="success-message">
          <div class="success-icon">✅</div>
          <p>Successfully redeemed!</p>
          <p class="success-detail">You redeemed {selectedReward.name}</p>
        </div>
      {:else}
        <div class="modal-body">
          <div class="reward-preview">
            <div class="reward-preview-image">{selectedReward.image}</div>
            <div class="reward-preview-info">
              <div class="reward-preview-title">{selectedReward.name}</div>
              <div class="reward-preview-desc">{selectedReward.description}</div>
            </div>
          </div>
          
          <div class="redeem-details">
            <div class="detail-row">
              <span>Your Points</span>
              <span class="detail-value">{formatPoints(loyalty.total_points)}</span>
            </div>
            <div class="detail-row">
              <span>Reward Cost</span>
              <span class="detail-value cost">-{formatPoints(selectedReward.points_cost)}</span>
            </div>
            <div class="detail-row total">
              <span>Points After</span>
              <span class="detail-value">{formatPoints(loyalty.total_points - selectedReward.points_cost)}</span>
            </div>
          </div>

          {#if loyalty.total_points < selectedReward.points_cost}
            <div class="error-message">
              Insufficient points. You need {formatPoints(selectedReward.points_cost - loyalty.total_points)} more points.
            </div>
          {/if}
        </div>
        
        <div class="modal-footer">
          <button class="btn-cancel" on:click={closeRedeemModal}>Cancel</button>
          <button 
            class="btn-confirm" 
            on:click={handleRedeem}
            disabled={loyalty.total_points < selectedReward.points_cost}
          >
            Redeem for {formatPoints(selectedReward.points_cost)} pts
          </button>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  /* Base Styles */
  .loyalty-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  .header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
  }

  .tab:hover {
    color: #d97706;
  }

  .tab.active {
    color: #d97706;
    border-bottom-color: #d97706;
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
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Points Card */
  .points-card {
    color: white;
    padding: 2rem;
    border-radius: 20px;
    margin-bottom: 1.5rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  }

  .points-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .tier-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
  }

  .tier-icon {
    font-size: 1.25rem;
  }

  .tier-name {
    font-weight: 600;
    font-size: 0.9rem;
  }

  .next-tier-hint {
    font-size: 0.85rem;
    opacity: 0.9;
  }

  .points-balance {
    text-align: center;
    margin-bottom: 1.5rem;
  }

  .points-label {
    display: block;
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
  }

  .points-value {
    font-size: 3.5rem;
    font-weight: 700;
    display: block;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .points-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
  }

  .stat {
    text-align: center;
  }

  .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
  }

  .stat-label {
    font-size: 0.8rem;
    opacity: 0.9;
  }

  .stat-divider {
    width: 1px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
  }

  .tier-progress {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 1rem;
  }

  .progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
  }

  .progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }

  .progress-fill {
    height: 100%;
    background: white;
    border-radius: 4px;
    transition: width 0.3s ease;
  }

  .progress-amounts {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    opacity: 0.8;
  }

  /* Quick Actions */
  .quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
  }

  .action-card:hover {
    border-color: #f59e0b;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
  }

  .action-icon {
    font-size: 1.75rem;
  }

  .action-text {
    display: flex;
    flex-direction: column;
  }

  .action-title {
    font-weight: 600;
    color: #1e293b;
  }

  .action-desc {
    font-size: 0.8rem;
    color: #64748b;
  }

  /* Sections */
  .section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .section-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 1.1rem;
  }

  .view-all {
    background: none;
    border: none;
    color: #f59e0b;
    font-size: 0.875rem;
    cursor: pointer;
  }

  .view-all:hover {
    text-decoration: underline;
  }

  /* Benefits Grid */
  .benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
  }

  .benefit-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #475569;
  }

  .benefit-check {
    color: #22c55e;
    font-weight: 700;
  }

  /* Transactions List */
  .transactions-list {
    display: flex;
    flex-direction: column;
  }

  .transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .transaction-item:last-child {
    border-bottom: none;
  }

  .tx-icon {
    font-size: 1.25rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 8px;
  }

  .tx-info {
    flex: 1;
  }

  .tx-description {
    font-weight: 500;
    color: #1e293b;
    margin-bottom: 0.125rem;
  }

  .tx-meta {
    font-size: 0.8rem;
    color: #64748b;
  }

  .tx-amount {
    font-weight: 600;
    font-size: 1rem;
  }

  .positive { color: #22c55e; }
  .negative { color: #dc2626; }

  /* Rewards Preview Grid */
  .rewards-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }

  .reward-preview-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
  }

  .reward-preview-card:hover {
    border-color: #f59e0b;
    background: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
  }

  .reward-preview-card .reward-image {
    font-size: 2rem;
    margin-bottom: 0.5rem;
  }

  .reward-preview-card .reward-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .reward-preview-card .reward-cost {
    font-size: 0.8rem;
    color: #f59e0b;
    font-weight: 600;
  }

  /* Rewards Page */
  .rewards-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .reward-stat-card {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .reward-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f59e0b;
    margin-bottom: 0.25rem;
  }

  .reward-stat-label {
    font-size: 0.8rem;
    color: #64748b;
  }

  .category-filter {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .category-btn {
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
  }

  .category-btn:hover,
  .category-btn.active {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
  }

  .rewards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .reward-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
  }

  .reward-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .reward-card.unavailable {
    opacity: 0.6;
  }

  .reward-card-image {
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  }

  .reward-card-content {
    padding: 1.25rem;
  }

  .reward-card-category {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
  }

  .reward-card-title {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .reward-card-desc {
    margin: 0 0 1rem;
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.4;
  }

  .reward-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .reward-card-cost {
    display: flex;
    flex-direction: column;
  }

  .cost-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f59e0b;
  }

  .cost-label {
    font-size: 0.7rem;
    color: #64748b;
  }

  .reward-stock {
    font-size: 0.75rem;
    color: #64748b;
  }

  .reward-stock.low-stock {
    color: #f59e0b;
    font-weight: 600;
  }

  .redeem-btn {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
  }

  .redeem-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
  }

  .redeem-btn.disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  /* History Page */
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
    width: 250px;
    font-size: 0.95rem;
  }

  .search-box input:focus {
    outline: none;
    border-color: #f59e0b;
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

  .history-summary {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  .summary-item {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 1rem;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .summary-label {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
  }

  .summary-value.positive {
    color: #22c55e;
  }

  .summary-value.negative {
    color: #dc2626;
  }

  .transactions-full-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .transaction-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
  }

  .transaction-row:hover {
    background: #f8fafc;
  }

  .transaction-row:last-child {
    border-bottom: none;
  }

  .tx-icon-large {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef3c7;
    border-radius: 12px;
  }

  .tx-details {
    flex: 1;
  }

  .tx-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.25rem;
  }

  .tx-type {
    background: #f1f5f9;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
  }

  .tx-amount-wrapper {
    text-align: right;
  }

  .tx-amount-large {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .tx-status {
    font-size: 0.7rem;
    text-transform: uppercase;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
  }

  .tx-status.completed {
    background: #dcfce7;
    color: #166534;
  }

  .tx-status.pending {
    background: #fef3c7;
    color: #92400e;
  }

  .tx-status.expired {
    background: #fee2e2;
    color: #991b1b;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
  }

  .empty-icon {
    font-size: 3rem;
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
    animation: fadeIn 0.2s;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    max-height: 90vh;
    overflow: auto;
    animation: slideUp 0.3s;
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

  .reward-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .reward-preview-image {
    font-size: 2.5rem;
  }

  .reward-preview-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .reward-preview-desc {
    font-size: 0.85rem;
    color: #64748b;
  }

  .redeem-details {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: #64748b;
  }

  .detail-row.total {
    border-top: 1px solid #e2e8f0;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    color: #1e293b;
  }

  .detail-value.cost {
    color: #dc2626;
  }

  .error-message {
    padding: 0.75rem;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 8px;
    font-size: 0.875rem;
  }

  .success-message {
    text-align: center;
    padding: 2rem;
  }

  .success-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .success-detail {
    font-size: 0.9rem;
    color: #64748b;
  }

  .modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-cancel {
    flex: 1;
    padding: 0.75rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
  }

  .btn-cancel:hover {
    background: #e2e8f0;
  }

  .btn-confirm {
    flex: 2;
    padding: 0.75rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    cursor: pointer;
  }

  .btn-confirm:hover {
    opacity: 0.9;
  }

  .btn-confirm:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
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

    .points-value {
      font-size: 2.5rem;
    }

    .points-stats {
      flex-direction: column;
      gap: 1rem;
    }

    .stat-divider {
      width: 60px;
      height: 1px;
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

    .rewards-grid {
      grid-template-columns: 1fr;
    }

    .modal {
      margin: 1rem;
      max-width: calc(100% - 2rem);
    }
  }
</style>
