<script lang="ts">
  import { fly, fade } from 'svelte/transition';
  import type { Call } from '../../contracts/callTypes';
  import { callsStore } from '../../state/callsStore';

  $: incomingCall = $callsStore.incomingCall;

  function formatDuration(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }
</script>

{#if incomingCall}
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center" transition:fade={{ duration: 200 }}>
    <div class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl text-center" transition:fly={{ y: 50, duration: 300 }}>
      <!-- Caller Avatar -->
      <div class="relative mb-6">
        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-4xl shadow-lg">
          {incomingCall.participants[0]?.name?.charAt(0).toUpperCase() || '👤'}
        </div>
        <div class="absolute bottom-0 right-1/2 translate-x-8 translate-y-1">
          <span class="animate-ping absolute inline-flex h-4 w-4 rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
        </div>
      </div>

      <!-- Call Info -->
      <h2 class="text-2xl font-bold text-gray-900 mb-2">
        {incomingCall.participants[0]?.name || 'Unknown Caller'}
      </h2>
      <p class="text-gray-500 mb-6">
        {incomingCall.type === 'video' ? '📹 Incoming Video Call' : '📞 Incoming Voice Call'}
      </p>

      <!-- Action Buttons -->
      <div class="flex gap-4 justify-center">
        <button 
          class="flex-1 bg-red-500 hover:bg-red-600 text-white py-4 px-6 rounded-2xl font-semibold transition-colors flex items-center justify-center gap-2"
          onclick={() => callsStore.declineIncomingCall()}
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Decline
        </button>
        <button 
          class="flex-1 bg-green-500 hover:bg-green-600 text-white py-4 px-6 rounded-2xl font-semibold transition-colors flex items-center justify-center gap-2"
          onclick={() => callsStore.acceptIncomingCall()}
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
          Accept
        </button>
      </div>
    </div>
  </div>
{/if}