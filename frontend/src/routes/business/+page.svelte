<script lang="ts">
  import { onMount } from 'svelte';

  interface BusinessAccount {
    id: number;
    business_name: string;
    business_type: string;
    is_verified: boolean;
    phone?: string;
    email?: string;
  }

  let accounts: BusinessAccount[] = [];
  let loading = false;
  let error = '';

  async function loadAccounts() {
    loading = true;
    try {
      const res = await fetch('/api/business-accounts');
      const data = await res.json();
      accounts = data.data || [];
    } catch (e) {
      error = 'Failed to load business accounts';
    }
    loading = false;
  }

  onMount(loadAccounts);
</script>

<div class="business-page">
  <header>
    <h1>🏪 Business Accounts</h1>
    <button class="primary">+ Register Business</button>
  </header>

  {#if loading}
    <div class="loading">Loading...</div>
  {:else if error}
    <div class="error">{error}</div>
  {:else}
    <div class="account-grid">
      {#each accounts as account}
        <div class="account-card">
          <div class="card-header">
            <h3>{account.business_name}</h3>
            {#if account.is_verified}
              <span class="verified">✓ Verified</span>
            {/if}
          </div>
          <span class="type-badge">{account.business_type}</span>
          <div class="contact-info">
            {#if account.phone}<p>📞 {account.phone}</p>{/if}
            {#if account.email}<p>✉️ {account.email}</p>{/if}
          </div>
          <div class="actions">
            <button class="secondary">Edit</button>
            <button class="outline">View Details</button>
          </div>
        </div>
      {/each}
    </div>
    {#if accounts.length === 0}
      <div class="empty-state">
        <p>No business accounts yet</p>
        <button class="primary">Register Your First Business</button>
      </div>
    {/if}
  {/if}
</div>

<style>
  .business-page { padding: 20px; max-width: 1000px; margin: 0 auto; }
  header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  h1 { font-size: 1.8rem; }
  .primary { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
  .account-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
  .account-card {
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
  h3 { margin: 0; }
  .verified { background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; }
  .type-badge { background: #e9ecef; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; }
  .contact-info { margin: 15px 0; color: #666; }
  .contact-info p { margin: 5px 0; }
  .actions { display: flex; gap: 10px; }
  .secondary { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
  .outline { background: transparent; border: 1px solid #ddd; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
  .empty-state { text-align: center; padding: 40px; color: #666; }
  .empty-state p { margin-bottom: 15px; }
</style>
