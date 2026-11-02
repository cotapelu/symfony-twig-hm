<script lang="ts">
  import type { Transaction } from '../../contracts/walletTypes';
  import TransactionItem from './TransactionItem.svelte';

  export let transactions: Transaction[] = [];
  export let loading = false;
  export let hasMore = false;
  export let onLoadMore: (() => void) | null = null;

  function groupByDate(transactions: Transaction[]) {
    const groups: Record<string, Transaction[]> = {};
    
    transactions.forEach(t => {
      const date = new Date(t.date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      
      if (!groups[date]) {
        groups[date] = [];
      }
      groups[date].push(t);
    });
    
    return groups;
  }

  $: groupedTransactions = groupByDate(transactions);
</script>

<div class="transaction-list">
  {#if loading && transactions.length === 0}
    <div class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>
  {:else if transactions.length === 0}
    <div class="text-center py-12 text-gray-500">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <p class="font-medium">No transactions yet</p>
      <p class="text-sm mt-1">Your transaction history will appear here</p>
    </div>
  {:else}
    <div class="space-y-6">
      {#each Object.entries(groupedTransactions) as [date, dayTransactions]}
        <div class="transaction-group">
          <h3 class="text-sm font-semibold text-gray-500 mb-3 sticky top-0 bg-gray-50 py-2">
            {date}
          </h3>
          <div class="bg-white rounded-xl shadow-sm border divide-y">
            {#each dayTransactions as transaction (transaction.id)}
              <TransactionItem {transaction} />
            {/each}
          </div>
        </div>
      {/each}
      
      {#if hasMore}
        <div class="text-center py-4">
          <button 
            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium transition-colors"
            onclick={() => onLoadMore?.()}
            disabled={loading}
          >
            {loading ? 'Loading...' : 'Load More'}
          </button>
        </div>
      {/if}
    </div>
  {/if}
</div>