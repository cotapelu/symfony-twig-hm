<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let items: any[] = [];
  export let layout: 'list' | 'grid' = 'list';
  export let pagination: boolean = false;
  export let pageSize: number = 10;
  export let infiniteScroll: boolean = false;
  export let loading: boolean = false;
  export let className: string = '';

  let currentPage = 1;
  let container: HTMLElement;
  let isLoadingMore = false;

  $: totalPages = Math.ceil(items.length / pageSize);
  $: paginatedItems = pagination ? items.slice((currentPage - 1) * pageSize, currentPage * pageSize) : items;

  $: classes = [
    'list',
    `list-${layout}`,
    pagination ? 'list-paginated' : '',
    infiniteScroll ? 'list-infinite' : '',
    className
  ].filter(Boolean).join(' ');

  function handlePageChange(page: number) {
    currentPage = page;
    dispatch('pageChange', { page, pageSize });
  }

  async function loadMore() {
    if (isLoadingMore || !infiniteScroll) return;
    isLoadingMore = true;
    dispatch('loadMore', { currentItems: items.length });
    // Simulate loading - in real use, handle in parent
    setTimeout(() => {
      isLoadingMore = false;
    }, 1000);
  }

  function handleScroll() {
    if (!infiniteScroll || !container) return;
    const { scrollTop, scrollHeight, clientHeight } = container;
    if (scrollTop + clientHeight >= scrollHeight - 100) {
      loadMore();
    }
  }
</script>

<div class={classes} bind:this={container} onscroll={handleScroll}>
  {#if layout === 'grid'}
    <div class="list-grid">
      {#each paginatedItems as item, index (item.id || index)}
        <div class="list-item">
          <slot {item} {index}>{item}</slot>
        </div>
      {/each}
    </div>
  {:else}
    <div class="list-container">
      {#each paginatedItems as item, index (item.id || index)}
        <div class="list-item">
          <slot {item} {index}>{item}</slot>
        </div>
      {/each}
    </div>
  {/if}

  {#if loading || isLoadingMore}
    <div class="list-loading">
      <div class="loading-spinner"></div>
      Loading...
    </div>
  {/if}

  {#if pagination && totalPages > 1}
    <div class="list-pagination">
      <button
        type="button"
        disabled={currentPage === 1}
        onclick={() => handlePageChange(currentPage - 1)}
        class="pagination-btn"
      >
        Previous
      </button>

      {#each Array(totalPages) as _, i}
        {@const page = i + 1}
        <button
          type="button"
          class:active={page === currentPage}
          onclick={() => handlePageChange(page)}
          class="pagination-btn"
        >
          {page}
        </button>
      {/each}

      <button
        type="button"
        disabled={currentPage === totalPages}
        onclick={() => handlePageChange(currentPage + 1)}
        class="pagination-btn"
      >
        Next
      </button>
    </div>
  {/if}
</div>

<style>
  .list {
    width: 100%;
    position: relative;
  }

  .list-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }

  .list-item {
    padding: 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--background, #ffffff);
    transition: box-shadow 0.2s ease;
  }

  .list-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .list-infinite {
    max-height: 400px;
    overflow-y: auto;
  }

  .list-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    color: var(--text-secondary, #6b7280);
  }

  .loading-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--gray-300, #d1d5db);
    border-top: 2px solid var(--primary-500, #3b82f6);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 0.5rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .list-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 1rem;
  }

  .pagination-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    background: var(--background, #ffffff);
    color: var(--text-primary, #111827);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .pagination-btn:hover:not(:disabled) {
    background: var(--gray-50, #f9fafb);
  }

  .pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-btn.active {
    background: var(--primary-500, #3b82f6);
    color: white;
    border-color: var(--primary-500, #3b82f6);
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .list-item {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .pagination-btn {
      background: var(--background-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
      border-color: var(--border-color-dark, #4b5563);
    }

    .pagination-btn:hover:not(:disabled) {
      background: var(--gray-700-dark, #374151);
    }
  }

  /* Accessibility */
  .list-item:focus-within {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .pagination-btn:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .list-item, .pagination-btn, .loading-spinner {
      transition: none;
      animation: none;
    }
  }
</style>