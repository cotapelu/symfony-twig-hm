<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { setContext, getContext } from 'svelte';

  interface MenuItem {
    key?: string;
    label: string;
    icon?: string;
    disabled?: boolean;
    children?: MenuItem[];
    badge?: string | number;
  }

  interface MenuContext {
    level: number;
    selectedKeys: string[];
    openKeys: string[];
    onSelect: (key: string, item: MenuItem) => void;
    onOpenChange: (key: string, open: boolean) => void;
  }

  export let items: MenuItem[] = [];
  export let mode: 'vertical' | 'horizontal' | 'inline' = 'vertical';
  export let theme: 'light' | 'dark' = 'light';
  export let selectedKeys: string[] = [];
  export let openKeys: string[] = [];
  export let triggerSubMenuAction: 'hover' | 'click' = 'hover';
  export let disabled = false;

  const dispatch = createEventDispatcher();

  const menuContext = getContext<MenuContext>('menu-context') || {
    level: 0,
    selectedKeys,
    openKeys,
    onSelect: (key: string, item: MenuItem) => {
      selectedKeys = [key];
      dispatch('select', { key, item, selectedKeys });
    },
    onOpenChange: (key: string, open: boolean) => {
      if (open) {
        openKeys = [...openKeys, key];
      } else {
        openKeys = openKeys.filter(k => k !== key);
      }
      dispatch('openChange', { key, open, openKeys });
    }
  };

  setContext('menu-context', {
    ...menuContext,
    level: menuContext.level + 1
  });

  $: classes = [
    'menu',
    `menu-${mode}`,
    `menu-${theme}`,
    disabled ? 'menu-disabled' : ''
  ].filter(Boolean).join(' ');

  function handleItemClick(item: { key?: string; label: string; icon?: string; disabled?: boolean; children?: Array<{ key?: string; label: string; icon?: string; disabled?: boolean }> }, key: string) {
    if (item.disabled || disabled) return;

    if (item.children && item.children.length > 0) {
      const isOpen = openKeys.includes(key);
      menuContext.onOpenChange(key, !isOpen);
    } else {
      menuContext.onSelect(key, item);
    }
  }

  function handleSubMenuToggle(key: string, open: boolean) {
    menuContext.onOpenChange(key, open);
  }
</script>

