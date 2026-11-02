<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  export let refreshing = false;
  export let threshold = 80;
  export let disabled = false;

  const dispatch = createEventDispatcher();

  let container: HTMLElement;
  let startY = 0;
  let currentY = 0;
  let isPulling = false;
  let canRefresh = false;
  let touchId: number | null = null;

  function handleTouchStart(event: TouchEvent) {
    if (disabled || refreshing) return;

    const touch = event.touches[0];
    if (!touch) return;

    if (container.scrollTop > 0) return;

    startY = touch.clientY;
    currentY = startY;
    touchId = touch.identifier;
    isPulling = true;
  }

  function handleTouchMove(event: TouchEvent) {
    if (!isPulling || disabled || refreshing) return;

    const touch = Array.from(event.touches).find(t => t.identifier === touchId);
    if (!touch) return;

    currentY = touch.clientY;
    const pullDistance = Math.max(0, currentY - startY);

    if (pullDistance > 0) {
      event.preventDefault();
      canRefresh = pullDistance >= threshold;
    }
  }

  function handleTouchEnd() {
    if (!isPulling) return;

    isPulling = false;

    if (canRefresh && !refreshing) {
      dispatch('refresh');
    }

    startY = 0;
    currentY = 0;
    canRefresh = false;
    touchId = null;
  }

  onMount(() => {
    if (container) {
      container.addEventListener('touchstart', handleTouchStart, { passive: false });
      container.addEventListener('touchmove', handleTouchMove, { passive: false });
      container.addEventListener('touchend', handleTouchEnd);
    }
  });

  onDestroy(() => {
    if (container) {
      container.removeEventListener('touchstart', handleTouchStart);
      container.removeEventListener('touchmove', handleTouchMove);
      container.removeEventListener('touchend', handleTouchEnd);
    }
  });

  $: pullDistance = isPulling ? Math.max(0, currentY - startY) : 0;
  $: progress = Math.min(pullDistance / threshold, 1);
  $: transform = pullDistance > 0 ? `translateY(${pullDistance * 0.5}px)` : '';
</script>

<div
  bind:this={container}
  class="pull-to-refresh"
  style="transform: {transform};"
>
  {#if pullDistance > 0}
    <div class="pull-indicator" class:ready={canRefresh}>
      <div class="pull-icon" style="transform: rotate({progress * 180}deg);">
        {#if refreshing}
          ⟳
        {:else}
          ↓
        {/if}
      </div>
      <span class="pull-text">
        {#if refreshing}
          Refreshing...
        {:else if canRefresh}
          Release to refresh
        {:else}
          Pull down to refresh
        {/if}
      </span>
    </div>
  {/if}

  <slot />
</div>

<style>
  .pull-to-refresh {
    position: relative;
    transition: transform 0.2s ease;
    min-height: 100%;
  }

  .pull-indicator {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
    transition: color 0.2s ease;
  }

  .pull-indicator.ready {
    color: var(--primary-500, #3b82f6);
  }

  .pull-icon {
    font-size: 1.5rem;
    transition: transform 0.2s ease;
  }

  .pull-text {
    font-weight: 500;
  }

  /* Mobile haptic feedback simulation */
  @media (max-width: 768px) {
    .pull-indicator {
      top: -70px;
    }
  }
</style>