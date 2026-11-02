<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade } from 'svelte/transition';
  import { Button, Typography } from '$lib/components';

  /** @type {any} */
  export let message = null;
  /** @type {boolean} */
  export let showTranslation = false;
  /** @type {string} */
  export let translatedText = '';
  /** @type {boolean} */
  export let isTranslating = false;
  /** @type {any} */
  export let onTranslate = null;

  const dispatch = createEventDispatcher();

  function handleTranslate() {
    dispatch('translate', { message });
    onTranslate?.(message);
  }

  function toggleTranslation() {
    showTranslation = !showTranslation;
  }

  /** @param {string} code */
  function getLanguageName(code) {
    const languages = {
      'en': 'English',
      'es': 'Spanish',
      'fr': 'French',
      'de': 'German',
      'it': 'Italian',
      'pt': 'Portuguese',
      'ru': 'Russian',
      'ja': 'Japanese',
      'ko': 'Korean',
      'zh': 'Chinese',
      'vi': 'Vietnamese',
      'ar': 'Arabic',
      'hi': 'Hindi'
    };
    return languages[code] || code.toUpperCase();
  }
</script>

{#if message}
  <div class="message-translation">
    <!-- Translate Button -->
     {#if !showTranslation && !isTranslating}
        <Button
          className="translate-btn"
          onclick={handleTranslate}
          disabled={isTranslating}
          aria-label="Translate message"
        >
          🌐 Translate
        </Button>
      {:else if isTranslating}
        <div class="translating" in:fade>
          <div class="loading-spinner"></div>
          <Typography variant="caption">Translating...</Typography>
        </div>
      {/if}

    <!-- Translation Display -->
     {#if showTranslation && translatedText}
        <div class="translation-display" in:fade>
          <div class="translation-header">
            <Typography variant="caption" className="translation-label">
              Translated from {getLanguageName(message.detectedLanguage || 'auto')}
            </Typography>
            <Button
              className="close-translation"
              onclick={toggleTranslation}
              aria-label="Hide translation"
            >
              ×
            </Button>
          </div>
          <Typography className="translated-text">
           {translatedText}
         </Typography>
       </div>
     {/if}
  </div>
{/if}

<style>
  .message-translation {
    margin-top: 0.25rem;
  }

  .translate-btn {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 255, 0.4);
    border-radius: 12px;
    padding: 0.25rem 0.75rem;
    color: #00ffff;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .translate-btn:hover:not(:disabled) {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 6px rgba(0, 255, 255, 0.3);
  }

  .translate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .translating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .loading-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid rgba(0, 255, 255, 0.3);
    border-top: 2px solid #00ffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .translating small {
    color: #00ffff;
    font-size: 0.75rem;
  }

  .translation-display {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: rgba(20, 20, 20, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 8px;
    border-left: 3px solid #00ffff;
  }

  .translation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .translation-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
    font-weight: 500;
  }

  .close-translation {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0;
    width: 1.2rem;
    height: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
  }

  .close-translation:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
  }

  .translated-text {
    color: #ffffff;
    line-height: 1.5;
    font-size: 0.95rem;
    background: rgba(0, 255, 255, 0.05);
    padding: 0.5rem;
    border-radius: 4px;
    border: 1px solid rgba(0, 255, 255, 0.2);
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .translate-btn {
      font-size: 0.7rem;
      padding: 0.2rem 0.6rem;
    }

    .translation-display {
      padding: 0.5rem;
    }

    .translated-text {
      font-size: 0.9rem;
      padding: 0.4rem;
    }
  }
</style>