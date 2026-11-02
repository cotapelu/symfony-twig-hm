<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  export let threshold = 80;
  export let disabled = false;
  export let leftActions: Array<{ icon: string; label: string; action: string }> = [];
  export let rightActions: Array<{ icon: string; label: string; action: string }> = [];

  const dispatch = createEventDispatcher();

  let container: HTMLElement;
  let startX = 0;
  let currentX = 0;
  let isSwiping = false;
  let swipeDirection: 'left' | 'right' | null = null;
  let touchId: number | null = null;

  function handleTouchStart(event: TouchEvent) {
    if (disabled) return;

    const touch = event.touches[0];
    if (!touch) return;

    startX = touch.clientX;
    currentX = startX;
    touchId = touch.identifier;
    isSwiping = false;
    swipeDirection = null;
  }

  function handleTouchMove(event: TouchEvent) {
    if (disabled || !touchId) return;

    const touch = Array.from(event.touches).find(t => t.identifier === touchId);
    if (!touch) return;

    currentX = touch.clientX;
    const deltaX = currentX - startX;

    if (Math.abs(deltaX) > 10) {
      isSwiping = true;
      swipeDirection = deltaX > 0 ? 'right' : 'left';

      event.preventDefault();
    }
  }

  function handleTouchEnd() {
    if (!isSwiping || disabled) return;

    const deltaX = currentX - startX;
    const absDeltaX = Math.abs(deltaX);

    if (absDeltaX >= threshold) {
      const action = swipeDirection === 'left' ? rightActions[0] : leftActions[0];
      if (action) {
        dispatch('action', { action: action.action, direction: swipeDirection });
      }
    }

    startX = 0;
    currentX = 0;
    isSwiping = false;
    swipeDirection = null;
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

  $: translateX = isSwiping ? currentX - startX : 0;
  $: showLeftActions = isSwiping && swipeDirection === 'right' && leftActions.length > 0;
  $: showRightActions = isSwiping && swipeDirection === 'left' && rightActions.length > 0;
  $: disabledClass = disabled ? 'disabled' : '';
</script>

<div
  bind:this={container}
  class="swipe-actions"
  class:swiping={isSwiping}
  class:disabled={disabled}
  data-testid="swipe-actions"
  style="transform: translateX({translateX}px);"
>
  <!-- Left actions (swipe right to reveal) -->
  {#if showLeftActions}
    <div class="swipe-actions-left">
      {#each leftActions as action}
        <button
          class="swipe-action-btn"
          onclick={(e) => { e.stopPropagation(); dispatch('action', { action: action.action, direction: 'right' }); }}
          aria-label={action.label}
        >
          <span class="action-icon">{action.icon}</span>
          <span class="action-label">{action.label}</span>
        </button>
      {/each}
    </div>
  {/if}

  <!-- Main content -->
  <div class="swipe-content">
    <slot />
  </div>

  <!-- Right actions (swipe left to reveal) -->
  {#if showRightActions}
    <div class="swipe-actions-right">
      {#each rightActions as action}
        <button
          class="swipe-action-btn"
          onclick={(e) => { e.stopPropagation(); dispatch('action', { action: action.action, direction: 'left' }); }}
          aria-label={action.label}
        >
          <span class="action-icon">{action.icon}</span>
          <span class="action-label">{action.label}</span>
        </button>
      {/each}
    </div>
  {/if}
</div>

<style>
  .swipe-actions {
    position: relative;
    transition: transform 0.2s ease;
    overflow: hidden;
  }

  .swipe-actions.swiping {
    transition: none; /* Disable transition during swipe */
  }

  .swipe-content {
    background: var(--bg-primary, white);
    position: relative;
    z-index: 2;
  }

  .swipe-actions-left,
  .swipe-actions-right {
    position: absolute;
    top: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    z-index: 1;
  }

  .swipe-actions-left {
    left: 0;
    background: linear-gradient(to right, var(--success-500, #10b981), var(--success-400, #34d399));
  }

  .swipe-actions-right {
    right: 0;
    background: linear-gradient(to left, var(--error-500, #ef4444), var(--error-400, #f87171));
  }

  .swipe-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 1rem;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    min-width: 80px;
  }

  .swipe-action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
  }

  .action-icon {
    font-size: 1.25rem;
  }

  .action-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  /* Mobile optimizations */
  @media (max-width: 768px) {
    .swipe-action-btn {
      padding: 0.75rem;
      min-width: 70px;
    }

    .action-icon {
      font-size: 1.125rem;
    }

    .action-label {
      font-size: 0.7rem;
    }
  }
</style>