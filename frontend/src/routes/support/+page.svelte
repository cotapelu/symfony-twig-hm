<script lang="ts">
  import { onMount } from 'svelte';

  interface Ticket {
    id: number;
    subject: string;
    category: string;
    priority: string;
    status: string;
    created_at: string;
  }

  let tickets: Ticket[] = [];
  let loading = false;
  let activeFilter = 'all';
  let error = '';

  async function loadTickets() {
    loading = true;
    try {
      const res = await fetch('/api/customer-service/tickets');
      const data = await res.json();
      tickets = data.data || [];
    } catch (e) {
      error = 'Failed to load tickets';
    }
    loading = false;
  }

  onMount(loadTickets);

  $: filteredTickets = activeFilter === 'all' 
    ? tickets 
    : tickets.filter(t => t.status === activeFilter);

  function getPriorityColor(priority: string) {
    switch(priority) {
      case 'urgent': return '#dc3545';
      case 'high': return '#fd7e14';
      case 'medium': return '#ffc107';
      default: return '#28a745';
    }
  }
</script>

<div class="support-page">
  <header>
    <h1>🎧 Customer Support</h1>
    <button class="primary">+ New Ticket</button>
  </header>

  <div class="filters">
    {#each ['all', 'open', 'in_progress', 'resolved', 'closed'] as filter}
      <button 
        class:active={activeFilter === filter}
        on:click={() => activeFilter = filter}
      >
        {filter.replace('_', ' ')}
      </button>
    {/each}
  </div>

  {#if loading}
    <div class="loading">Loading tickets...</div>
  {:else if error}
    <div class="error">{error}</div>
  {:else}
    <div class="ticket-list">
      {#each filteredTickets as ticket}
        <div class="ticket-item">
          <div class="ticket-main">
            <span class="ticket-id">#{ticket.id}</span>
            <h3>{ticket.subject}</h3>
            <span class="category">{ticket.category}</span>
          </div>
          <div class="ticket-meta">
            <span class="priority" style="background: {getPriorityColor(ticket.priority)}">
              {ticket.priority}
            </span>
            <span class="status" class:open={ticket.status === 'open'}>
              {ticket.status.replace('_', ' ')}
            </span>
            <span class="date">{new Date(ticket.created_at).toLocaleDateString()}</span>
          </div>
        </div>
      {/each}
    </div>
    {#if filteredTickets.length === 0}
      <div class="empty">No tickets found</div>
    {/if}
  {/if}
</div>

<style>
  .support-page { padding: 20px; max-width: 900px; margin: 0 auto; }
  header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  h1 { font-size: 1.8rem; }
  .primary { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
  .filters { display: flex; gap: 10px; margin-bottom: 20px; }
  .filters button {
    padding: 6px 14px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    text-transform: capitalize;
  }
  .filters button.active { background: #007bff; color: white; border-color: #007bff; }
  .ticket-list { display: flex; flex-direction: column; gap: 10px; }
  .ticket-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .ticket-main h3 { margin: 5px 0; font-size: 1rem; }
  .ticket-id { color: #999; font-size: 0.85rem; }
  .category { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; }
  .ticket-meta { display: flex; align-items: center; gap: 10px; }
  .priority { color: white; padding: 3px 10px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; }
  .status { padding: 3px 10px; border-radius: 4px; font-size: 0.8rem; background: #e9ecef; }
  .status.open { background: #cce5ff; color: #004085; }
  .date { color: #999; font-size: 0.85rem; }
</style>
