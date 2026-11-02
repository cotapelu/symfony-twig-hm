<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';
  import { fade } from 'svelte/transition';
  import { slide } from 'svelte/transition';

  const dispatch = createEventDispatcher();

  interface Props {
    open?: boolean;
    title?: string;
    height?: string;
    maxHeight?: string;
    snapPoints?: string[];
    enableSwipe?: boolean;
    closeOnBackdrop?: boolean;
    closeOnEscape?: boolean;
    className?: string;
    // Callback props for Svelte 5 compatibility
    onOpen?: () => void;
    onClose?: () => void;
    onSnap?: (detail: { height: string }) => void;
  }

  let {
    open = false,
    title = '',
    height = '50vh',
    maxHeight = '90vh',
    snapPoints = ['25vh', '50vh', '90vh'],
    enableSwipe = true,
    closeOnBackdrop = true,
    closeOnEscape = true,
    className = '',
    onOpen,
    onClose,
    onSnap
  }: Props = $props();

  let sheetElement: HTMLElement;
  let overlayElement: HTMLElement;
  let contentElement: HTMLElement;
  let isDragging = $state(false);
  let startY = $state(0);
  let currentY = $state(0);
  let currentHeight = $state(height);
  let mounted = $state(false);

  $effect(() => {
    if (!mounted) {
      currentHeight = height;
      mounted = true;
    }
  });

  function openSheet() {
    open = true;
    currentHeight = height;
    dispatch('open');
    onOpen?.();
  }

  function closeSheet() {
    open = false;
    dispatch('close');
    onClose?.();
    document.body.style.overflow = '';
  }

  function handleBackdropClick(event: MouseEvent) {
    if (closeOnBackdrop && event.target === overlayElement) {
      closeSheet();
    }
  }

  function handleKeyDown(event: KeyboardEvent) {
    if (closeOnEscape && event.key === 'Escape') {
      closeSheet();
    }
  }

  function handleTouchStart(event: TouchEvent) {
    if (!enableSwipe) return;
    isDragging = true;
    startY = event.touches[0].clientY;
    currentY = startY;
  }

  function handleTouchMove(event: TouchEvent) {
    if (!isDragging || !enableSwipe) return;
    event.preventDefault();
    currentY = event.touches[0].clientY;

    const deltaY = currentY - startY;
    const newHeight = Math.max(0, parseFloat(currentHeight) - deltaY);

    if (contentElement) {
      contentElement.style.transform = `translateY(${Math.max(0, deltaY)}px)`;
    }
  }

  function handleTouchEnd() {
    if (!isDragging || !enableSwipe) return;
    isDragging = false;

    const deltaY = currentY - startY;
    const velocity = Math.abs(deltaY) / 100; // rough velocity

    if (contentElement) {
      contentElement.style.transform = '';
    }

    // Snap to closest point
    const currentHeightValue = parseFloat(currentHeight);
    const newHeightValue = Math.max(0, currentHeightValue - deltaY);

    let closestPoint = snapPoints[0];
    let minDistance = Math.abs(newHeightValue - parseFloat(snapPoints[0]));

    for (const point of snapPoints) {
      const distance = Math.abs(newHeightValue - parseFloat(point));
      if (distance < minDistance) {
        minDistance = distance;
        closestPoint = point;
      }
    }

    // If dragged down significantly or high velocity, close
    if (deltaY > 100 || velocity > 0.5) {
      closeSheet();
    } else {
      currentHeight = closestPoint;
      dispatch('snap', { height: closestPoint });
    }
  }

  function snapToHeight(height: string) {
    currentHeight = height;
    dispatch('snap', { height });
    onSnap?.({ height });
  }

  $effect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  });

  onMount(() => {
    if (open) {
      openSheet();
    }
  });

  onDestroy(() => {
    document.body.style.overflow = '';
  });

  let classes = $derived([
    'bottom-sheet',
    className
  ].filter(Boolean).join(' '));

  let sheetStyle = $derived(`height: ${currentHeight}; max-height: ${maxHeight};`);
