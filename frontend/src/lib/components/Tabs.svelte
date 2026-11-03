<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  interface Tab {
    label: string;
    // Optional stable id: index-based consumers may omit it (added additively,
    // Cycle 23) so both activeTab={1} and activeTab={'overview'} work.
    id?: string;
    disabled?: boolean;
    icon?: string;
    badge?: string;
    content?: string;
  }

  export let tabs: Tab[] = [];
  export let activeTab: string | number = 0;

  // Resolve the active tab to an index (id-based consumers keep their own
  // representation; selectTab writes back tab.id when defined, else the index).
  function resolveTabIndex(value: string | number, tabList: Tab[]): number {
    if (typeof value === 'number') return value;
    const index = tabList.findIndex((t) => t.id === value);
    return index >= 0 ? index : 0;
  }
  export let size = 'md';
  export let variant = 'default';
  export let fullWidth = false;
  export let disabled = false;

  const dispatch = createEventDispatcher();

  $: activeIndex = resolveTabIndex(activeTab, tabs);
  $: activeTabData = tabs[activeIndex];

  function selectTab(value: string | number): void {
    const index = resolveTabIndex(value, tabs);
    if (!disabled && !tabs[index]?.disabled) {
      activeTab = tabs[index]?.id ?? index;
      dispatch('change', { index, tab: tabs[index] });
    }
  }

  function handleKeydown(event: KeyboardEvent, _index: number): void {
    if (disabled) return;

    switch (event.key) {
      case 'ArrowLeft':
        event.preventDefault();
        {
          const prevIndex = activeIndex > 0 ? activeIndex - 1 : tabs.length - 1;
          selectTab(prevIndex);
        }
        break;
      case 'ArrowRight':
        event.preventDefault();
        {
          const nextIndex = activeIndex < tabs.length - 1 ? activeIndex + 1 : 0;
          selectTab(nextIndex);
        }
        break;
      case 'Home':
        event.preventDefault();
        selectTab(0);
        break;
      case 'End':
        event.preventDefault();
        selectTab(tabs.length - 1);
        break;
    }
  }
</script>

<div class="tabs" class:fullWidth={fullWidth}>
  <div
    class="tabs-list"
    role="tablist"
    aria-orientation="horizontal"
  >
    {#each tabs as tab, index}
      <button
        class="tab {`tab-${size} tab-${variant}`}"
        class:active={index === activeIndex}
        class:disabled={disabled || tab.disabled}
        role="tab"
        aria-selected={index === activeIndex}
        aria-controls={`tabpanel-${index}`}
        id={`tab-${index}`}
        tabindex={index === activeIndex ? 0 : -1}
        disabled={disabled || tab.disabled}
        onclick={() => selectTab(index)}
        onkeydown={(e) => handleKeydown(e, index)}
      >
        {#if tab.icon}
          <span class="tab-icon">{tab.icon}</span>
        {/if}
        <span class="tab-label">{tab.label}</span>
        {#if tab.badge}
          <span class="tab-badge">{tab.badge}</span>
        {/if}
      </button>
    {/each}
  </div>

  <div class="tabs-content">
    {#each tabs as tab, index}
      <div
        class="tab-panel"
        class:active={index === activeIndex}
        role="tabpanel"
        aria-labelledby={`tab-${index}`}
        id={`tabpanel-${index}`}
        tabindex="0"
      >
        {#if index === activeIndex}
          <slot {tab} {index}>
            {tab.content || tab.label}
          </slot>
        {/if}
      </div>
    {/each}
  </div>
</div>

<style>
  .tabs {
    display: flex;
    flex-direction: column;
  }

  .tabs-list {
    display: flex;
    border-bottom: 1px solid var(--border-primary, #e5e7eb);
    background: var(--bg-primary, white);
  }

  /* svelte-ignore css_unused_selector */
  .tabs.full-width .tabs-list {
    justify-content: space-between;
  }

  .tab {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .tab:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: -2px;
  }

  .tab:hover:not(.disabled) {
    color: var(--text-primary, #111827);
    background: var(--gray-50, #f9fafb);
  }

  .tab.active {
    color: var(--primary-600, #2563eb);
  }

  .tab.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Variants */
  .tab-underline.active {
    border-bottom-color: var(--primary-500, #3b82f6);
    color: var(--primary-600, #2563eb);
  }

  .tab-underline.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-500, #3b82f6);
  }

  .tab-pills {
    border-radius: 6px 6px 0 0;
    margin-bottom: -1px;
  }

  .tab-pills.active {
    background: var(--bg-primary, white);
    border-color: var(--border-primary, #e5e7eb);
    border-bottom-color: var(--bg-primary, white);
  }

  .tab-pills:not(.active) {
    border-bottom-color: transparent;
  }

  /* Sizes */
  .tab-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
  }

  .tab-lg {
    padding: 1rem 1.25rem;
    font-size: 1rem;
  }

  /* Elements */
  .tab-icon {
    font-size: 1rem;
  }

  .tab-badge {
    background: var(--primary-500, #3b82f6);
    color: white;
    font-size: 0.625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
    min-width: 1rem;
    text-align: center;
  }

  /* Content */
  .tabs-content {
    flex: 1;
    padding: 1.5rem 0;
  }

  .tab-panel {
    display: none;
  }

  .tab-panel.active {
    display: block;
  }

  /* Mobile */
  @media (max-width: 768px) {
    .tabs-list {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .tab {
      flex-shrink: 0;
    }

    /* svelte-ignore css_unused_selector */
    .tabs.full-width .tabs-list {
      justify-content: flex-start;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .tab {
      border-bottom-width: 3px;
    }

    .tab-underline.active::after {
      height: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .tab {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .tabs-list {
      border-bottom-color: var(--gray-700, #374151);
      background: var(--gray-900, #111827);
    }

    .tab {
      color: var(--gray-400, #9ca3af);
    }

    .tab:hover:not(.disabled) {
      color: var(--gray-100, #f3f4f6);
      background: var(--gray-800, #1f2937);
    }

    .tab.active {
      color: var(--primary-400, #60a5fa);
    }

    .tab-underline.active {
      border-bottom-color: var(--primary-400, #60a5fa);
    }

    .tab-underline.active::after {
      background: var(--primary-400, #60a5fa);
    }

    .tab-pills.active {
      background: var(--gray-900, #111827);
      border-color: var(--gray-700, #374151);
    }
  }
</style>