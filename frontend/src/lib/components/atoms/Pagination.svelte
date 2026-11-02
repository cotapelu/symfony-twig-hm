<script lang="ts">
  export let current = 1;
  export let total = 0;
  export let pageSize = 10;
  export let showSizeChanger = false;
  export let showQuickJumper = false;
  export let showTotal = true;
  export let size = 'md'; // sm, md, lg
  export let disabled = false;
  export let simple = false;

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  $: totalPages = Math.ceil(total / pageSize);
  $: pages = generatePages(current, totalPages);
  $: startItem = (current - 1) * pageSize + 1;
  $: endItem = Math.min(current * pageSize, total);

  function generatePages(current: number, total: number) {
    const pages: (number | string)[] = [];
    const maxVisible = 7;

    if (total <= maxVisible) {
      for (let i = 1; i <= total; i++) {
        pages.push(i);
      }
    } else {
      pages.push(1);

      if (current > 4) {
        pages.push('...');
      }

      const start = Math.max(2, current - 1);
      const end = Math.min(total - 1, current + 1);

      for (let i = start; i <= end; i++) {
        pages.push(i);
      }

      if (current < total - 3) {
        pages.push('...');
      }

      if (total > 1) {
        pages.push(total);
      }
    }

    return pages;
  }

  function goToPage(page: number) {
    if (page >= 1 && page <= totalPages && page !== current && !disabled) {
      current = page;
      dispatch('change', { current, pageSize });
    }
  }

  function handleSizeChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    const newSize = parseInt(target.value, 10);
    if (newSize !== pageSize) {
      pageSize = newSize;
      current = 1;
      dispatch('showSizeChange', { current, pageSize });
    }
  }

  function handleQuickJump(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const target = event.target as HTMLInputElement;
      const page = parseInt(target.value, 10);
      if (page >= 1 && page <= totalPages) {
        goToPage(page);
        target.value = '';
      }
    }
  }

  $: classes = [
    'pagination',
    `pagination-${size}`,
    simple ? 'pagination-simple' : '',
    disabled ? 'pagination-disabled' : ''
  ].filter(Boolean).join(' ');
</script>

{#if simple}
  <div class={classes} role="navigation" aria-label="Pagination Navigation">
    <button
      type="button"
      class="pagination-prev"
      disabled={current <= 1 || disabled}
      onclick={() => goToPage(current - 1)}
      aria-label="Previous page"
    >
      ‹
    </button>

    <span class="pagination-simple-text">
      {current} / {totalPages}
    </span>

    <button
      type="button"
      class="pagination-next"
      disabled={current >= totalPages || disabled}
      onclick={() => goToPage(current + 1)}
      aria-label="Next page"
    >
      ›
    </button>
  </div>
{:else}
  <div class={classes} role="navigation" aria-label="Pagination Navigation">
    {#if showTotal}
      <span class="pagination-total">
        {startItem}-{endItem} of {total} items
      </span>
    {/if}

    <button
      type="button"
      class="pagination-prev"
      disabled={current <= 1 || disabled}
      onclick={() => goToPage(current - 1)}
      aria-label="Previous page"
    >
      ‹ Previous
    </button>

    <ul class="pagination-list" role="list">
      {#each pages as page}
        {#if page === '...'}
          <li class="pagination-item" role="presentation">
            <span class="pagination-ellipsis">…</span>
          </li>
        {:else}
           <li class="pagination-item" role="presentation">
            <button
              type="button"
              class="pagination-page"
              class:active={page === current}
              disabled={disabled}
              onclick={() => typeof page === 'number' && goToPage(page)}
              aria-label="Page {page}"
              aria-current={page === current ? 'page' : undefined}
            >
              {page}
            </button>
          </li>
        {/if}
      {/each}
    </ul>

    <button
      type="button"
      class="pagination-next"
      disabled={current >= totalPages || disabled}
      onclick={() => goToPage(current + 1)}
      aria-label="Next page"
    >
      Next ›
    </button>

    {#if showSizeChanger}
      <div class="pagination-size-changer">
        <span>Show</span>
        <select
          value={pageSize}
          onchange={handleSizeChange}
          disabled={disabled}
          class="pagination-size-select"
        >
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span>items</span>
      </div>
    {/if}

    {#if showQuickJumper}
      <div class="pagination-quick-jumper">
        <span>Go to</span>
        <input
          type="number"
          min="1"
          max={totalPages}
          onkeydown={handleQuickJump}
          disabled={disabled}
          class="pagination-jumper-input"
          placeholder={current.toString()}
        />
      </div>
    {/if}
  </div>
{/if}

<style>
  .pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .pagination-simple {
    justify-content: center;
  }

  .pagination-disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  .pagination-total {
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
    margin-right: 1rem;
  }

  .pagination-prev,
  .pagination-next {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
  }

  .pagination-prev:hover:not(:disabled),
  .pagination-next:hover:not(:disabled) {
    background: var(--gray-50, #f9fafb);
    border-color: var(--primary-500, #3b82f6);
  }

  .pagination-prev:disabled,
  .pagination-next:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-simple-text {
    font-weight: 500;
    color: var(--text-primary, #111827);
    margin: 0 1rem;
  }

  .pagination-list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 0.25rem;
  }

  .pagination-item {
    display: flex;
  }

  .pagination-page {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
  }

  .pagination-page:hover:not(:disabled) {
    background: var(--gray-50, #f9fafb);
    border-color: var(--primary-500, #3b82f6);
  }

  .pagination-page.active {
    background: var(--primary-500, #3b82f6);
    color: white;
    border-color: var(--primary-500, #3b82f6);
  }

  .pagination-page:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .pagination-ellipsis {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    color: var(--text-secondary, #6b7280);
    user-select: none;
  }

  .pagination-size-changer,
  .pagination-quick-jumper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: 1rem;
  }

  .pagination-size-select,
  .pagination-jumper-input {
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    font-size: 0.875rem;
    width: 4rem;
  }

  .pagination-jumper-input {
    width: 3rem;
  }

  /* Sizes */
  .pagination-sm {
    font-size: 0.8125rem;
  }

  .pagination-sm .pagination-page {
    width: 2rem;
    height: 2rem;
    font-size: 0.8125rem;
  }

  .pagination-lg {
    font-size: 1rem;
  }

  .pagination-lg .pagination-page {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .pagination {
      justify-content: center;
    }

    .pagination-total {
      display: none;
    }

    .pagination-size-changer,
    .pagination-quick-jumper {
      margin-left: 0.5rem;
    }

    .pagination-size-select,
    .pagination-jumper-input {
      width: 3rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .pagination-page,
    .pagination-prev,
    .pagination-next {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .pagination-page,
    .pagination-prev,
    .pagination-next {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .pagination-page,
    .pagination-prev,
    .pagination-next {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
      color: var(--gray-100, #f3f4f6);
    }

    .pagination-page:hover:not(:disabled) {
      background: var(--gray-700, #374151);
    }

    .pagination-page.active {
      background: var(--primary-600, #2563eb);
    }
  }
</style>