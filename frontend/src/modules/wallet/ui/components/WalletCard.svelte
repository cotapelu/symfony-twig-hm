<script lang="ts">
  import type { Wallet } from '../../contracts/walletTypes';

  export let wallet: Wallet;
  export let onDeposit: (() => void) | null = null;
  export let onWithdraw: (() => void) | null = null;
  export let onTransfer: (() => void) | null = null;

  function formatCurrency(amount: number, currency: string) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD'
    }).format(amount);
  }

  function getCardGradient(currency: string) {
    switch (currency) {
      case 'USD': return 'from-blue-600 to-blue-800';
      case 'EUR': return 'from-blue-500 to-indigo-600';
      case 'GBP': return 'from-purple-600 to-purple-800';
      default: return 'from-blue-600 to-purple-700';
    }
  }
</script>

<div class="wallet-card">
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {getCardGradient(wallet.currency)} p-6 text-white shadow-xl">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
    <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>
    
    <!-- Card content -->
    <div class="relative z-10">
      <div class="flex justify-between items-start mb-8">
        <div>
          <p class="text-white/80 text-sm font-medium">Available Balance</p>
          <p class="text-4xl font-bold mt-1">{formatCurrency(wallet.balance, wallet.currency)}</p>
        </div>
        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-2">
          <span class="text-2xl">👛</span>
        </div>
      </div>

      <div class="flex items-center gap-3 mb-6">
        <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium">
          {wallet.currency}
        </span>
        <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium capitalize">
          {wallet.status}
        </span>
      </div>

      <!-- Action buttons -->
      <div class="grid grid-cols-3 gap-3">
        <button 
          class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl py-3 px-4 transition-all flex flex-col items-center gap-1"
          onclick={() => onDeposit?.()}
        >
          <span class="text-xl">⬇️</span>
          <span class="text-xs font-medium">Deposit</span>
        </button>
        <button 
          class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl py-3 px-4 transition-all flex flex-col items-center gap-1"
          onclick={() => onWithdraw?.()}
        >
          <span class="text-xl">⬆️</span>
          <span class="text-xs font-medium">Withdraw</span>
        </button>
        <button 
          class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl py-3 px-4 transition-all flex flex-col items-center gap-1"
          onclick={() => onTransfer?.()}
        >
          <span class="text-xl">↔️</span>
          <span class="text-xs font-medium">Transfer</span>
        </button>
      </div>
    </div>
  </div>
</div>