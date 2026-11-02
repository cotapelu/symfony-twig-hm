<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  interface Props {
    items?: any[];
    actions?: SwipeAction[];
    threshold?: number;
    className?: string;
  }

  interface SwipeAction {
    key: string;
    label: string;
    color: string;
    icon?: string;
    action?: (item: any) => void;
  }

  let {
    items = [],
    actions = [],
    threshold = 50,
    className = ''
  }: Props = $props();

  let container: HTMLElement;
  let isDragging = $state(false);
  let startX = $state(0);
  let currentX = $state(0);
  let itemIndex = $state(-1);
  let openedIndex = $state(-1);

  const classes = $derived([
    'swipeable-list',
    className
  ].filter(Boolean).join(' '));

  function handleTouchStart(event: TouchEvent, index: number) {
    if (actions.length === 0) return;

    isDragging = true;
    startX = event.touches[0].clientX;
    currentX = startX;
    itemIndex = index;
  }

  function handleTouchMove(event: TouchEvent) {
    if (!isDragging) return;

    event.preventDefault();
    currentX = event.touches[0].clientX;

    const deltaX = startX - currentX;
    const itemElement = container.children[itemIndex] as HTMLElement;

    if (deltaX > 0) { // Swipe left
      const translateX = Math.min(deltaX, threshold * actions.length);
      itemElement.style.transform = `translateX(-${translateX}px)`;
    }
  }

  function handleTouchEnd() {
    if (!isDragging) return;

    isDragging = false;
    const deltaX = startX - currentX;
    const itemElement = container.children[itemIndex] as HTMLElement;

    if (deltaX > threshold) {
      // Open actions
      const actionIndex = Math.min(Math.floor(deltaX / threshold), actions.length - 1);
      openedIndex = itemIndex;
      itemElement.style.transform = `translateX(-${threshold * (actionIndex + 1)}px)`;
      dispatch('swipe', { item: items[itemIndex], actionIndex });
    } else {
      // Close
      itemElement.style.transform = '';
      if (openedIndex === itemIndex) {
        openedIndex = -1;
      }
    }

    itemIndex = -1;
  }

  function closeActions() {
    if (openedIndex >= 0) {
      const itemElement = container.children[openedIndex] as HTMLElement;
      itemElement.style.transform = '';
      openedIndex = -1;
    }
  }

  function handleActionClick(action: SwipeAction, item: any, index: number) {
    if (typeof action.action === 'function') {
      action.action(item);
    }
    closeActions();
    dispatch('action', { action: action.key, item, index });
  }

  // Close on outside click
  function handleDocumentClick(event: MouseEvent) {
    if (container && !container.contains(event.target as Node)) {
      closeActions();
    }
  }
</script>

<svelte:window onclick={handleDocumentClick} />

<div bind:this={container} class={classes}>
  {#each items as item, index (item.id || index)}
    <div class="swipeable-item">
      <div
        class="swipeable-content"
        ontouchstart={(e) => handleTouchStart(e, index)}
        ontouchmove={handleTouchMove}
        ontouchend={handleTouchEnd}
      >
        <slot {item} {index}>{item}</slot>
      </div>

      {#if actions.length > 0}
        <div class="swipeable-actions">
          {#each actions as action}
            <button
              class="swipeable-action"
              style="background-color: {action.color}"
              onclick={() => handleActionClick(action, item, index)}
            >
              {#if action.icon}
                <span class="swipeable-action-icon">{action.icon}</span>
              {/if}
              <span class="swipeable-action-label">{action.label}</span>
            </button>
          {/each}
        </div>
      {/if}
    </div>
  {/each}
</div>

<style>
  .swipeable-list {
    width: 100%;
    position: relative;
  }

  .swipeable-item {
    position: relative;
    overflow: hidden;
    background: var(--background, #ffffff);
    border-bottom: 1px solid var(--border-color, #d1d5db);
  }

  .swipeable-item:last-child {
    border-bottom: none;
  }

  .swipeable-content {
    width: 100%;
    touch-action: pan-y;
    transition: transform 0.3s ease;
  }

  .swipeable-actions {
    position: absolute;
    top: 0;
    right: 0;
    height: 100%;
    display: flex;
  }

  .swipeable-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    min-width: 4rem;
    color: white;
    border: none;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.2s ease;
  }

  .swipeable-action:hover {
    opacity: 0.8;
  }

  .swipeable-action-icon {
    font-size: 1rem;
    margin-bottom: 0.25rem;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .swipeable-item {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }
  }

  /* Accessibility */
  .swipeable-content:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .swipeable-action:focus {
    outline: 2px solid white;
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .swipeable-content {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .swipeable-action {
      min-width: 3rem;
      padding: 0.375rem;
      font-size: 0.6875rem;
    }

    .swipeable-action-label {
      display: none;
    }

    .swipeable-action-icon {
      margin-bottom: 0;
    }
  }
</style>