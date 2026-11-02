<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade, slide } from 'svelte/transition';
  import { Button, Typography } from '$lib/components';

  /** @type {any[]} */
  export let suggestions = [];
  /** @type {boolean} */
  export let isLoading = false;
  /** @type {any} */
  export let onSuggestionSelect = null;

  const dispatch = createEventDispatcher();

  function selectSuggestion(suggestion) {
    dispatch('select', { suggestion });
    onSuggestionSelect?.(suggestion);
  }

  function formatSuggestion(suggestion) {
    // Clean up suggestion text
    return suggestion.replace(/^["']|["']$/g, '').trim();
  }
</script>

{#if isLoading}
  <div class="smart-replies-loading" in:fade>
    <div class="loading-dots">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <small>Generating smart replies...</small>
  </div>
{:else if suggestions.length > 0}
   <div class="smart-replies" in:slide={{ duration: 300 }}>
     <div class="replies-header">
       <Typography variant="caption">💡 Smart replies</Typography>
     </div>
      <div class="replies-list">
        {#each suggestions as suggestion, index}
          <div in:fade={{ delay: index * 100 }}>
            <Button
              class="reply-suggestion"
              onclick={() => selectSuggestion(suggestion)}
            >
              {formatSuggestion(suggestion)}
            </Button>
          </div>
        {/each}
      </div>
   </div>
{/if}

<style>
  .smart-replies {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: rgba(20, 20, 20, 0.9);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 8px;
    max-width: 100%;
  }

  .replies-header {
    margin-bottom: 0.5rem;
  }

  .replies-header small {
    color: #00ffff;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .replies-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .reply-suggestion {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid rgba(0, 255, 255, 0.4);
    border-radius: 16px;
    padding: 0.5rem 1rem;
    color: #00ffff;
    font-size: 0.9rem;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.3s ease;
    max-width: 200px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
  }

  .reply-suggestion:hover {
    background: rgba(0, 255, 255, 0.2);
    border-color: #00ffff;
    box-shadow: 0 0 8px rgba(0, 255, 255, 0.4);
    transform: translateY(-1px);
  }

  .smart-replies-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .loading-dots {
    display: flex;
    gap: 0.25rem;
  }

  .loading-dots span {
    width: 4px;
    height: 4px;
    background: #00ffff;
    border-radius: 50%;
    animation: loading-dot 1.4s infinite ease-in-out;
  }

  .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
  .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

  @keyframes loading-dot {
    0%, 80%, 100% {
      transform: scale(0);
      opacity: 0.5;
    }
    40% {
      transform: scale(1);
      opacity: 1;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .replies-list {
      flex-direction: column;
    }

    .reply-suggestion {
      max-width: none;
      text-align: left;
    }
  }
</style>