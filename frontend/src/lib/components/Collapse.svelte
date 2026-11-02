<script lang="ts">
  export let accordion = false;
  // `ghost` is declared first so `bordered` can default against it: a ghost
  // collapse is borderless by design (see .collapse-ghost { border: none }),
  // which also avoids .collapse-bordered re-adding a border in high-contrast mode.
  export let ghost = false;
  export let bordered = !ghost;
  export let expandIconPosition: 'left' | 'right' = 'left';
  export let activeKey: string[] = [];
  export let defaultActiveKey: string[] = [];
  export let className = '';

  import { createEventDispatcher } from 'svelte';
  import { onMount, setContext } from 'svelte';

  const dispatch = createEventDispatcher();

  interface Panel {
    key: string;
  }

  let panels: Panel[] = [];
  let currentActiveKey = [...(activeKey.length ? activeKey : defaultActiveKey)];

  $: activeKey, updateActiveKey();
  $: currentActiveKey, dispatch('change', { activeKey: currentActiveKey });

  function updateActiveKey() {
    if (activeKey.length) {
      currentActiveKey = [...activeKey];
    }
  }

  function registerPanel(panel: Panel) {
    panels = [...panels, panel];
    return () => {
      panels = panels.filter(p => p !== panel);
    };
  }

  function togglePanel(key: string) {
    if (accordion) {
      currentActiveKey = currentActiveKey.includes(key) ? [] : [key];
    } else {
      if (currentActiveKey.includes(key)) {
        currentActiveKey = currentActiveKey.filter(k => k !== key);
      } else {
        currentActiveKey = [...currentActiveKey, key];
      }
    }
  }

  onMount(() => {
  });

  $: classes = [
    'collapse',
    bordered ? 'collapse-bordered' : '',
    ghost ? 'collapse-ghost' : '',
    `collapse-icon-${expandIconPosition}`,
    className
  ].filter(Boolean).join(' ');

  const collapseContext = {
    registerPanel,
    togglePanel,
    isActive: (key: string) => currentActiveKey.includes(key),
    accordion,
    expandIconPosition
  };

  setContext('collapse-context', collapseContext);
</script>

<div class={classes}>
  <slot />
</div>

<style>
  .collapse {
    border-radius: 6px;
    background: var(--bg-primary, white);
  }

  .collapse-bordered {
    border: 1px solid var(--border-primary, #d1d5db);
  }

  .collapse-ghost {
    background: transparent;
    border: none;
  }

  .collapse-ghost .collapse-item {
    border: none;
    background: transparent;
  }

  .collapse-ghost .collapse-item:last-child {
    border-radius: 0;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .collapse {
      border-radius: 0;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .collapse-bordered {
      border-width: 2px;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .collapse {
      background: var(--gray-800, #1f2937);
    }

    .collapse-bordered {
      border-color: var(--gray-600, #4b5563);
    }
  }
</style>