</script>

<svelte:window onkeydown={handleKeyDown} />

{#if open}
  <!-- svelte-ignore a11y_click_events_have_key_events -->
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <div
    bind:this={overlayElement}
    class="bottom-sheet-overlay"
    role="presentation"
    onclick={handleBackdropClick}
    onkeydown={(e) => { if (e.key === 'Escape') closeSheet(); }}
  >
    <div
      bind:this={sheetElement}
      class={classes}
      style={sheetStyle}
    >
      {#if snapPoints.length > 1}
        <div class="bottom-sheet-handle">
          <div class="bottom-sheet-indicators">
            {#each snapPoints as point}
              <button
                class="bottom-sheet-indicator"
                class:active={currentHeight === point}
                onclick={() => snapToHeight(point)}
                aria-label="Snap to {point}"
              ></button>
            {/each}
          </div>
        </div>
      {/if}

      <div
        bind:this={contentElement}
        class="bottom-sheet-content"
        ontouchstart={handleTouchStart}
        ontouchmove={handleTouchMove}
        ontouchend={handleTouchEnd}
      >
        {#if title}
          <div class="bottom-sheet-header">
            <h3 class="bottom-sheet-title">{title}</h3>
            <button
              class="bottom-sheet-close"
              onclick={closeSheet}
              aria-label="Close"
            >
              ×
            </button>
          </div>
        {/if}

        <div class="bottom-sheet-body">
          <slot />
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .bottom-sheet-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
  }

  .bottom-sheet {
    width: 100%;
    max-width: 500px;
    background: var(--background, #ffffff);
    border-radius: 1rem 1rem 0 0;
    box-shadow: 0 -10px 25px -5px rgba(0, 0, 0, 0.1), 0 -10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transform: translateY(0);
    transition: transform 0.3s ease;
  }

  .bottom-sheet-handle {
    display: flex;
    justify-content: center;
    padding: 0.75rem;
    background: var(--background, #ffffff);
    border-bottom: 1px solid var(--border-color, #d1d5db);
  }

  .bottom-sheet-indicators {
    display: flex;
    gap: 0.5rem;
  }

  .bottom-sheet-indicator {
    width: 2rem;
    height: 0.25rem;
    background: var(--gray-300, #d1d5db);
    border: none;
    border-radius: 0.125rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
  }

  .bottom-sheet-indicator:hover {
    background: var(--gray-400, #9ca3af);
  }

  .bottom-sheet-indicator.active {
    background: var(--primary-500, #3b82f6);
  }

  .bottom-sheet-content {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .bottom-sheet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color, #d1d5db);
  }

  .bottom-sheet-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
  }

  .bottom-sheet-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
  }

  .bottom-sheet-close:hover {
    background: var(--gray-100, #f3f4f6);
    color: var(--text-primary, #111827);
  }

  .bottom-sheet-body {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .bottom-sheet {
      background: var(--background-dark, #1f2937);
    }

    .bottom-sheet-handle {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .bottom-sheet-indicator {
      background: var(--gray-600-dark, #4b5563);
    }

    .bottom-sheet-indicator:hover {
      background: var(--gray-500-dark, #6b7280);
    }

    .bottom-sheet-header {
      border-color: var(--border-color-dark, #4b5563);
    }

    .bottom-sheet-title {
      color: var(--text-primary-dark, #f9fafb);
    }

    .bottom-sheet-close {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .bottom-sheet-close:hover {
      background: var(--gray-700-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }
  }

  /* Accessibility */
  .bottom-sheet:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .bottom-sheet-close:focus,
  .bottom-sheet-indicator:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .bottom-sheet-overlay,
    .bottom-sheet {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .bottom-sheet {
      max-width: 100vw;
      border-radius: 0;
    }

    .bottom-sheet-handle {
      cursor: grab;
    }

    .bottom-sheet-handle:active {
      cursor: grabbing;
    }
  }
</style>