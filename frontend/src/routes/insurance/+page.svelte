<script lang="ts">
  import { onMount } from 'svelte';
  import { fade, scale } from 'svelte/transition';

  interface Policy {
    id: string;
    type: 'health' | 'car' | 'life' | 'travel';
    provider: string;
    policyNumber: string;
    coverageAmount: number;
    premium: number;
    premiumFrequency: 'monthly' | 'quarterly' | 'yearly';
    status: 'active' | 'expired' | 'pending';
    startDate: string;
    endDate: string;
    lastPaymentDate: string;
    nextPaymentDate: string;
    holder: {
      name: string;
      email: string;
      phone: string;
    };
    benefits: string[];
    documents: { name: string; url: string }[];
  }

  interface Claim {
    id: string;
    policyId: string;
    type: string;
    description: string;
    amount: number;
    status: 'pending' | 'approved' | 'rejected' | 'processing';
    filedDate: string;
    resolvedDate?: string;
    documents: { name: string; url: string }[];
  }

  interface Payment {
    id: string;
    policyId: string;
    amount: number;
    date: string;
    method: string;
    status: 'completed' | 'pending' | 'failed';
    transactionId: string;
  }

  // State
  let policies: Policy[] = [];
  let claims: Claim[] = [];
  let payments: Payment[] = [];
  let activeTab: 'policies' | 'claims' | 'payments' | 'buy' = 'policies';
  let selectedPolicy: Policy | null = null;
  let showDetailModal = false;
  let showClaimModal = false;
  let showPaymentModal = false;
  let loading = false;
  let error = '';

  // Form states
  let newClaim = { type: '', description: '', amount: 0 };
  let paymentForm = { method: 'card', cardNumber: '', expiry: '', cvv: '' };

  // Policy type config
  const policyTypes = {
    health: { icon: '🏥', label: 'Health Insurance', color: '#10b981' },
    car: { icon: '🚗', label: 'Car Insurance', color: '#3b82f6' },
    life: { icon: '👤', label: 'Life Insurance', color: '#8b5cf6' },
    travel: { icon: '✈️', label: 'Travel Insurance', color: '#f59e0b' }
  };

  // Load data from localStorage
  function loadData() {
    try {
      const savedPolicies = localStorage.getItem('insurance_policies');
      const savedClaims = localStorage.getItem('insurance_claims');
      const savedPayments = localStorage.getItem('insurance_payments');

      if (savedPolicies) {
        policies = JSON.parse(savedPolicies);
      } else {
        // Initialize with sample data
        policies = getSamplePolicies();
        savePolicies();
      }

      if (savedClaims) {
        claims = JSON.parse(savedClaims);
      } else {
        claims = getSampleClaims();
        saveClaims();
      }

      if (savedPayments) {
        payments = JSON.parse(savedPayments);
      } else {
        payments = getSamplePayments();
        savePayments();
      }
    } catch (e) {
      error = 'Failed to load insurance data';
      console.error(e);
    }
  }

  function savePolicies() {
    localStorage.setItem('insurance_policies', JSON.stringify(policies));
  }

  function saveClaims() {
    localStorage.setItem('insurance_claims', JSON.stringify(claims));
  }

  function savePayments() {
    localStorage.setItem('insurance_payments', JSON.stringify(payments));
  }

  function getSamplePolicies(): Policy[] {
    return [
      {
        id: 'pol_001',
        type: 'health',
        provider: 'Pacific Health',
        policyNumber: 'HTH-2024-78542',
        coverageAmount: 500000000,
        premium: 12000000,
        premiumFrequency: 'yearly',
        status: 'active',
        startDate: '2024-01-15',
        endDate: '2025-01-14',
        lastPaymentDate: '2024-01-15',
        nextPaymentDate: '2025-01-15',
        holder: { name: 'John Doe', email: 'john@example.com', phone: '+84 123 456 789' },
        benefits: ['Hospitalization coverage', 'Dental care', 'Emergency evacuation', 'Pre/postnatal care'],
        documents: [{ name: 'Policy Certificate', url: '#' }]
      },
      {
        id: 'pol_002',
        type: 'car',
        provider: 'AutoProtect',
        policyNumber: 'CAR-2024-32156',
        coverageAmount: 800000000,
        premium: 8500000,
        premiumFrequency: 'yearly',
        status: 'active',
        startDate: '2024-03-01',
        endDate: '2025-02-28',
        lastPaymentDate: '2024-03-01',
        nextPaymentDate: '2025-03-01',
        holder: { name: 'John Doe', email: 'john@example.com', phone: '+84 123 456 789' },
        benefits: ['Third party liability', 'Comprehensive coverage', 'Theft protection', 'Natural disaster'],
        documents: [{ name: 'Vehicle Certificate', url: '#' }]
      },
      {
        id: 'pol_003',
        type: 'travel',
        provider: 'WorldGuard',
        policyNumber: 'TRV-2025-00124',
        coverageAmount: 200000000,
        premium: 2500000,
        premiumFrequency: 'yearly',
        status: 'active',
        startDate: '2025-01-01',
        endDate: '2025-12-31',
        lastPaymentDate: '2025-01-01',
        nextPaymentDate: '2026-01-01',
        holder: { name: 'John Doe', email: 'john@example.com', phone: '+84 123 456 789' },
        benefits: ['Trip cancellation', 'Baggage loss', 'Medical emergency', 'Flight delay compensation'],
        documents: [{ name: 'Travel Certificate', url: '#' }]
      },
      {
        id: 'pol_004',
        type: 'life',
        provider: 'SecureLife',
        policyNumber: 'LIF-2023-98234',
        coverageAmount: 1000000000,
        premium: 15000000,
        premiumFrequency: 'yearly',
        status: 'expired',
        startDate: '2023-01-01',
        endDate: '2024-12-31',
        lastPaymentDate: '2023-01-01',
        nextPaymentDate: '2025-01-01',
        holder: { name: 'John Doe', email: 'john@example.com', phone: '+84 123 456 789' },
        benefits: ['Death benefit', 'Terminal illness', 'Disability rider', 'Accidental death'],
        documents: [{ name: 'Life Policy', url: '#' }]
      }
    ];
  }

  function getSampleClaims(): Claim[] {
    return [
      {
        id: 'clm_001',
        policyId: 'pol_001',
        type: 'Medical',
        description: 'Hospitalization for appendectomy surgery',
        amount: 45000000,
        status: 'approved',
        filedDate: '2024-06-15',
        resolvedDate: '2024-07-01',
        documents: [{ name: 'Medical Report', url: '#' }]
      },
      {
        id: 'clm_002',
        policyId: 'pol_002',
        type: 'Accident',
        description: 'Car accident damage - rear end collision',
        amount: 15000000,
        status: 'processing',
        filedDate: '2024-11-20',
        documents: [{ name: 'Police Report', url: '#' }, { name: 'Photos', url: '#' }]
      }
    ];
  }

  function getSamplePayments(): Payment[] {
    return [
      { id: 'pay_001', policyId: 'pol_001', amount: 12000000, date: '2024-01-15', method: 'Credit Card', status: 'completed', transactionId: 'TXN-78542' },
      { id: 'pay_002', policyId: 'pol_002', amount: 8500000, date: '2024-03-01', method: 'Credit Card', status: 'completed', transactionId: 'TXN-32156' },
      { id: 'pay_003', policyId: 'pol_003', amount: 2500000, date: '2025-01-01', method: 'Bank Transfer', status: 'completed', transactionId: 'TXN-00124' }
    ];
  }

  // Computed values
  $: totalCoverage = policies.filter(p => p.status === 'active').reduce((sum, p) => sum + p.coverageAmount, 0);
  $: totalPremium = policies.filter(p => p.status === 'active').reduce((sum, p) => sum + p.premium, 0);
  $: activePolicies = policies.filter(p => p.status === 'active').length;
  $: pendingClaims = claims.filter(c => c.status === 'pending' || c.status === 'processing').length;

  // Actions
  function viewPolicyDetails(policy: Policy) {
    selectedPolicy = policy;
    showDetailModal = true;
  }

  function closeDetailModal() {
    showDetailModal = false;
    selectedPolicy = null;
  }

  function openClaimModal(policyId: string) {
    newClaim = { type: '', description: '', amount: 0 };
    showClaimModal = true;
  }

  function submitClaim() {
    if (!selectedPolicy || !newClaim.type || !newClaim.description) return;

    const claim: Claim = {
      id: 'clm_' + Date.now(),
      policyId: selectedPolicy.id,
      type: newClaim.type,
      description: newClaim.description,
      amount: newClaim.amount,
      status: 'pending',
      filedDate: new Date().toISOString().split('T')[0],
      documents: []
    };

    claims = [...claims, claim];
    saveClaims();
    showClaimModal = false;
    newClaim = { type: '', description: '', amount: 0 };
  }

  function openPaymentModal(policy: Policy) {
    selectedPolicy = policy;
    paymentForm = { method: 'card', cardNumber: '', expiry: '', cvv: '' };
    showPaymentModal = true;
  }

  function processPayment() {
    if (!selectedPolicy) return;

    const payment: Payment = {
      id: 'pay_' + Date.now(),
      policyId: selectedPolicy.id,
      amount: selectedPolicy.premium,
      date: new Date().toISOString().split('T')[0],
      method: paymentForm.method === 'card' ? 'Credit Card' : 'Bank Transfer',
      status: 'completed',
      transactionId: 'TXN-' + Date.now()
    };

    payments = [...payments, payment];
    savePayments();

    // Update policy
    policies = policies.map(p => {
      if (p.id === selectedPolicy?.id) {
        return {
          ...p,
          lastPaymentDate: payment.date,
          nextPaymentDate: getNextPaymentDate(p.premiumFrequency)
        };
      }
      return p;
    });
    savePolicies();

    showPaymentModal = false;
  }

  function getNextPaymentDate(frequency: string): string {
    const now = new Date();
    switch (frequency) {
      case 'monthly': now.setMonth(now.getMonth() + 1); break;
      case 'quarterly': now.setMonth(now.getMonth() + 3); break;
      case 'yearly': now.setFullYear(now.getFullYear() + 1); break;
    }
    return now.toISOString().split('T')[0];
  }

  function renewPolicy(policy: Policy) {
    const newEndDate = new Date(policy.endDate);
    newEndDate.setFullYear(newEndDate.getFullYear() + 1);

    policies = policies.map(p => {
      if (p.id === policy.id) {
        return {
          ...p,
          startDate: p.endDate,
          endDate: newEndDate.toISOString().split('T')[0],
          status: 'active' as const
        };
      }
      return p;
    });
    savePolicies();
  }

  function getPolicyById(id: string): Policy | undefined {
    return policies.find(p => p.id === id);
  }

  function formatCurrency(amount: number): string {
    return '₫' + amount.toLocaleString();
  }

  function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  function getStatusColor(status: string): string {
    switch (status) {
      case 'active': return 'status-active';
      case 'expired': return 'status-expired';
      case 'pending': return 'status-pending';
      case 'approved': return 'status-approved';
      case 'rejected': return 'status-rejected';
      case 'processing': return 'status-processing';
      default: return 'status-default';
    }
  }

  onMount(loadData);
