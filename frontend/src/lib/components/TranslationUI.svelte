<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let messageText = '';
  export let originalLanguage = 'auto';
  export let targetLanguage = 'en';
  export let showTranslation = false;

  const dispatch = createEventDispatcher();

  let translatedText = '';
  let isTranslating = false;
  let translationError = null;
  let supportedLanguages = [
    { code: 'en', name: 'English' },
    { code: 'vi', name: 'Tiếng Việt' },
    { code: 'es', name: 'Español' },
    { code: 'fr', name: 'Français' },
    { code: 'de', name: 'Deutsch' },
    { code: 'ja', name: '日本語' },
    { code: 'ko', name: '한국어' },
    { code: 'zh', name: '中文' },
    { code: 'pt', name: 'Português' },
    { code: 'it', name: 'Italiano' },
    { code: 'ru', name: 'Русский' },
    { code: 'ar', name: 'العربية' },
    { code: 'hi', name: 'हिन्दी' }
  ];

  async function translateMessage() {
    if (!messageText.trim()) return;

    isTranslating = true;
    translationError = null;

    try {
      const response = await fetch('/api/ai/translate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({
          text: messageText,
          from: originalLanguage,
          to: targetLanguage
        }),
      });

      if (!response.ok) {
        throw new Error('Translation failed');
      }

      const data = await response.json();
      translatedText = data.translatedText;
      showTranslation = true;
      
      dispatch('translated', {
        originalText: messageText,
        translatedText,
        from: originalLanguage,
        to: targetLanguage
      });
    } catch (error) {
      translationError = 'Translation failed';
      console.error('Translation error:', error);
    } finally {
      isTranslating = false;
    }
  }

  function toggleTranslation() {
    if (translatedText && showTranslation) {
      showTranslation = false;
    } else if (translatedText) {
      showTranslation = true;
    } else {
      translateMessage();
    }
  }

  function copyTranslation() {
    if (translatedText) {
      navigator.clipboard.writeText(translatedText).then(() => {
        dispatch('copied', { text: translatedText });
      });
    }
  }

  $: if (targetLanguage !== 'en' && messageText && !translatedText) {
    // Auto-translate when language changes
    translateMessage();
  }
</script>

<div class="translation-ui">
  {#if messageText}
    <div class="translation-controls">
      <select
        class="language-select"
        bind:value={targetLanguage}
        disabled={isTranslating}
      >
        {#each supportedLanguages as lang}
          <option value={lang.code}>{lang.name}</option>
        {/each}
      </select>

      <button
        class="translate-btn"
        onclick={toggleTranslation}
        disabled={isTranslating}
        class:active={showTranslation}
      >
        {#if isTranslating}
          <div class="loading-spinner"></div>
        {:else}
          🌐
        {/if}
        {showTranslation ? 'Hide' : 'Translate'}
      </button>
    </div>

    {#if showTranslation && translatedText}
      <div class="translation-result">
        <div class="translation-header">
          <span class="translation-label">
            Translation ({supportedLanguages.find(l => l.code === targetLanguage)?.name || targetLanguage})
          </span>
          <button class="copy-btn" onclick={copyTranslation} title="Copy translation">
            📋
          </button>
        </div>
        <div class="translated-text">
          {translatedText}
        </div>
      </div>
    {/if}

    {#if translationError}
      <div class="translation-error">
        ⚠️ {translationError}
      </div>
    {/if}
  {/if}
</div>

<style>
  .translation-ui {
    margin-top: 0.5rem;
  }

  .translation-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .language-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    font-size: 0.875rem;
    color: #374151;
    transition: border-color 0.15s ease-in-out;
  }

  .language-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .translate-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
  }

  .translate-btn:hover:not(:disabled) {
    background: #f9fafb;
    border-color: #9ca3af;
  }

  .translate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .translate-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }

  .loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  .translation-result {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f8fafc;
    padding: 0.75rem;
    margin-top: 0.5rem;
  }

  .translation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .translation-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .copy-btn {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
  }

  .copy-btn:hover {
    background: #e5e7eb;
    color: #6b7280;
  }

  .translated-text {
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.5;
  }

  .translation-error {
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.5rem;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .language-select {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .translate-btn {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .translate-btn:hover:not(:disabled) {
      background: #374151;
    }

    .translate-btn.active {
      background: #3b82f6;
    }

    .translation-result {
      background: #374151;
      border-color: #4b5563;
    }

    .translation-label {
      color: #9ca3af;
    }

    .copy-btn:hover {
      background: #4b5563;
      color: #d1d5db;
    }

    .translated-text {
      color: #f9fafb;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .translation-controls {
      flex-direction: column;
      align-items: stretch;
    }

    .language-select,
    .translate-btn {
      width: 100%;
    }
  }
</style>
