<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  interface BreadcrumbItem {
    label: string;
    href?: string;
    disabled?: boolean;
    type?: 'collapsed';
    icon?: string;
  }

  export let items: BreadcrumbItem[] = [];
  export let separator = '/';
  export let maxItems = 5;
  export let size = 'md';

  const dispatch = createEventDispatcher();

  $: visibleItems = getVisibleItems(items, maxItems);

  function getVisibleItems(allItems: BreadcrumbItem[], max: number): BreadcrumbItem[] {
    if (allItems.length <= max) return allItems;

    const firstItem = allItems[0];
    const lastItems = allItems.slice(-max + 2);
    const collapsedCount = allItems.length - max + 1;

    return [
      firstItem,
      { label: `+${collapsedCount}`, type: 'collapsed' },
      ...lastItems
    ];
  }

  function handleItemClick(item: BreadcrumbItem, index: number): void {
    if (item.disabled || item.type === 'collapsed') return;
    dispatch('select', { item, index });
  }
</script>

<nav aria-label="Breadcrumb navigation">
  <ol class="breadcrumb breadcrumb-{size}">
    {#each visibleItems as item, index}
      <li class="breadcrumb-item" class:disabled={item.disabled}>
        {#if index > 0}
          <span class="breadcrumb-separator" aria-hidden="true">{separator}</span>
        {/if}

        {#if item.type === 'collapsed'}
          <span class="breadcrumb-collapsed" aria-hidden="true">
            {item.label}
          </span>
        {:else if item.href}
          <a
            href={item.href}
            class="breadcrumb-link"
            aria-current={index === visibleItems.length - 1 ? 'page' : undefined}
            onclick={(e) => { e.preventDefault(); handleItemClick(item, index); }}
          >
            {#if item.icon}
              <span class="breadcrumb-icon">{item.icon}</span>
            {/if}
            <span>{item.label}</span>
          </a>
        {:else}
          <button
            type="button"
            class="breadcrumb-button"
            aria-current={index === visibleItems.length - 1 ? 'page' : undefined}
            disabled={item.disabled}
            onclick={() => handleItemClick(item, index)}
          >
            {#if item.icon}
              <span class="breadcrumb-icon">{item.icon}</span>
            {/if}
            <span>{item.label}</span>
          </button>
        {/if}
      </li>
    {/each}
  </ol>
</nav>

<style>
  .breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    list-style: none;
    margin: 0;
    padding: 0;
    font-size: 0.875rem;
  }

  .breadcrumb-item {
    display: flex;
    align-items: center;
  }

  .breadcrumb-separator {
    margin: 0 0.5rem;
    color: var(--text-secondary, #6b7280);
    user-select: none;
  }

  .breadcrumb-link,
  .breadcrumb-button {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    background: none;
    border: none;
    color: var(--text-secondary, #6b7280);
    text-decoration: none;
    cursor: pointer;
    transition: color 0.2s ease;
    font: inherit;
  }

  .breadcrumb-link:hover,
  .breadcrumb-button:hover:not(:disabled) {
    color: var(--text-primary, #111827);
    background: var(--gray-50, #f9fafb);
  }

  .breadcrumb-link:focus,
  .breadcrumb-button:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .breadcrumb-link[aria-current="page"],
  .breadcrumb-button[aria-current="page"] {
    color: var(--text-primary, #111827);
    font-weight: 500;
    cursor: default;
  }

  .breadcrumb-link[aria-current="page"]:hover,
  .breadcrumb-button[aria-current="page"]:hover {
    background: none;
    color: var(--text-primary, #111827);
  }

  .breadcrumb-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .breadcrumb-collapsed {
    padding: 0.25rem 0.5rem;
    color: var(--text-secondary, #6b7280);
    background: var(--gray-100, #f3f4f6);
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .breadcrumb-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
  }

  /* Sizes */
  .breadcrumb-sm {
    font-size: 0.8125rem;
  }

  .breadcrumb-sm .breadcrumb-icon {
    font-size: 0.75rem;
  }

  .breadcrumb-lg {
    font-size: 1rem;
  }

  .breadcrumb-lg .breadcrumb-icon {
    font-size: 1rem;
  }

  /* Responsive */
  @media (max-width: 640px) {
    .breadcrumb {
      font-size: 0.8125rem;
    }

    .breadcrumb-separator {
      margin: 0 0.25rem;
    }

    .breadcrumb-link,
    .breadcrumb-button {
      padding: 0.125rem 0.25rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .breadcrumb-link,
    .breadcrumb-button {
      border: 1px solid transparent;
    }

    .breadcrumb-link:hover,
    .breadcrumb-button:hover:not(:disabled) {
      border-color: currentColor;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .breadcrumb-link,
    .breadcrumb-button {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .breadcrumb-separator {
      color: var(--gray-500, #6b7280);
    }

    .breadcrumb-link,
    .breadcrumb-button {
      color: var(--gray-400, #9ca3af);
    }

    .breadcrumb-link:hover,
    .breadcrumb-button:hover:not(:disabled) {
      color: var(--gray-100, #f3f4f6);
      background: var(--gray-800, #1f2937);
    }

    .breadcrumb-link[aria-current="page"],
    .breadcrumb-button[aria-current="page"] {
      color: var(--gray-100, #f3f4f6);
    }

    .breadcrumb-collapsed {
      background: var(--gray-800, #1f2937);
      color: var(--gray-400, #9ca3af);
    }
  }
</style>