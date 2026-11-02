<script lang="ts">
  import { paymentsStore, loadPaymentMethods, processPayment } from '../state/paymentsStore';
  import { onMount } from 'svelte';
  import type { PaymentMethod, Transaction } from '../contracts/paymentsTypes';

  let activeTab = 'methods';
  let showAddCard = false;
  let processingPayment = false;
  let selectedMethod: PaymentMethod | null = null;

  let newCard = {
    number: '',
    expiry: '',
    cvv: '',
    name: ''
  };

  onMount(() => {
    loadPaymentMethods();
  });

  $: paymentMethods = $paymentsStore.paymentMethods;
  $: transactions = $paymentsStore.transactions;
  $: loading = $paymentsStore.loading;

  async function handleAddCard() {
    try {
      await paymentsStore.addPaymentMethod({
        type: 'card',
        ...newCard
      });
      showAddCard = false;
      newCard = { number: '', expiry: '', cvv: '', name: '' };
    } catch (error) {
      console.error('Failed to add card:', error);
    }
  }

  function formatCardNumber(num: string) {
    return '•••• '.repeat(3) + num.slice(-4);
  }

  function getCardIcon(type: string) {
    switch (type) {
      case 'visa': return '💳 Visa';
      case 'mastercard': return '💳 Mastercard';
      case 'amex': return '💳 Amex';
      default: return '💳 Card';
    }
  }

  function getStatusColor(status: string) {
    switch (status) {
      case 'completed': return 'text-green-600 bg-green-100';
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'failed': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  }
</script>

<div class="payments-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">Payments</h1>

      <div class="flex gap-4 mb-6 border-b">
        <button 
          class="px-4 py-2 font-medium {activeTab === 'methods' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'methods'}
        >
          Payment Methods
        </button>
        <button 
          class="px-4 py-2 font-medium {activeTab === 'history' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'history'}
        >
          Transaction History
        </button>
        <button 
          class="px-4 py-2 font-medium {activeTab === 'checkout' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'}"
          onclick={() => activeTab = 'checkout'}
        >
          Checkout
        </button>
      </div>

      {#if loading}
        <div class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else if activeTab === 'methods'}
        <div class="space-y-4">
          {#each paymentMethods as method}
            <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">
                  {method.type === 'card' ? '💳' : method.type === 'bank' ? '🏦' : '💰'}
                </div>
                <div>
                  <p class="font-semibold">{getCardIcon(method.cardType || 'card')}</p>
                  <p class="text-gray-600 text-sm">{formatCardNumber(method.last4 || '')}</p>
                  <p class="text-gray-500 text-xs">Expires {method.expiry}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                {#if method.isDefault}
                  <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Default</span>
                {/if}
                <button 
                  class="text-red-500 hover:text-red-700 p-2"
                  onclick={() => paymentsStore.removePaymentMethod(method.id)}
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          {/each}

          <button 
            class="w-full py-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors"
            onclick={() => showAddCard = true}
          >
            + Add Payment Method
          </button>
        </div>

        {#if showAddCard}
          <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
              <h2 class="text-xl font-bold mb-4">Add Card</h2>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                  <input 
                    type="text" 
                    bind:value={newCard.number}
                    placeholder="1234 5678 9012 3456"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div class="flex gap-4">
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label>
                    <input 
                      type="text" 
                      bind:value={newCard.expiry}
                      placeholder="MM/YY"
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                    <input 
                      type="text" 
                      bind:value={newCard.cvv}
                      placeholder="123"
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
                  <input 
                    type="text" 
                    bind:value={newCard.name}
                    placeholder="John Doe"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
              <div class="flex gap-2 mt-6">
                <button 
                  class="flex-1 py-2 border rounded-lg hover:bg-gray-50"
                  onclick={() => showAddCard = false}
                >
                  Cancel
                </button>
                <button 
                  class="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  onclick={handleAddCard}
                >
                  Add Card
                </button>
              </div>
            </div>
          </div>
        {/if}

      {:else if activeTab === 'history'}
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Transaction</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Amount</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              {#each transactions as transaction}
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <span class="text-2xl">{transaction.icon || '💳'}</span>
                      <div>
                        <p class="font-medium">{transaction.description}</p>
                        <p class="text-sm text-gray-500">{transaction.id}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600">{new Date(transaction.date).toLocaleDateString()}</td>
                  <td class="px-4 py-3 font-medium">${transaction.amount.toFixed(2)}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full {getStatusColor(transaction.status)}">
                      {transaction.status}
                    </span>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>

      {:else if activeTab === 'checkout'}
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold mb-4">Quick Payment</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                <input 
                  type="number" 
                  class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="0.00"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
              <select class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                {#each paymentMethods as method}
                  <option value={method.id}>{getCardIcon(method.cardType || 'card')} ending in {method.last4}</option>
                {/each}
              </select>
            </div>
            <button 
              class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
              onclick={() => processPayment({ amount: 100, currency: 'USD' })}
            >
              Pay Now
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .payments-page {
    min-height: 100vh;
    background: #f9fafb;
  }
</style>
