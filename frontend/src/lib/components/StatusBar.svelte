<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let visible: boolean = true;
  export let backgroundColor: string = 'var(--primary-500, #3b82f6)';
  export let textColor: string = 'white';
  export let height: number = 24;
  export let translucent: boolean = false;
  export let className: string = '';

  $: classes = [
    'status-bar',
    translucent ? 'status-bar-translucent' : '',
    className
  ].filter(Boolean).join(' ');

  $: style = `
    background-color: ${backgroundColor};
    color: ${textColor};
    height: ${height}px;
    ${visible ? '' : 'display: none;'}
  `;

  function updateStatusBar() {
    // For mobile apps, this would update native status bar
    // For web, we can use meta theme-color
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.setAttribute('content', backgroundColor);
    } else {
      const meta = document.createElement('meta');
      meta.name = 'theme-color';
      meta.content = backgroundColor;
      document.head.appendChild(meta);
    }

    dispatch('update', { backgroundColor, textColor, height });
  }

  $: if (backgroundColor) {
    updateStatusBar();
  }
</script>

{#if visible}
  <div class={classes} {style}>
    <slot />
  </div>
{/if}

<style>
  .status-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0 1rem;
    box-sizing: border-box;
    transition: all 0.3s ease;
  }

  .status-bar-translucent {
    background-color: rgba(59, 130, 246, 0.8);
    backdrop-filter: blur(10px);
  }

  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    .status-bar {
      background-color: var(--primary-600-dark, #2563eb);
    }

    .status-bar-translucent {
      background-color: rgba(37, 99, 235, 0.8);
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .status-bar {
      border-bottom: 1px solid var(--text-primary, #111827);
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .status-bar {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .status-bar {
      padding: 0 0.5rem;
      font-size: 0.6875rem;
    }
  }
</style>