</script>

<svelte:head>
  <title>Insurance - Manage Your Policies</title>
</svelte:head>

<div class="insurance-page">
  <!-- Header -->
  <header class="page-header">
    <div class="header-content">
      <div>
        <h1>🛡️ Insurance</h1>
        <p class="subtitle">Manage your policies, claims & payments</p>
      </div>
      <button class="btn-primary" on:click={() => activeTab = 'buy'}>
        + Buy Insurance
      </button>
    </div>
  </header>

  <!-- Stats Overview -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📋</div>
      <div class="stat-value">{activePolicies}</div>
      <div class="stat-label">Active Policies</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div class="stat-value">{formatCurrency(totalCoverage)}</div>
      <div class="stat-label">Total Coverage</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💳</div>
      <div class="stat-value">{formatCurrency(totalPremium)}</div>
      <div class="stat-label">Annual Premium</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📝</div>
      <div class="stat-value">{pendingClaims}</div>
      <div class="stat-label">Pending Claims</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'policies'}
      on:click={() => activeTab = 'policies'}
    >
      📋 Policies
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'claims'}
      on:click={() => activeTab = 'claims'}
    >
      📝 Claims ({claims.length})
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'payments'}
      on:click={() => activeTab = 'payments'}
    >
      💳 Payments
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'buy'}
      on:click={() => activeTab = 'buy'}
    >
      ➕ Buy New
    </button>
  </div>

  <!-- Tab Content -->
  <div class="tab-content">
    {#if activeTab === 'policies'}
      <!-- Policies Tab -->
      <div class="policies-grid">
        {#each policies as policy}
          <div class="policy-card" class:expired={policy.status === 'expired'}>
            <div class="policy-header">
              <span class="policy-type" style="background: {policyTypes[policy.type].color}20; color: {policyTypes[policy.type].color}">
                {policyTypes[policy.type].icon} {policyTypes[policy.type].label}
              </span>
              <span class="status-badge {getStatusColor(policy.status)}">
                {policy.status}
              </span>
            </div>
            
            <div class="provider-info">
              <h3>{policy.provider}</h3>
              <p class="policy-number">#{policy.policyNumber}</p>
            </div>

            <div class="policy-details">
              <div class="detail-row">
                <span class="label">Coverage</span>
                <span class="value coverage">{formatCurrency(policy.coverageAmount)}</span>
              </div>
              <div class="detail-row">
                <span class="label">Premium</span>
                <span class="value">{formatCurrency(policy.premium)}/{policy.premiumFrequency}</span>
              </div>
              <div class="detail-row">
                <span class="label">Next Payment</span>
                <span class="value">{formatDate(policy.nextPaymentDate)}</span>
              </div>
              <div class="detail-row">
                <span class="label">Valid Until</span>
                <span class="value">{formatDate(policy.endDate)}</span>
              </div>
            </div>

            <div class="policy-actions">
              <button class="btn-outline" on:click={() => viewPolicyDetails(policy)}>
                View Details
              </button>
              {#if policy.status === 'active'}
                <button class="btn-primary" on:click={() => openPaymentModal(policy)}>
                  Pay Premium
                </button>
              {:else}
                <button class="btn-renew" on:click={() => renewPolicy(policy)}>
                  Renew Policy
                </button>
              {/if}
            </div>
          </div>
        {/each}

        {#if policies.length === 0}
          <div class="empty-state">
            <div class="empty-icon">🛡️</div>
            <h3>No Insurance Policies</h3>
            <p>You don't have any insurance policies yet.</p>
            <button class="btn-primary" on:click={() => activeTab = 'buy'}>
              Buy Your First Policy
            </button>
          </div>
        {/if}
      </div>

    {:else if activeTab === 'claims'}
      <!-- Claims Tab -->
      <div class="claims-list">
        {#each claims as claim}
          {@const policy = getPolicyById(claim.policyId)}
          <div class="claim-card">
            <div class="claim-header">
              <div>
                <h4>{claim.type} Claim</h4>
                <p class="policy-ref">{policy?.provider} - #{policy?.policyNumber}</p>
              </div>
              <span class="status-badge {getStatusColor(claim.status)}">
                {claim.status}
              </span>
            </div>
            
            <div class="claim-details">
              <p class="description">{claim.description}</p>
              <div class="claim-meta">
                <span>Filed: {formatDate(claim.filedDate)}</span>
                {#if claim.resolvedDate}
                  <span>Resolved: {formatDate(claim.resolvedDate)}</span>
                {/if}
              </div>
              <div class="claim-amount">
                Claim Amount: <strong>{formatCurrency(claim.amount)}</strong>
              </div>
            </div>
          </div>
        {/each}

        {#if claims.length === 0}
          <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3>No Claims</h3>
            <p>You haven't filed any claims yet.</p>
          </div>
        {/if}
      </div>

    {:else if activeTab === 'payments'}
      <!-- Payments Tab -->
      <div class="payments-list">
        {#each payments as payment}
          {@const policy = getPolicyById(payment.policyId)}
          <div class="payment-card">
            <div class="payment-info">
              <h4>{policy?.provider}</h4>
              <p class="payment-date">{formatDate(payment.date)}</p>
              <p class="transaction-id">Transaction: {payment.transactionId}</p>
            </div>
            <div class="payment-amount">
              <span class="amount">{formatCurrency(payment.amount)}</span>
              <span class="method">{payment.method}</span>
              <span class="status-badge {getStatusColor(payment.status)}">
                {payment.status}
              </span>
            </div>
          </div>
        {/each}

        {#if payments.length === 0}
          <div class="empty-state">
            <div class="empty-icon">💳</div>
            <h3>No Payments</h3>
            <p>No payment history available.</p>
          </div>
        {/if}
      </div>

    {:else if activeTab === 'buy'}
      <!-- Buy New Policy Tab -->
      <div class="buy-section">
        <h2>Choose Your Insurance</h2>
        <p class="section-desc">Select a policy type to get started</p>

        <div class="policy-types-grid">
          {#each Object.entries(policyTypes) as [type, config]}
            <button class="policy-type-card" on:click={() => {/* TODO: Implement buy flow */}}>
              <div class="type-icon">{config.icon}</div>
              <h3>{config.label}</h3>
              <p>Get comprehensive {config.label.toLowerCase()} coverage</p>
              <span class="starting-price">From ₫2,500,000/year</span>
            </button>
          {/each}
        </div>
      </div>
    {/if}
  </div>
</div>

<!-- Policy Detail Modal -->
{#if showDetailModal && selectedPolicy}
  <div class="modal-overlay" on:click={closeDetailModal} on:keydown={(e) => e.key === 'Escape' && closeDetailModal()} transition:fade={{ duration: 200 }}>
    <div class="modal" on:click|stopPropagation transition:scale={{ duration: 200, start: 0.95 }}>
      <div class="modal-header">
        <h2>Policy Details</h2>
        <button class="close-btn" on:click={closeDetailModal}>×</button>
      </div>
      
      <div class="modal-content">
        <div class="detail-section">
          <div class="detail-header">
            <span class="policy-type" style="background: {policyTypes[selectedPolicy.type].color}20; color: {policyTypes[selectedPolicy.type].color}">
              {policyTypes[selectedPolicy.type].icon} {policyTypes[selectedPolicy.type].label}
            </span>
            <span class="status-badge {getStatusColor(selectedPolicy.status)}">
              {selectedPolicy.status}
            </span>
          </div>
          
          <h3>{selectedPolicy.provider}</h3>
          <p class="policy-number">#{selectedPolicy.policyNumber}</p>
        </div>

        <div class="detail-grid">
          <div class="detail-item">
            <span class="label">Coverage Amount</span>
            <span class="value">{formatCurrency(selectedPolicy.coverageAmount)}</span>
          </div>
          <div class="detail-item">
            <span class="label">Premium</span>
            <span class="value">{formatCurrency(selectedPolicy.premium)}/{selectedPolicy.premiumFrequency}</span>
          </div>
          <div class="detail-item">
            <span class="label">Start Date</span>
            <span class="value">{formatDate(selectedPolicy.startDate)}</span>
          </div>
          <div class="detail-item">
            <span class="label">End Date</span>
            <span class="value">{formatDate(selectedPolicy.endDate)}</span>
          </div>
          <div class="detail-item">
            <span class="label">Last Payment</span>
            <span class="value">{formatDate(selectedPolicy.lastPaymentDate)}</span>
          </div>
          <div class="detail-item">
            <span class="label">Next Payment</span>
            <span class="value">{formatDate(selectedPolicy.nextPaymentDate)}</span>
          </div>
        </div>

        <div class="detail-section">
          <h4>Policy Holder</h4>
          <div class="holder-info">
            <p><strong>Name:</strong> {selectedPolicy.holder.name}</p>
            <p><strong>Email:</strong> {selectedPolicy.holder.email}</p>
            <p><strong>Phone:</strong> {selectedPolicy.holder.phone}</p>
          </div>
        </div>

        <div class="detail-section">
          <h4>Benefits</h4>
          <ul class="benefits-list">
            {#each selectedPolicy.benefits as benefit}
              <li>✓ {benefit}</li>
            {/each}
          </ul>
        </div>

        <div class="modal-actions">
          <button class="btn-outline" on:click={() => openClaimModal(selectedPolicy?.id || '')}>
            📝 File a Claim
          </button>
          <button class="btn-primary" on:click={() => openPaymentModal(selectedPolicy)}>
            💳 Pay Premium
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<!-- Claim Modal -->
{#if showClaimModal && selectedPolicy}
  <div class="modal-overlay" on:click={() => showClaimModal = false} on:keydown={(e) => e.key === 'Escape' && (showClaimModal = false)} transition:fade={{ duration: 200 }}>
    <div class="modal" on:click|stopPropagation transition:scale={{ duration: 200, start: 0.95 }}>
      <div class="modal-header">
        <h2>File a Claim</h2>
        <button class="close-btn" on:click={() => showClaimModal = false}>×</button>
      </div>
      
      <div class="modal-content">
        <p class="modal-subtitle">Policy: {selectedPolicy.provider} - #{selectedPolicy.policyNumber}</p>
        
        <div class="form-group">
          <label for="claim-type">Claim Type</label>
          <select id="claim-type" bind:value={newClaim.type}>
            <option value="">Select type...</option>
            <option value="Medical">Medical</option>
            <option value="Accident">Accident</option>
            <option value="Theft">Theft</option>
            <option value="Damage">Damage</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="claim-description">Description</label>
          <textarea id="claim-description" bind:value={newClaim.description} placeholder="Describe the incident..." rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="claim-amount">Claim Amount (₫)</label>
          <input type="number" id="claim-amount" bind:value={newClaim.amount} placeholder="0" />
        </div>

        <div class="modal-actions">
          <button class="btn-outline" on:click={() => showClaimModal = false}>Cancel</button>
          <button class="btn-primary" on:click={submitClaim}>Submit Claim</button>
        </div>
      </div>
    </div>
  </div>
{/if}

<!-- Payment Modal -->
{#if showPaymentModal && selectedPolicy}
  <div class="modal-overlay" on:click={() => showPaymentModal = false} on:keydown={(e) => e.key === 'Escape' && (showPaymentModal = false)} transition:fade={{ duration: 200 }}>
    <div class="modal" on:click|stopPropagation transition:scale={{ duration: 200, start: 0.95 }}>
      <div class="modal-header">
        <h2>Pay Premium</h2>
        <button class="close-btn" on:click={() => showPaymentModal = false}>×</button>
      </div>
      
      <div class="modal-content">
        <div class="payment-summary">
          <p class="policy-name">{selectedPolicy.provider}</p>
          <p class="policy-number">#{selectedPolicy.policyNumber}</p>
          <p class="payment-amount">Amount: <strong>{formatCurrency(selectedPolicy.premium)}</strong></p>
        </div>

        <div class="form-group">
          <label>Payment Method</label>
          <div class="payment-methods">
            <label class="method-option" class:selected={paymentForm.method === 'card'}>
              <input type="radio" bind:group={paymentForm.method} value="card" />
              💳 Credit/Debit Card
            </label>
            <label class="method-option" class:selected={paymentForm.method === 'bank'}>
              <input type="radio" bind:group={paymentForm.method} value="bank" />
              🏦 Bank Transfer
            </label>
          </div>
        </div>

        {#if paymentForm.method === 'card'}
          <div class="form-group">
            <label for="card-number">Card Number</label>
            <input type="text" id="card-number" bind:value={paymentForm.cardNumber} placeholder="1234 5678 9012 3456" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="expiry">Expiry Date</label>
              <input type="text" id="expiry" bind:value={paymentForm.expiry} placeholder="MM/YY" />
            </div>
            <div class="form-group">
              <label for="cvv">CVV</label>
              <input type="text" id="cvv" bind:value={paymentForm.cvv} placeholder="123" />
            </div>
          </div>
        {/if}

        <div class="modal-actions">
          <button class="btn-outline" on:click={() => showPaymentModal = false}>Cancel</button>
          <button class="btn-primary" on:click={processPayment}>
            Pay {formatCurrency(selectedPolicy.premium)}
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .insurance-page {
    min-height: 100vh;
    background: #f8fafc;
    padding-bottom: 2rem;
  }

  .page-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    padding: 2rem;
    margin-bottom: 1.5rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .page-header h1 {
    color: white;
    margin: 0 0 0.25rem 0;
    font-size: 1.75rem;
  }

  .subtitle {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
  }

  .btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .btn-outline {
    background: transparent;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 0.625rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-outline:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
  }

  .btn-renew {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 0.625rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
  }

  .btn-renew:hover {
    background: #d97706;
  }

  .stats-grid {
    max-width: 1200px;
    margin: 0 auto 1.5rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    padding: 0 1rem;
  }

  .stat-card {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    text-align: center;
  }

  .stat-icon {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.25rem;
  }

  .stat-label {
    color: #64748b;
    font-size: 0.875rem;
  }

  .tabs {
    max-width: 1200px;
    margin: 0 auto 1.5rem;
    display: flex;
    gap: 0.5rem;
    padding: 0 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .tab {
    background: none;
    border: none;
    padding: 0.75rem 1.25rem;
    font-size: 0.9375rem;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
  }

  .tab:hover {
    color: #1e293b;
  }

  .tab.active {
    color: #10b981;
    border-bottom-color: #10b981;
  }

  .tab-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  .policies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.25rem;
  }

  .policy-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .policy-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .policy-card.expired {
    opacity: 0.7;
  }

  .policy-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .policy-type {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 500;
  }

  .status-badge {
    padding: 0.25rem 0.625rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
  }

  .status-active { background: #d1fae5; color: #065f46; }
  .status-expired { background: #fee2e2; color: #991b1b; }
  .status-pending { background: #fef3c7; color: #92400e; }
  .status-approved { background: #d1fae5; color: #065f46; }
  .status-rejected { background: #fee2e2; color: #991b1b; }
  .status-processing { background: #dbeafe; color: #1e40af; }
  .status-default { background: #f1f5f9; color: #475569; }

  .provider-info h3 {
    margin: 0 0 0.25rem 0;
    color: #1e293b;
    font-size: 1.125rem;
  }

  .policy-number {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0 0 1rem 0;
  }

  .policy-details {
    margin-bottom: 1.25rem;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .detail-row .label {
    color: #64748b;
    font-size: 0.875rem;
  }

  .detail-row .value {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.875rem;
  }

  .detail-row .value.coverage {
    color: #10b981;
    font-weight: 600;
  }

  .policy-actions {
    display: flex;
    gap: 0.75rem;
  }

  .policy-actions button {
    flex: 1;
    font-size: 0.875rem;
  }

  .claims-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .claim-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  }

  .claim-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
  }

  .claim-header h4 {
    margin: 0 0 0.25rem 0;
    color: #1e293b;
  }

  .policy-ref {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
  }

  .claim-details .description {
    color: #475569;
    margin-bottom: 0.75rem;
  }

  .claim-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0.75rem;
  }

  .claim-amount {
    font-size: 0.9375rem;
    color: #1e293b;
  }

  .payments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .payment-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .payment-info h4 {
    margin: 0 0 0.25rem 0;
    color: #1e293b;
  }

  .payment-date {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0 0 0.25rem 0;
  }

  .transaction-id {
    color: #94a3b8;
    font-size: 0.75rem;
    margin: 0;
    font-family: monospace;
  }

  .payment-amount {
    text-align: right;
  }

  .payment-amount .amount {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  .payment-amount .method {
    display: block;
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.25rem 0;
  }

  .buy-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  }

  .buy-section h2 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .section-desc {
    color: #64748b;
    margin-bottom: 1.5rem;
  }

  .policy-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }

  .policy-type-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
  }

  .policy-type-card:hover {
    border-color: #10b981;
    background: #f0fdf4;
  }

  .type-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
  }

  .policy-type-card h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.0625rem;
  }

  .policy-type-card p {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0 0 0.75rem 0;
  }

  .starting-price {
    color: #10b981;
    font-weight: 600;
    font-size: 0.9375rem;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .empty-state p {
    color: #64748b;
    margin-bottom: 1.25rem;
  }

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

  .modal-content {
    padding: 1.5rem;
  }

  .modal-subtitle {
    color: #64748b;
    margin: 0 0 1.25rem 0;
    font-size: 0.9375rem;
  }

  .detail-section {
    margin-bottom: 1.5rem;
  }

  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .detail-section h4 {
    margin: 0 0 0.75rem 0;
    color: #1e293b;
    font-size: 1rem;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .detail-item .label {
    display: block;
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .detail-item .value {
    font-weight: 500;
    color: #1e293b;
  }

  .holder-info p {
    margin: 0.375rem 0;
    color: #475569;
    font-size: 0.9375rem;
  }

  .benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .benefits-list li {
    padding: 0.5rem 0;
    color: #475569;
    font-size: 0.9375rem;
  }

  .modal-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }

  .modal-actions button {
    flex: 1;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: border-color 0.2s;
    box-sizing: border-box;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
  }

  .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .method-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .method-option:hover {
    border-color: #10b981;
  }

  .method-option.selected {
    border-color: #10b981;
    background: #f0fdf4;
  }

  .payment-summary {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    text-align: center;
  }

  .payment-summary .policy-name {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
    color: #1e293b;
  }

  .payment-summary .policy-number {
    margin: 0 0 0.75rem 0;
    color: #64748b;
    font-size: 0.875rem;
  }

  .payment-summary .payment-amount {
    font-size: 1.125rem;
    color: #1e293b;
  }

  @media (max-width: 640px) {
    .header-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .tabs {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .policies-grid {
      grid-template-columns: 1fr;
    }

    .payment-card {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .payment-amount {
      text-align: left;
    }
  }
</style>
