<script lang="ts">
  import { walletStore, loadWallet, deposit, withdraw, transfer } from '../state/walletStore';
  import { onMount } from 'svelte';
  import type { Transaction } from '../contracts/walletTypes';

  let activeTab = 'overview';
  let showDepositModal = false;
  let showWithdrawModal = false;
  let showTransferModal = false;

  let depositAmount = '';
  let withdrawAmount = '';
  let transferAmount = '';
  let transferRecipient = '';
  let transferNote = '';

  onMount(() => {
    loadWallet();
  });

  $: wallet = $walletStore.wallet;
  $: transactions = $walletStore.transactions;
  $: loading = $walletStore.loading;

  async function handleDeposit() {
    const amount = parseFloat(depositAmount);
    if (amount > 0) {
      await deposit({ amount, currency: wallet?.currency || 'USD' });
      showDepositModal = false;
      depositAmount = '';
    }
  }

  async function handleWithdraw() {
    const amount = parseFloat(withdrawAmount);
    if (amount > 0 && wallet && amount <= wallet.balance) {
      await withdraw({ amount, currency: wallet.currency });
      showWithdrawModal = false;
      withdrawAmount = '';
    }
  }

  async function handleTransfer() {
    const amount = parseFloat(transferAmount);
    if (amount > 0 && transferRecipient && wallet && amount <= wallet.balance) {
      await transfer({
        amount,
        currency: wallet.currency,
        recipientId: transferRecipient,
        note: transferNote
      });
      showTransferModal = false;
      transferAmount = '';
      transferRecipient = '';
      transferNote = '';
    }
  }

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
        return 'text-red-600';
      default:
        return 'text-gray-600';
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
        return '-';
      default:
        return '';
    }
  }
</script>

