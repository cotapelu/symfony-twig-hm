<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let placeholder = 'Click to speak...';
  export const language = 'en-US';
  export const continuous = false;
  export const interimResults = true;
  export const maxAlternatives = 1;

  let isListening = false;
  let transcript = '';
  let interimTranscript = '';
  let recognition: any;

  const dispatch = createEventDispatcher();

  function initRecognition() {
    // For production, implement Web Speech API
    // This is a placeholder
    console.log('Voice recognition initialized');
  }

  function startListening() {
    if (!recognition) initRecognition();
    isListening = true;
    dispatch('start');
    // Simulate recognition
    setTimeout(() => {
      transcript = 'Hello world';
      dispatch('result', { transcript, isFinal: true });
      stopListening();
    }, 2000);
  }

  function stopListening() {
    isListening = false;
    dispatch('end');
  }

  function handleClick() {
    if (isListening) {
      stopListening();
    } else {
      startListening();
    }
  }
</script>

<button
  class="voice-search-btn"
  class:listening={isListening}
  onclick={handleClick}
  aria-label={isListening ? 'Stop voice search' : 'Start voice search'}
>
  <span class="icon">{isListening ? '🎤' : '🔊'}</span>
  <span class="text">
    {isListening ? 'Listening...' : placeholder}
  </span>
  {#if transcript}
    <div class="transcript">{transcript}</div>
  {/if}
</button>

<style>
  .voice-search-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 8px;
    color: var(--text-primary, #374151);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
  }

  .voice-search-btn:hover {
    border-color: var(--primary-500, #3b82f6);
  }

  .voice-search-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .listening {
    background-color: var(--primary-50, #eff6ff);
    border-color: var(--primary-500, #3b82f6);
    color: var(--primary-700, #1d4ed8);
  }

  .icon {
    font-size: 1.25rem;
  }

  .transcript {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }
</style>