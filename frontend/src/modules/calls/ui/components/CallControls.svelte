<script lang="ts">
  import { callsStore } from '../../state/callsStore';

  $: isMuted = $callsStore.isMuted;
  $: isVideoOn = $callsStore.isVideoOn;
  $: isScreenSharing = $callsStore.isScreenSharing;
  $: isRecording = $callsStore.isRecording;
  $: currentCall = $callsStore.currentCall;

  function formatDuration(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }
</script>

<div class="call-controls">
  <!-- Timer -->
  <div class="text-center mb-4">
    <span class="text-cyan-400 font-mono text-lg font-bold">
      {currentCall?.duration ? formatDuration(currentCall.duration) : '00:00'}
    </span>
    {#if isRecording}
      <span class="ml-2 text-red-500 animate-pulse">● REC</span>
    {/if}
  </div>

  <!-- Control Buttons -->
  <div class="flex items-center justify-center gap-4 flex-wrap">
    <!-- Mute -->
    <button 
      class="w-14 h-14 rounded-full flex items-center justify-center transition-all
        {isMuted ? 'bg-red-500 text-white' : 'bg-gray-700 text-white hover:bg-gray-600'}"
      onclick={() => callsStore.toggleMute()}
      aria-label={isMuted ? 'Unmute' : 'Mute'}
    >
      {#if isMuted}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
      {:else}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
        </svg>
      {/if}
    </button>

    <!-- Video -->
    <button 
      class="w-14 h-14 rounded-full flex items-center justify-center transition-all
        {isVideoOn ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-red-500 text-white'}"
      onclick={() => callsStore.toggleVideo()}
      aria-label={isVideoOn ? 'Turn off video' : 'Turn on video'}
    >
      {#if isVideoOn}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      {:else}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
      {/if}
    </button>

    <!-- Screen Share -->
    <button 
      class="w-14 h-14 rounded-full flex items-center justify-center transition-all
        {isScreenSharing ? 'bg-cyan-500 text-black' : 'bg-gray-700 text-white hover:bg-gray-600'}"
      onclick={() => callsStore.toggleScreenShare()}
      aria-label={isScreenSharing ? 'Stop sharing' : 'Share screen'}
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    </button>

    <!-- Record -->
    <button 
      class="w-14 h-14 rounded-full flex items-center justify-center transition-all
        {isRecording ? 'bg-red-500 text-white animate-pulse' : 'bg-gray-700 text-white hover:bg-gray-600'}"
      onclick={() => callsStore.toggleRecording()}
      aria-label={isRecording ? 'Stop recording' : 'Record'}
    >
      {#if isRecording}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <rect x="6" y="6" width="12" height="12" rx="2" stroke-width="2" />
        </svg>
      {:else}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="6" stroke-width="2" />
        </svg>
      {/if}
    </button>

    <!-- End Call -->
    <button 
      class="w-14 h-14 rounded-full bg-red-600 text-white hover:bg-red-700 flex items-center justify-center transition-all"
      onclick={() => callsStore.endCall()}
      aria-label="End call"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z" />
      </svg>
    </button>
  </div>
</div>