<div class="wallet-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">Wallet</h1>

      {#if loading && !wallet}
        <div class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else if wallet}
        <div class="bg-gradient-to-br from-blue-600 to-purple-700 rounded-2xl p-6 mb-8 text-white">
          <div class="flex justify-between items-start mb-4">
            <div>
              <p class="text-blue-100 text-sm">Available Balance</p>
              <p class="text-4xl font-bold">{formatCurrency(wallet.balance, wallet.currency)}</p>
            </div>
            <div class="bg-white/20 backdrop-blur rounded-lg p-2">
              <span class="text-2xl">👛</span>
            </div>
          </div>
          <div class="flex gap-4 text-sm">
            <span class="bg-white/20 px-3 py-1 rounded-full">{wallet.currency}</span>
            <span class="bg-white/20 px-3 py-1 rounded-full">Active</span>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8">
          <button 
            class="bg-green-500 hover:bg-green-600 text-white py-4 rounded-xl font-medium transition-colors flex flex-col items-center gap-2"
            onclick={() => showDepositModal = true}
          >
            <span class="text-2xl">⬇️</span>
            <span>Deposit</span>
          </button>
          <button 
            class="bg-red-500 hover:bg-red-600 text-white py-4 rounded-xl font-medium transition-colors flex flex-col items-center gap-2"
            onclick={() => showWithdrawModal = true}
          >
            <span class="text-2xl">⬆️</span>
            <span>Withdraw</span>
          </button>
          <button 
            class="bg-blue-500 hover:bg-blue-600 text-white py-4 rounded-xl font-medium transition-colors flex flex-col items-center gap-2"
            onclick={() => showTransferModal = true}
          >
            <span class="text-2xl">↔️</span>
            <span>Transfer</span>
          </button>
        </div>

        <div class="flex gap-4 mb-6 border-b">
          <button 
            class="px-4 py-2 font-medium {activeTab === 'overview' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
            onclick={() => activeTab = 'overview'}
          >
            Overview
          </button>
          <button 
            class="px-4 py-2 font-medium {activeTab === 'transactions' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
            onclick={() => activeTab = 'transactions'}
          >
            Transactions
          </button>
          <button 
            class="px-4 py-2 font-medium {activeTab === 'settings' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
            onclick={() => activeTab = 'settings'}
          >
            Settings
          </button>
        </div>

        {#if activeTab === 'overview'}
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Quick Stats</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">Total Deposited</p>
                <p class="text-xl font-semibold text-green-600">
                  {formatCurrency(transactions.filter(t => t.type === 'deposit').reduce((sum, t) => sum + t.amount, 0), wallet.currency)}
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">Total Spent</p>
                <p class="text-xl font-semibold text-red-600">
                  {formatCurrency(transactions.filter(t => t.type === 'payment' || t.type === 'withdraw').reduce((sum, t) => sum + t.amount, 0), wallet.currency)}
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">Transfers In</p>
                <p class="text-xl font-semibold text-blue-600">
                  {formatCurrency(transactions.filter(t => t.type === 'transfer_in').reduce((sum, t) => sum + t.amount, 0), wallet.currency)}
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">Transfers Out</p>
                <p class="text-xl font-semibold text-orange-600">
                  {formatCurrency(transactions.filter(t => t.type === 'transfer_out').reduce((sum, t) => sum + t.amount, 0), wallet.currency)}
                </p>
              </div>
            </div>
          </div>

        {:else if activeTab === 'transactions'}
          <div class="bg-white rounded-lg shadow overflow-hidden">
            {#if transactions.length === 0}
              <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">📊</div>
                <p>No transactions yet</p>
              </div>
            {:else}
              <div class="divide-y">
                {#each transactions.slice(0, 20) as transaction}
                  <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-4">
                      <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-xl">
                        {getTransactionIcon(transaction.type)}
                      </div>
                      <div>
                        <p class="font-medium capitalize">{transaction.type.replace('_', ' ')}</p>
                        <p class="text-sm text-gray-500">{new Date(transaction.date).toLocaleDateString()}</p>
                        {#if transaction.description}
                          <p class="text-xs text-gray-400">{transaction.description}</p>
                        {/if}
                      </div>
                    </div>
                    <p class="font-semibold {getTransactionColor(transaction.type)}">
                      {getTransactionSign(transaction.type)}{formatCurrency(transaction.amount, transaction.currency)}
                    </p>
                  </div>
                {/each}
              </div>
            {/if}
          </div>

        {:else if activeTab === 'settings'}
          <div class="bg-white rounded-lg shadow p-6 space-y-6">
            <div>
              <h3 class="font-semibold mb-4">Wallet Settings</h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b">
                  <div>
                    <p class="font-medium">Currency</p>
                    <p class="text-sm text-gray-500">Primary currency for your wallet</p>
                  </div>
                  <select class="px-3 py-2 border rounded-lg">
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                    <option value="GBP">GBP (£)</option>
                  </select>
                </div>
                <div class="flex items-center justify-between py-3 border-b">
                  <div>
                    <p class="font-medium">Transaction Notifications</p>
                    <p class="text-sm text-gray-500">Get notified for all transactions</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" checked class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>
                <div class="flex items-center justify-between py-3">
                  <div>
                    <p class="font-medium">Auto-Convert</p>
                    <p class="text-sm text-gray-500">Automatically convert incoming payments</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        {/if}
      {/if}
    </div>
  </div>
</div>

{#if showDepositModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Deposit Funds</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
            <input 
              type="number" 
              bind:value={depositAmount}
              placeholder="0.00"
              class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
            />
          </div>
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button 
          class="flex-1 py-2 border rounded-lg hover:bg-gray-50"
          onclick={() => showDepositModal = false}
        >
          Cancel
        </button>
        <button 
          class="flex-1 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          onclick={handleDeposit}
        >
          Deposit
        </button>
      </div>
    </div>
  </div>
{/if}

{#if showWithdrawModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Withdraw Funds</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
            <input 
              type="number" 
              bind:value={withdrawAmount}
              placeholder="0.00"
              max={wallet?.balance}
              class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500"
            />
          </div>
          <p class="text-sm text-gray-500 mt-1">Available: {formatCurrency(wallet?.balance || 0, wallet?.currency || 'USD')}</p>
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button 
          class="flex-1 py-2 border rounded-lg hover:bg-gray-50"
          onclick={() => showWithdrawModal = false}
        >
          Cancel
        </button>
        <button 
          class="flex-1 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          onclick={handleWithdraw}
        >
          Withdraw
        </button>
      </div>
    </div>
  </div>
{/if}

{#if showTransferModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Transfer Funds</h2>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Recipient ID / Email</label>
          <input 
            type="text" 
            bind:value={transferRecipient}
            placeholder="Enter recipient..."
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
            <input 
              type="number" 
              bind:value={transferAmount}
              placeholder="0.00"
              class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note (Optional)</label>
          <textarea 
            bind:value={transferNote}
            placeholder="Add a note..."
            rows="2"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
      </div>
      <div class="flex gap-2 mt-6">
        <button 
          class="flex-1 py-2 border rounded-lg hover:bg-gray-50"
          onclick={() => showTransferModal = false}
        >
          Cancel
        </button>
        <button 
          class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          onclick={handleTransfer}
        >
          Transfer
        </button>
      </div>
    </div>
  </div>
{/if}

<style>
  .wallet-page {
    min-height: 100vh;
    background: #f9fafb;
  }
</style>
