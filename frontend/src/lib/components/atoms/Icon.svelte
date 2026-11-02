<script lang="ts">
  export let name = '';
  export let size = 'md'; // xs, sm, md, lg, xl
  export let color = 'currentColor';
  export let spin = false;
  export let rotate = 0; // degrees
  export let flip: 'horizontal' | 'vertical' | 'both' | null = null;

  $: iconPath = getIconPath(name);
  $: classes = [
    'icon',
    `icon-${size}`,
    spin ? 'icon-spin' : '',
    flip ? `icon-flip-${flip}` : ''
  ].filter(Boolean).join(' ');

  $: style = `color: ${color}; transform: rotate(${rotate}deg);`;

  function getIconPath(iconName: string): string {
    // Simple icon set - expand with actual SVG paths
    const icons: Record<string, string> = {
      // Navigation
      home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
      chevronLeft: 'M15 19l-7-7 7-7',
      chevronRight: 'M9 5l7 7-7 7',
      chevronUp: 'M5 15l7-7 7 7',
      chevronDown: 'M19 9l-7 7-7-7',

      // Actions
      plus: 'M12 4v16m8-8H4',
      minus: 'M20 12H4',
      edit: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
      trash: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
      search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',

      // Status
      check: 'M5 13l4 4L19 7',
      x: 'M6 18L18 6M6 6l12 12',
      alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
      info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',

      // User
      user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
      users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',

      // Communication
      mail: 'M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
      phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
      message: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',

      // Media
      play: 'M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l.707.707A1 1 0 0012.414 11H15m-3 7.5A9.5 9.5 0 1121 12.5 9.5 9.5 0 0112 2.5z',
      pause: 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z',
      volume: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
      image: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',

      // Files
      file: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h2a2 2 0 012 2v2H8V5z',

      // UI
      menu: 'M4 6h16M4 12h16M4 18h16',
      more: 'M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z',
      settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',

      // Loading
      loader: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
    };

    return icons[iconName] || '';
  }
</script>

{#if iconPath}
  <svg
    class={classes}
    style={style}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    role="img"
    aria-hidden="true"
  >
    <path d={iconPath} />
  </svg>
{:else}
  <span class={classes} style={style} aria-hidden="true">?</span>
{/if}

<style>
  .icon {
    display: inline-block;
    flex-shrink: 0;
    vertical-align: middle;
  }

  /* Sizes */
  .icon-xs {
    width: 0.75rem;
    height: 0.75rem;
  }

  .icon-sm {
    width: 1rem;
    height: 1rem;
  }

  .icon-md {
    width: 1.25rem;
    height: 1.25rem;
  }

  .icon-lg {
    width: 1.5rem;
    height: 1.5rem;
  }

  .icon-xl {
    width: 2rem;
    height: 2rem;
  }

  /* Animations */
  .icon-spin {
    animation: spin 1s linear infinite;
  }

  .icon-flip-horizontal {
    transform: scaleX(-1);
  }

  .icon-flip-vertical {
    transform: scaleY(-1);
  }

  .icon-flip-both {
    transform: scaleX(-1) scaleY(-1);
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* High contrast support */
  @media (prefers-contrast: high) {
    .icon {
      stroke-width: 3;
    }
  }
</style>