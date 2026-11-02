<script lang="ts">
  export let variant: 'vertical' | 'horizontal' = 'vertical';
  export let hasSidebar = true;
  export let sidebarWidth = '250px';
  export let collapsible = false;
  export let collapsed = false;
  export let className = '';

  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  $: classes = [
    'layout',
    `layout-${variant}`,
    hasSidebar ? 'layout-has-sidebar' : '',
    collapsible ? 'layout-collapsible' : '',
    collapsed ? 'layout-collapsed' : '',
    className
  ].filter(Boolean).join(' ');

  function toggleSidebar() {
    collapsed = !collapsed;
    dispatch('toggle', { collapsed });
  }
</script>

<div class={classes} style="--sidebar-width: {sidebarWidth}">
  {#if $$slots.header}
    <header class="layout-header">
      {#if collapsible}
        <button
          class="layout-toggle"
          onclick={toggleSidebar}
          aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          ☰
        </button>
      {/if}
      <slot name="header" />
    </header>
  {/if}

  <div class="layout-main">
    {#if hasSidebar && $$slots.sidebar}
      <aside class="layout-sidebar" class:collapsed>
        <slot name="sidebar" />
      </aside>
    {/if}

    <main class="layout-content">
      <slot />
    </main>
  </div>

  {#if $$slots.footer}
    <footer class="layout-footer">
      <slot name="footer" />
    </footer>
  {/if}
</div>

<style>
  .layout {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
  }

  .layout-main {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  .layout-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-primary, #d1d5db);
    background: var(--bg-secondary, #f9fafb);
    z-index: 10;
  }

  .layout-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    margin-right: 1rem;
    background: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    transition: background 0.2s ease;
  }

  .layout-toggle:hover {
    background: var(--gray-100, #f3f4f6);
  }

  .layout-toggle:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .layout-sidebar {
    width: var(--sidebar-width, 250px);
    flex-shrink: 0;
    overflow-y: auto;
    border-right: 1px solid var(--border-primary, #d1d5db);
    background: var(--bg-secondary, #f9fafb);
    transition: width 0.3s ease;
  }

  .layout-collapsed .layout-sidebar {
    width: 0;
    border-right: none;
  }

  .layout-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
  }

  .layout-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-primary, #d1d5db);
    background: var(--bg-secondary, #f9fafb);
    text-align: center;
  }

  /* Horizontal variant */
  .layout-horizontal .layout-main {
    flex-direction: column;
  }

  .layout-horizontal .layout-sidebar {
    width: 100%;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border-primary, #d1d5db);
  }

  .layout-horizontal .layout-content {
    flex: 1;
  }

  .layout-horizontal.layout-collapsed .layout-sidebar {
    height: 0;
    border-bottom: none;
  }

  /* Mobile */
  @media (max-width: 768px) {
    .layout-sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      z-index: 100;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }

    .layout-has-sidebar .layout-sidebar {
      transform: translateX(0);
    }

    .layout-collapsed .layout-sidebar {
      transform: translateX(-100%);
    }

    .layout-content {
      padding: 0.5rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .layout-header,
    .layout-sidebar,
    .layout-footer {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .layout-sidebar,
    .layout-toggle {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .layout-header,
    .layout-sidebar,
    .layout-footer {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .layout-toggle {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .layout-toggle:hover {
      background: var(--gray-700, #374151);
    }
  }
</style>