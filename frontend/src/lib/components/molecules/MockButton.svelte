<script lang="ts">
  export let variant = 'primary';
  export let size = 'md';
  export let type = 'button';
  export let disabled = false;
  export let loading = false;
  export let onclick;
  export let children;

  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  function handleClick(event: MouseEvent) {
    if (disabled || loading) return;
    if (onclick) onclick(event);
    dispatch('click', event);
  }
</script>

<button
  class="btn btn-{variant} btn-{size}"
  type={type}
  disabled={disabled || loading}
  onclick={handleClick}
  data-testid="mock-button-{variant}-{size}"
  data-variant={variant}
  data-size={size}
>
  {#if loading}
    <span class="spinner" aria-hidden="true"></span>
    <span class="sr-only">Loading...</span>
  {:else}
    {@render children?.()}
  {/if}
</button>

<style>
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s;
    cursor: pointer;
    border: 1px solid transparent;
  }

  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
  }

  .btn-secondary {
    background: #e5e7eb;
    color: #1f2937;
    border-color: #d1d5db;
  }

  .btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
  }

  .btn-md {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>