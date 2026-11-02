<script lang="ts">
  import { callsStore } from '$lib/../stores';
  import { onMount } from 'svelte';

  onMount(() => {
    callsStore.loadActiveCalls();
  });

  async function initiateCall(type: 'audio' | 'video' = 'audio') {
    try {
      await callsStore.initiateCall({ type: 'video' as const, participantId: '' });
    } catch (error) {
      console.error('Failed to initiate call:', error);
    }
  }

  async function joinCall(callId) {
    try {
      await callsStore.joinCall(callId);
    } catch (error) {
      console.error('Failed to join call:', error);
    }
  }

  async function leaveCall() {
    try {
      await callsStore.leaveCall();
    } catch (error) {
      console.error('Failed to leave call:', error);
    }
  }
</script>

<div class="calls-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Calls</h1>
        <button 
          class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center space-x-2"
          onclick={() => initiateCall('video')}
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 10.894V12a1 1 0 01-1.447.894L15 14m0 0l-4.553 2.276A1 1 0 0111.447.894V12a1 1 0 001-1.447-.894L15 14z" />
          </svg>
          <span>New Video Call</span>
        </button>
      </div>

      {#if $callsStore.loading}
        <div class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500"></div>
        </div>
      {:else}
        <div class="space-y-4">
          {#if $callsStore.currentCall}
            <div class="bg-gray-900 text-white rounded-lg p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">In Call</h2>
                <button 
                  class="px-4 py-2 bg-red-500 rounded hover:bg-red-600"
                  onclick={leaveCall}
                >
                  End Call
                </button>
              </div>
              
              <div class="flex justify-center space-x-4 py-8">
                <button class="w-12 h-12 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 017 7m0 0a7 7 0 01-7 7m0 0a7 7 0 017 7m0 0a7 7 0 01-7 7" />
                  </svg>
                </button>
                <button class="w-12 h-12 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 017 7m0 0a7 7 0 01-7 7m0 0a7 7 0 017 7m0 0a7 7 0 01-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          {/if}

          <h2 class="text-lg font-semibold mb-4 {$callsStore.currentCall ? 'mt-8' : ''}">
            Active Calls
          </h2>
          
          {#each $callsStore.activeCalls as call}
            <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
              <div>
                <p class="font-medium">{call.title || call.name || 'Call'}</p>
                <p class="text-sm text-gray-600">
                  {call.type || 'video'} • {call.participantCount || 0} participants
                </p>
              </div>
              <button 
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                onclick={() => joinCall(call.id)}
              >
                Join
              </button>
            </div>
          {/each}
          
          {#if $callsStore.activeCalls.length === 0 && !$callsStore.currentCall}
            <div class="text-center py-12 text-gray-600">
              No active calls
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .calls-page {
    min-height: 100vh;
  }
</style>
