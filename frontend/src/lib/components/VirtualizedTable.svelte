<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, tick } from 'svelte';

  const dispatch = createEventDispatcher();

  export let items: any[] = [];
  export let itemHeight: number = 50;
  export let containerHeight: number = 400;
  export let overscan: number = 5;
  export let className: string = '';

  let container: HTMLElement;
  let scrollTop = 0;
  let visibleRange = { start: 0, end: 0 };

  $: classes = [
    'virtualized-table',
    className
  ].filter(Boolean).join(' ');

  $: totalHeight = items.length * itemHeight;
  $: visibleStart = Math.floor(scrollTop / itemHeight);
  $: visibleEnd = Math.min(
    items.length - 1,
    Math.ceil((scrollTop + containerHeight) / itemHeight)
  );

  $: visibleRange = {
    start: Math.max(0, visibleStart - overscan),
    end: Math.min(items.length - 1, visibleEnd + overscan)
  };

  $: visibleItems = items.slice(visibleRange.start, visibleRange.end + 1);
  $: offsetY = visibleRange.start * itemHeight;

  function handleScroll(event: Event) {
    const target = event.target as HTMLElement;
    scrollTop = target.scrollTop;
    dispatch('scroll', { scrollTop });
  }

  function scrollToIndex(index: number) {
    if (container) {
      container.scrollTop = index * itemHeight;
    }
  }

  function scrollToTop() {
    scrollToIndex(0);
  }

  function scrollToBottom() {
    scrollToIndex(items.length - 1);
  }

  onMount(async () => {
    await tick();
    if (container) {
      container.addEventListener('scroll', handleScroll, { passive: true });
    }
  });

  $: if (container) {
    // Update visible range when items change
    visibleRange = {
      start: Math.max(0, visibleStart - overscan),
      end: Math.min(items.length - 1, visibleEnd + overscan)
    };
  }
</script>

<div
  bind:this={container}
  class={classes}
  style="height: {containerHeight}px; overflow-y: auto;"
>
  <div
    class="virtualized-table-inner"
    style="height: {totalHeight}px; position: relative;"
  >
    <div
      class="virtualized-table-content"
      style="transform: translateY({offsetY}px);"
    >
      {#each visibleItems as item, index (item.id || visibleRange.start + index)}
        <div
          class="virtualized-table-row"
          style="height: {itemHeight}px;"
        >
          <slot {item} index={visibleRange.start + index}>
            <div class="virtualized-table-cell">
              {item}
            </div>
          </slot>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .virtualized-table {
    width: 100%;
    position: relative;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    overflow: hidden;
  }

  .virtualized-table-inner {
    width: 100%;
  }

  .virtualized-table-content {
    width: 100%;
  }

  .virtualized-table-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border-color, #d1d5db);
    transition: background-color 0.2s ease;
  }

  .virtualized-table-row:last-child {
    border-bottom: none;
  }

  .virtualized-table-row:hover {
    background: var(--gray-50, #f9fafb);
  }

  .virtualized-table-cell {
    padding: 0.75rem 1rem;
    flex: 1;
    color: var(--text-primary, #111827);
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .virtualized-table {
      border-color: var(--border-color-dark, #4b5563);
    }

    .virtualized-table-row {
      border-color: var(--border-color-dark, #4b5563);
    }

    .virtualized-table-row:hover {
      background: var(--gray-700-dark, #374151);
    }

    .virtualized-table-cell {
      color: var(--text-primary-dark, #f9fafb);
    }
  }

  /* Accessibility */
  .virtualized-table:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .virtualized-table-row {
      transition: none;
    }
  }
</style>