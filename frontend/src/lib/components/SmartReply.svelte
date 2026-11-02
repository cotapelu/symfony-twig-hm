<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  export let message = '';
  export let conversationId = '';
  export let isVisible = false;

  const dispatch = createEventDispatcher();
  
  interface Suggestion {
    text: string;
    confidence?: number;
  }
  
  let suggestions: Suggestion[] = [];
  let isLoading = false;
  let error: string | null = null;

  let debounceTimer: ReturnType<typeof setTimeout>;

  $: if (message && message.length > 10 && conversationId) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      fetchSuggestions();
    }, 500);
  }

  async function fetchSuggestions() {
    if (!message || !conversationId) return;

    isLoading = true;
    error = null;

    try {
      const response = await fetch(`/api/ai/smart-replies`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({
          message: message,
          conversationId: conversationId,
          count: 3
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to fetch suggestions');
      }

      const data = await response.json();
      suggestions = data.suggestions || [];
    } catch (err) {
      error = err instanceof Error ? err.message : 'Failed to fetch suggestions';
      console.error('Smart reply error:', err);
    } finally {
      isLoading = false;
    }
  }

  function selectSuggestion(suggestion: Suggestion) {
    dispatch('select', { suggestion });
    suggestions = [];
  }

  function dismissSuggestions() {
    suggestions = [];
    dispatch('dismiss');
  }
</script>

{#if isVisible && (suggestions.length > 0 || isLoading)}
  <div class="smart-reply-container">
    <div class="smart-reply-header">
      <span class="smart-reply-title">💡 Smart Suggestions</span>
      <button class="dismiss-btn" onclick={dismissSuggestions}>✕</button>
    </div>

    {#if isLoading}
      <div class="loading-state">
        <div class="loading-spinner"></div>
        <span>Generating suggestions...</span>
      </div>
    {:else if error}
      <div class="error-state">
        <span>⚠️ {error}</span>
      </div>
    {:else}
      <div class="suggestions-grid">
        {#each suggestions as suggestion, index}
          <button
            class="suggestion-btn"
            onclick={() => selectSuggestion(suggestion)}
            style="animation-delay: {index * 0.1}s"
          >
            <span class="suggestion-text">{suggestion.text}</span>
            {#if suggestion.confidence}
              <span class="confidence-indicator" class:confidence-high={suggestion.confidence > 0.8}>
                {Math.round(suggestion.confidence * 100)}%
              </span>
            {/if}
          </button>
        {/each}
      </div>
    {/if}
  </div>
{/if}

<style>
  .smart-reply-container {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    margin-bottom: 0.5rem;
    z-index: 10;
    animation: slideUp 0.2s ease-out;
  }

  .smart-reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem 0.5rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .smart-reply-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
  }

  .dismiss-btn {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
  }

  .dismiss-btn:hover {
    background: #f3f4f6;
    color: #6b7280;
  }

  .loading-state,
  .error-state {
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #6b7280;
  }

  .loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  .error-state {
    color: #ef4444;
  }

  .suggestions-grid {
    padding: 0.5rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
  }

  .suggestion-btn {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    opacity: 0;
    animation: fadeInUp 0.3s ease-out forwards;
  }

  .suggestion-btn:hover {
    background: #f1f5f9;
    border-color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
  }

  .suggestion-text {
    display: block;
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.4;
    margin-bottom: 0.25rem;
  }

  .confidence-indicator {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    background: #fef3c7;
    color: #d97706;
  }

  .confidence-high {
    background: #d1fae5;
    color: #065f46;
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .smart-reply-container {
      background: #1f2937;
      border-color: #374151;
    }

    .smart-reply-header {
      border-color: #374151;
    }

    .smart-reply-title {
      color: #f9fafb;
    }

    .dismiss-btn:hover {
      background: #374151;
      color: #d1d5db;
    }

    .loading-state,
    .error-state {
      color: #d1d5db;
    }

    .suggestion-btn {
      background: #374151;
      border-color: #4b5563;
      color: #f9fafb;
    }

    .suggestion-btn:hover {
      background: #4b5563;
    }

    .suggestion-text {
      color: #f9fafb;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .suggestions-grid {
      grid-template-columns: 1fr;
    }

    .suggestion-btn {
      padding: 0.625rem;
    }
  }
</style>
