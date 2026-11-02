<script lang="ts">
  import { paymentsStore } from '../../state/paymentsStore';
  import type { PaymentMethod } from '../../contracts/paymentsTypes';
  import { 
    validateCardNumber, 
    validateExpiry, 
    validateCVV, 
    validateAmount,
    formatCardNumber,
    detectCardType,
    validateCardInput
  } from '../../utils/cardValidation';

  export let amount = 0;
  export let currency = 'USD';
  export let onSuccess: (result: any) => void = () => {};
  export let onError: (error: any) => void = () => {};

  let selectedMethodId = '';
  let processing = false;
  let error: string | null = null;
  
  let cardNumber = '';
  let cardExpiry = '';
  let cardCVV = '';
  let cardName = '';
  let showAddCard = false;

  $: paymentMethods = $paymentsStore.paymentMethods;
  $: selectedMethod = paymentMethods.find((m: PaymentMethod) => m.id === selectedMethodId);
  $: detectedCardType = cardNumber ? detectCardType(cardNumber) : null;

  async function handlePayment() {
    if (!selectedMethodId) {
      error = 'Please select a payment method';
      return;
    }

    processing = true;
    error = null;

    try {
      const validation = validateAmount(amount);
      if (!validation.valid) {
        throw new Error(validation.error);
      }

      const result = await paymentsStore.processPayment({
        amount,
        currency,
        paymentMethodId: selectedMethodId
      });
      onSuccess(result);
    } catch (err) {
      error = err instanceof Error ? err.message : 'Payment failed';
      onError(err);
    } finally {
      processing = false;
    }
  }

  async function handleAddCard() {
    const validation = validateCardInput(cardNumber, cardExpiry, cardCVV, amount);
    
    if (!validation.valid) {
      error = validation.errors.join(', ');
      return;
    }

    processing = true;
    error = null;

    try {
      await paymentsStore.addPaymentMethod({
        type: 'card',
        cardType: detectedCardType || undefined,
        last4: cardNumber.slice(-4),
        expiry: cardExpiry,
        name: cardName,
        isDefault: paymentMethods.length === 0
      });
      
      showAddCard = false;
      cardNumber = '';
      cardExpiry = '';
      cardCVV = '';
      cardName = '';
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to add card';
    } finally {
      processing = false;
    }
  }

  function handleCardNumberInput(e: Event) {
    const target = e.target as HTMLInputElement;
    cardNumber = formatCardNumber(target.value);
  }

  function handleExpiryInput(e: Event) {
    const target = e.target as HTMLInputElement;
    let value = target.value.replace(/\D/g, '');
    if (value.length >= 2) {
      value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    cardExpiry = value;
  }

  function formatAmountDisplay(amount: number): string {
    return new Intl.NumberFormat('vi-VN').format(amount);
  }
</script>

<div class="payments-component bg-white rounded-lg shadow p-6">
  <h3 class="text-lg font-semibold mb-4">Payment</h3>

  <div class="mb-4">
    <p class="text-3xl font-bold">{formatAmountDisplay(amount)} <span class="text-lg font-normal text-gray-500">{currency}</span></p>
  </div>

  {#if paymentMethods.length === 0 && !showAddCard}
    <div class="text-center py-8 text-gray-500">
      <p>No payment methods available</p>
      <button 
        class="mt-2 text-blue-600 hover:underline"
        onclick={() => showAddCard = true}
      >
        Add a payment method
      </button>
    </div>
  {:else if showAddCard}
    <div class="space-y-4 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
        <input
          type="text"
          value={cardNumber}
          oninput={handleCardNumberInput}
          placeholder="1234 5678 9012 3456"
          maxlength="19"
          class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
        />
        {#if cardNumber && !validateCardNumber(cardNumber).valid}
          <p class="text-red-500 text-sm mt-1">{validateCardNumber(cardNumber).error}</p>
        {/if}
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label>
          <input
            type="text"
            value={cardExpiry}
            oninput={handleExpiryInput}
            placeholder="MM/YY"
            maxlength="5"
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          {#if cardExpiry && !validateExpiry(cardExpiry).valid}
            <p class="text-red-500 text-sm mt-1">{validateExpiry(cardExpiry).error}</p>
          {/if}
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
          <input
            type="text"
            bind:value={cardCVV}
            placeholder={detectedCardType === 'amex' ? '1234' : '123'}
            maxlength={detectedCardType === 'amex' ? 4 : 3}
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          {#if cardCVV && !validateCVV(cardCVV, detectedCardType || undefined).valid}
            <p class="text-red-500 text-sm mt-1">{validateCVV(cardCVV, detectedCardType || undefined).error}</p>
          {/if}
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name on Card</label>
        <input
          type="text"
          bind:value={cardName}
          placeholder="John Doe"
          class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500"
        />
      </div>

      {#if error}
        <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
          {error}
        </div>
      {/if}

      <div class="flex gap-3">
        <button
          class="flex-1 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300"
          onclick={() => showAddCard = false}
        >
          Cancel
        </button>
        <button
          class="flex-1 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
          onclick={handleAddCard}
          disabled={processing || !cardNumber || !cardExpiry || !cardCVV}
        >
          {#if processing}
            Processing...
          {:else}
            Add Card
          {/if}
        </button>
      </div>
    </div>
  {:else}
    <div class="space-y-3 mb-6">
      {#each paymentMethods as method}
        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {selectedMethodId === method.id ? 'border-blue-500 bg-blue-50' : ''}">
          <input 
            type="radio" 
            name="paymentMethod" 
            value={method.id}
            bind:group={selectedMethodId}
            class="w-4 h-4 text-blue-600"
          />
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="text-xl">{method.type === 'card' ? '💳' : '🏦'}</span>
              <span class="font-medium capitalize">{method.cardType || method.type}</span>
              {#if method.isDefault}
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Default</span>
              {/if}
            </div>
            <p class="text-sm text-gray-600">•••• {method.last4 || ''}</p>
          </div>
        </label>
      {/each}

      <button
        class="w-full p-3 border border-dashed rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-500 transition-colors"
        onclick={() => showAddCard = true}
      >
        + Add new payment method
      </button>
    </div>

    {#if error}
      <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm">
        {error}
      </div>
    {/if}

    <button 
      class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      onclick={handlePayment}
      disabled={processing || !selectedMethodId}
    >
      {#if processing}
        <span class="inline-block animate-spin mr-2">⟳</span>
        Processing...
      {:else}
        Pay {formatAmountDisplay(amount)} {currency}
      {/if}
    </button>
  {/if}
</div>