<ul class={classes} role="menu" aria-orientation={mode === 'horizontal' ? 'horizontal' : 'vertical'}>
  {#each items as item, index}
    {@const key = item.key || `item-${index}`}
    {@const isSelected = selectedKeys.includes(key)}
    {@const isOpen = openKeys.includes(key)}
    {@const hasChildren = item.children && item.children.length > 0}

    <li class="menu-item-wrapper" role="none">
      {#if hasChildren}
        <!-- Submenu -->
        <div
          class="menu-submenu"
          class:open={isOpen}
          role="menuitem"
          aria-haspopup="true"
          aria-expanded={isOpen}
          aria-disabled={item.disabled || disabled}
          tabindex={item.disabled || disabled ? -1 : 0}
          onclick={() => handleItemClick(item, key)}
          onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleItemClick(item, key); e.preventDefault(); } }}
          onmouseenter={() => triggerSubMenuAction === 'hover' && handleSubMenuToggle(key, true)}
          onmouseleave={() => triggerSubMenuAction === 'hover' && handleSubMenuToggle(key, false)}
        >
          <div class="menu-submenu-title">
            {#if item.icon}
              <span class="menu-item-icon">{item.icon}</span>
            {/if}
            <span class="menu-item-label">{item.label}</span>
            <span class="menu-submenu-arrow" class:open={isOpen}>
              {mode === 'horizontal' ? (isOpen ? '▲' : '▼') : (isOpen ? '▶' : '▶')}
            </span>
          </div>

          {#if isOpen}
            <svelte:self
              items={item.children}
              mode="vertical"
              theme={theme}
              selectedKeys={selectedKeys}
              openKeys={openKeys}
              triggerSubMenuAction={triggerSubMenuAction}
              disabled={disabled || item.disabled}
            />
          {/if}
        </div>
      {:else}
        <!-- Menu item -->
        <div
          class="menu-item"
          class:selected={isSelected}
          class:disabled={item.disabled || disabled}
          role="menuitem"
          aria-disabled={item.disabled || disabled}
          tabindex={item.disabled || disabled ? -1 : 0}
          onclick={() => handleItemClick(item, key)}
          onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { handleItemClick(item, key); e.preventDefault(); } }}
        >
          {#if item.icon}
            <span class="menu-item-icon">{item.icon}</span>
          {/if}
          <span class="menu-item-label">{item.label}</span>
          {#if item.badge}
            <span class="menu-item-badge">{item.badge}</span>
          {/if}
        </div>
      {/if}
    </li>
  {/each}
</ul>

<style>
  /* svelte-ignore css_unused_selector */
  .menu-horizontal .menu-submenu.open > ul {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    min-width: 200px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }

  /* svelte-ignore css_unused_selector */
  .menu .menu {
    border: none;
    border-radius: 0;
    box-shadow: none;
  }

  /* svelte-ignore css_unused_selector */
  .menu .menu .menu-item,
  .menu .menu .menu-submenu {
    padding-left: 2rem;
  }

  /* svelte-ignore css_unused_selector */
  .menu .menu .menu .menu-item,
  .menu .menu .menu .menu-submenu {
    padding-left: 3rem;
  }

  .menu-vertical {
    min-width: 200px;
  }

  .menu-horizontal {
    display: flex;
    border: none;
    background: transparent;
  }

  .menu-horizontal .menu-item-wrapper {
    flex: 1;
  }

  .menu-inline {
    border: none;
    background: transparent;
  }

  .menu-dark {
    background: var(--gray-800, #1f2937);
    border-color: var(--gray-600, #4b5563);
    color: var(--gray-100, #f3f4f6);
  }

  .menu-disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  /* Menu items */
  .menu-item,
  .menu-submenu {
    position: relative;
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
  }

  .menu-item:hover:not(.disabled),
  .menu-submenu:hover:not(.disabled) {
    background: var(--gray-50, #f9fafb);
  }

  .menu-item.selected {
    background: var(--primary-50, #eff6ff);
    color: var(--primary-600, #2563eb);
    font-weight: 500;
  }

  .menu-item.disabled,
  .menu-submenu.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .menu-dark .menu-item:hover:not(.disabled),
  .menu-dark .menu-submenu:hover:not(.disabled) {
    background: var(--gray-700, #374151);
  }

  .menu-dark .menu-item.selected {
    background: var(--primary-900, #1e3a8a);
    color: var(--primary-200, #bfdbfe);
  }

  /* Submenu */
  .menu-submenu {
    flex-direction: column;
    align-items: stretch;
  }

  .menu-submenu-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
  }

  .menu-submenu-arrow {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
  }

  .menu-submenu.open .menu-submenu-arrow {
    transform: rotate(90deg);
  }

  .menu-horizontal .menu-submenu {
    position: relative;
  }

  /* svelte-ignore css_unused_selector */
  .menu-horizontal .menu-submenu.open > ul {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    min-width: 200px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }

  /* Icons and badges */
  .menu-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
  }

  .menu-item-label {
    flex: 1;
  }

  .menu-item-badge {
    background: var(--primary-500, #3b82f6);
    color: white;
    font-size: 0.625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
    min-width: 1rem;
    text-align: center;
  }

  /* svelte-ignore css_unused_selector */
  /* Nested menus */
  .menu .menu {
    border: none;
    border-radius: 0;
    box-shadow: none;
  }

  /* svelte-ignore css_unused_selector */
  .menu .menu .menu-item,
  .menu .menu .menu-submenu {
    padding-left: 2rem;
  }

  /* svelte-ignore css_unused_selector */
  .menu .menu .menu .menu-item,
  .menu .menu .menu .menu-submenu {
    padding-left: 3rem;
  }

  /* Focus styles */
  .menu-item:focus,
  .menu-submenu:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: -2px;
  }

  /* Mobile */
  @media (max-width: 768px) {
    /* svelte-ignore css_unused_selector */
    .menu-horizontal {
      flex-direction: column;
    }

    /* svelte-ignore css_unused_selector */
    .menu-horizontal .menu-submenu.open > ul {
      position: static;
      box-shadow: none;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .menu {
      border-width: 2px;
    }

    .menu-item,
    .menu-submenu {
      border: 1px solid transparent;
    }

    .menu-item:hover:not(.disabled),
    .menu-submenu:hover:not(.disabled) {
      border-color: currentColor;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .menu-item,
    .menu-submenu,
    .menu-submenu-arrow {
      transition: none;
    }
  }

  /* Print styles */
  @media print {
    .menu {
      border: 1px solid #000;
    }
  }
</style>