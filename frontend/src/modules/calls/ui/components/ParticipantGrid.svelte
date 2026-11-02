<script lang="ts">
  import type { CallParticipant } from '../../contracts/callTypes';

  export let participants: CallParticipant[] = [];
  export let localParticipant: CallParticipant | null = null;

  function getGridClass(count: number): string {
    if (count <= 1) return 'grid-cols-1';
    if (count <= 2) return 'grid-cols-2';
    if (count <= 4) return 'grid-cols-2';
    if (count <= 6) return 'grid-cols-3';
    return 'grid-cols-4';
  }

  $: totalParticipants = localParticipant 
    ? [...participants, localParticipant] 
    : participants;
</script>

<div class="participant-grid grid {getGridClass(totalParticipants.length)} gap-4 p-4 h-full">
  {#each totalParticipants as participant}
    <div class="participant-card relative bg-gray-900 rounded-2xl overflow-hidden aspect-video">
      <!-- Video Placeholder -->
      <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900">
        {#if participant.isVideoOn}
          <video 
            class="w-full h-full object-cover"
            autoplay 
            muted={participant.id === localParticipant?.id}
          ></video>
        {:else}
          <div class="w-24 h-24 rounded-full bg-gradient-to-br from-cyan-400 to-purple-500 flex items-center justify-center text-3xl font-bold text-white">
            {participant.name.charAt(0).toUpperCase()}
          </div>
        {/if}
      </div>

      <!-- Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

      <!-- Name Tag -->
      <div class="absolute bottom-4 left-4 flex items-center gap-2">
        <span class="text-white font-medium">{participant.name}</span>
        {#if participant.isHost}
          <span class="bg-yellow-500 text-black text-xs px-2 py-0.5 rounded-full font-bold">Host</span>
        {/if}
        {#if participant.id === localParticipant?.id}
          <span class="bg-gray-600 text-white text-xs px-2 py-0.5 rounded-full">You</span>
        {/if}
      </div>

      <!-- Status Icons -->
      <div class="absolute top-4 right-4 flex gap-2">
        {#if participant.isMuted}
          <div class="w-8 h-8 rounded-full bg-red-500/80 flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          </div>
        {/if}
        {#if participant.isScreenSharing}
          <div class="w-8 h-8 rounded-full bg-cyan-500/80 flex items-center justify-center">
            <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
        {/if}
        {#if participant.isSpeaking}
          <div class="w-8 h-8 rounded-full bg-green-500/80 flex items-center justify-center animate-pulse">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
          </div>
        {/if}
      </div>
    </div>
  {/each}
</div>