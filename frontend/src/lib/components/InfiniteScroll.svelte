<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  const dispatch = createEventDispatcher();

  export let loading: boolean = false;
  export let hasMore: boolean = true;
  export let threshold: number = 100;
  export let className: string = '';

  let container: HTMLElement;
  let sentinel: HTMLElement;
  let observer: IntersectionObserver | null = null;

  $: classes = [
    'infinite-scroll',
    className
  ].filter(Boolean).join(' ');

  function setupObserver() {
    if (!container || observer) return;

    observer = new IntersectionObserver(
      (entries) => {
        const target = entries[0];
        if (target.isIntersecting && hasMore && !loading) {
          dispatch('loadMore');
        }
      },
      {
        root: container,
        rootMargin: `${threshold}px`,
        threshold: 0.1
      }
    );

    if (sentinel) {
      observer.observe(sentinel);
    }
  }

  function cleanupObserver() {
    if (observer) {
      observer.disconnect();
      observer = null;
    }
  }

  onMount(() => {
    setupObserver();
  });

  onDestroy(() => {
    cleanupObserver();
  });

  $: if (container && sentinel) {
    setupObserver();
  }
</script>

<div bind:this={container} class={classes}>
  <slot />

  {#if hasMore}
    <div bind:this={sentinel} class="infinite-scroll-sentinel">
      {#if loading}
        <div class="infinite-scroll-loading">
          <div class="loading-spinner"></div>
          <span class="loading-text">Loading more...</span>
        </div>
      {:else}
        <div class="infinite-scroll-placeholder"></div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .infinite-scroll {
    width: 100%;
    position: relative;
  }

  .infinite-scroll-sentinel {
    width: 100%;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .infinite-scroll-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    color: var(--text-secondary, #6b7280);
  }

  .loading-spinner {
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid var(--gray-300, #d1d5db);
    border-top: 2px solid var(--primary-500, #3b82f6);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  .loading-text {
    font-size: 0.875rem;
  }

  .infinite-scroll-placeholder {
    height: 20px;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .infinite-scroll-loading {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .loading-spinner {
      border-color: var(--gray-600-dark, #4b5563);
      border-top-color: var(--primary-500, #3b82f6);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .loading-spinner {
      animation: none;
    }
  }
</style>