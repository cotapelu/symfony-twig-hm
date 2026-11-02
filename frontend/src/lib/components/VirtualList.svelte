<script lang="ts">
  import { onMount, tick } from 'svelte';

  interface Props {
    items?: any[];
    itemHeight?: number;
    containerHeight?: number;
    overscan?: number;
  }

  let {
    items = [],
    itemHeight = 60,
    containerHeight = 400,
    overscan = 5
  }: Props = $props();

  let container: HTMLElement;
  let scrollTop = $state(0);
  let visibleStart = $state(0);
  let visibleEnd = $state(0);

  const totalHeight = $derived(items.length * itemHeight);
  const visibleCount = $derived(Math.ceil(containerHeight / itemHeight) + overscan * 2);
  const offsetY = $derived(visibleStart * itemHeight);

  function updateVisibleRange() {
    if (!container) return;

    scrollTop = container.scrollTop;
    visibleStart = Math.max(0, Math.floor(scrollTop / itemHeight) - overscan);
    visibleEnd = Math.min(items.length, visibleStart + visibleCount);
  }

  function handleScroll() {
    updateVisibleRange();
  }

  onMount(() => {
    updateVisibleRange();
  });

  $effect(() => {
    if (items.length) {
      // Use requestAnimationFrame to wait for DOM update
      requestAnimationFrame(updateVisibleRange);
    }
  });
</script>

<div
  bind:this={container}
  class="virtual-list"
  style="height: {containerHeight}px; overflow-y: auto;"
  onscroll={handleScroll}
>
  <div
    class="virtual-list-inner"
    style="height: {totalHeight}px; position: relative;"
  >
    <div
      class="virtual-list-content"
      style="transform: translateY({offsetY}px);"
    >
      {#each items.slice(visibleStart, visibleEnd) as item, index (item.id || index)}
        <div
          class="virtual-list-item"
          style="height: {itemHeight}px;"
        >
          <slot {item} index={visibleStart + index}>
            {item}
          </slot>
        </div>
      {/each}
    </div>
  </div>
</div>

<style>
  .virtual-list {
    position: relative;
    overflow: hidden;
  }

  .virtual-list-inner {
    position: relative;
  }

  .virtual-list-content {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
  }

  .virtual-list-item {
    display: flex;
    align-items: center;
    padding: 0 1rem;
    border-bottom: 1px solid var(--border-light, #f0f0f0);
  }

  .virtual-list-item:hover {
    background: var(--bg-hover, #f9f9f9);
  }

  /* Mobile optimizations */
  @media (max-width: 768px) {
    .virtual-list-item {
      padding: 0 0.75rem;
      min-height: 48px; /* Touch target */
    }
  }
</style>