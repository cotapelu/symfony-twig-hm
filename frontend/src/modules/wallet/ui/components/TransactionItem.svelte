<script lang="ts">
  import type { Transaction } from '../../contracts/walletTypes';

  export let transaction: Transaction;
  export let compact = false;

  function formatCurrency(amount: number, currency: string) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD'
    }).format(amount);
  }

  function getTransactionIcon(type: string) {
    switch (type) {
      case 'deposit': return '⬇️';
      case 'withdraw': return '⬆️';
      case 'transfer_in': return '↙️';
      case 'transfer_out': return '↗️';
      case 'payment': return '💳';
      case 'refund': return '↩️';
      case 'fee': return '💸';
      default: return '💰';
    }
  }

  function getTransactionColor(type: string) {
    switch (type) {
      case 'deposit':
      case 'transfer_in':
      case 'refund':
        return 'text-green-600';
      case 'withdraw':
      case 'transfer_out':
      case 'payment':
      case 'fee':
        return 'text-red-600';
      default:
        return 'text-gray-600';
    }
  }

  function getTransactionBg(type: string) {
    switch (type) {
      case 'deposit':
      case 'transfer_in':
      case 'refund':
        return 'bg-green-50';
      case 'withdraw':
      case 'transfer_out':
      case 'payment':
      case 'fee':
        return 'bg-red-50';
      default:
        return 'bg-gray-50';
    }
  }

  function getTransactionSign(type: string) {
    switch (type) {
      case 'deposit':
      case 'transfer_in':
      case 'refund':
        return '+';
      case 'withdraw':
      case 'transfer_out':
      case 'payment':
      case 'fee':
        return '-';
      default:
        return '';
    }
  }

  function getStatusColor(status: string) {
    switch (status) {
      case 'completed': return 'bg-green-100 text-green-800';
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'failed': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  }

  function formatTime(dateStr: string) {
    return new Date(dateStr).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }
</script>

<div class="transaction-item">
  {#if compact}
    <!-- Compact view for lists -->
    <div class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full {getTransactionBg(transaction.type)} flex items-center justify-center text-lg">
          {getTransactionIcon(transaction.type)}
        </div>
        <div>
          <p class="font-medium text-gray-900 capitalize">{transaction.type.replace('_', ' ')}</p>
          <p class="text-xs text-gray-500">{formatTime(transaction.date)}</p>
        </div>
      </div>
      <div class="text-right">
        <p class="font-semibold {getTransactionColor(transaction.type)}">
          {getTransactionSign(transaction.type)}{formatCurrency(transaction.amount, transaction.currency)}
        </p>
        <span class="text-xs px-2 py-0.5 rounded-full {getStatusColor(transaction.status)}">
          {transaction.status}
        </span>
      </div>
    </div>
  {:else}
    <!-- Full view -->
    <div class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl {getTransactionBg(transaction.type)} flex items-center justify-center text-xl shadow-sm">
          {getTransactionIcon(transaction.type)}
        </div>
        <div>
          <p class="font-semibold text-gray-900 capitalize">{transaction.type.replace('_', ' ')}</p>
          {#if transaction.description}
            <p class="text-sm text-gray-500">{transaction.description}</p>
          {/if}
          <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-400">{formatTime(transaction.date)}</span>
            <span class="text-xs px-2 py-0.5 rounded-full {getStatusColor(transaction.status)}">
              {transaction.status}
            </span>
          </div>
        </div>
      </div>
      <div class="text-right">
        <p class="text-lg font-bold {getTransactionColor(transaction.type)}">
          {getTransactionSign(transaction.type)}{formatCurrency(transaction.amount, transaction.currency)}
        </p>
        {#if transaction.recipientId}
          <p class="text-xs text-gray-500 mt-1">To: {transaction.recipientId}</p>
        {/if}
        {#if transaction.senderId}
          <p class="text-xs text-gray-500 mt-1">From: {transaction.senderId}</p>
        {/if}
      </div>
    </div>
  {/if}
</div>