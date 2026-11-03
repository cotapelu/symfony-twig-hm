<script lang="ts">
  export let name = 'home';
  export let size: 'xs' | 'sm' | 'md' | 'lg' | 'xl' = 'md';
  export let color = '';
  export let spin = false;
  export let rotate = 0;
  export let flip: 'horizontal' | 'vertical' | 'none' = 'none';
  export let className = '';

  const icons: Record<string, string> = {
    home: 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
    user: 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2',
    settings: 'M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-2 2h-.44a2 2 0 0 1-2-2v-.44a2 2 0 0 1-.73-1l-.15-.1a2 2 0 0 0-.73-2.73l-.38-.22a2 2 0 0 1 0-2.73l.08-.15a2 2 0 0 1 1-1.72v-.51a2 2 0 0 1 1-1.73l.15-.1a2 2 0 0 0 .73-2.73l.38-.22a2 2 0 0 1 2 0l.22.38a2 2 0 0 0 2.73.73l.15-.1a2 2 0 0 1 1.73-1l.1-.08a2 2 0 0 1 1.73 1l.38.22a2 2 0 0 1 .73 2.73l-.08.15a2 2 0 0 0 .73 2.73l.22-.38a2 2 0 0 1 2 0l.15.1a2 2 0 0 1 1 1.73v.51a2 2 0 0 1-1 1.73l-.15.1a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73-.73l.15-.1a2 2 0 0 1 1.73-1l.1.08a2 2 0 0 1 1.73-1l.38-.22a2 2 0 0 1 .73-2.73l-.08-.15a2 2 0 0 0-.73-2.73l-.22-.38a2 2 0 0 1 0-2.73l.08-.15a2 2 0 0 1-1-1.73v-.51a2 2 0 0 1 1-1.73l.15-.1a2 2 0 0 0 .73-2.73l-.38-.22a2 2 0 0 1 0-2.73l-.08-.15a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1-1-1.73l-.15-.1a2 2 0 0 0-.73-2.73l-.38-.22a2 2 0 0 1-2 0l-.22-.38a2 2 0 0 0-2.73-.73l-.15.1a2 2 0 0 1-1.73-1l-.1-.08a2 2 0 0 1-1.73 1l-.38-.22a2 2 0 0 1-.73-2.73l.08-.15a2 2 0 0 0-.73-2.73l-.22.38a2 2 0 0 1 0 2.73l-.38.22a2 2 0 0 0-.73 2.73l-.15.1a2 2 0 0 1-1.73 1l-.1.08a2 2 0 0 1-1.73-1l-.38-.22a2 2 0 0 1-.73-2.73l.08-.15a2 2 0 0 0-.73-2.73l-.22-.38a2 2 0 0 1-2 0l-.15-.1a2 2 0 0 1-1.73 1l-.1.08a2 2 0 0 1-1.73-1l-.38-.22a2 2 0 0 1-.73-2.73l.08-.15a2 2 0 0 0-.73-2.73l-.22-.38a2 2 0 0 1-2 0l-.15-.1a2 2 0 0 1-1.73 1l-.1.08a2 2 0 0 1-1.73-1l-.38-.22a2 2 0 0 1-.73-2.73l.08-.15a2 2 0 0 0-.73-2.73l-.22-.38a2 2 0 0 1-2 0z',
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    menu: 'M3 12h18M3 6h18M3 18h18',
    x: 'M18 6L6 18M6 6l12 12',
    check: 'M20 6L9 17l-5-5',
    alert: 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
    info: 'M12 9v4m0 4h.01',
    'check-circle': 'M22 11.08V12a10 10 0 11-5.93-9.14',
    'x-circle': 'M22 11.08V12a10 10 0 11-5.93-9.14',
    'exclamation-circle': 'M12 9v4m0 4h.01',
    'info-circle': 'M12 9v4m0 4h.01',
    plus: 'M12 5v14M5 12h14',
    minus: 'M5 12h14',
    chevron: 'M6 9l6 6 6-6',
    arrow: 'M5 12h14M12 5l7 7-7 7',
    download: 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3',
    upload: 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M5 15l7-7 7 7',
    trash: 'M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2',
    edit: 'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7',
    eye: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z',
    'eye-off': 'M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 5.94M3.36 3.36l17.28 17.28',
    share: 'M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v10',
    star: 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
    heart: 'M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z',
    bell: 'M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0',
    mail: 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z',
    location: 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z',
    calendar: 'M17 21v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6',
    clock: 'M12 6v6l4 2',
    filter: 'M3 3h18v18H3z',
    sort: 'M3 6h18M3 12h18M3 18h18',
    grid: 'M3 9h18v11H3V9zm0-6h18v11H3V3z',
    list: 'M3 12h18M3 6h18M3 18h18',
    database: 'M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7',
    server: 'M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7',
    cloud: 'M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z',
    wifi: 'M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 012.94 0M12 12h.01',
    bluetooth: 'M17 21l-5-5 5-5',
    battery: 'M8 3H5a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2v-5',
    lock: 'M18 8h-1V7a4 4 0 00-8 0v1h1',
    unlock: 'M12 15v-2a4 4 0 018 0v2',
    users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2',
    github: 'M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22',
    twitter: 'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z',
    linkedin: 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2h-2a2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z',
    facebook: 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
  };

  $: path = icons[name] || icons.home;
</script>

<svg
  class="icon icon-{size} {className}"
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
  role="img"
  aria-hidden="true"
  style="color: {color}; transform: rotate({rotate}deg);"
  data-testid="mock-icon-{name}-{size}"
  data-name={name}
  data-size={size}
>
  {#if spin}
    <animateTransform
      attributeName="transform"
      type="rotate"
      from="0 12 12"
      to="360 12 12"
      dur="1s"
      repeatCount="indefinite"
    />
  {/if}
  {#if flip === 'horizontal'}
    <g transform="scale(-1, 1) translate(-24, 0)">
      <path d={path} />
    </g>
  {:else if flip === 'vertical'}
    <g transform="scale(1, -1) translate(0, -24)">
      <path d={path} />
    </g>
  {:else}
    <path d={path} />
  {/if}
</svg>

<style>
  .icon {
    display: inline-block;
    vertical-align: middle;
    flex-shrink: 0;
  }

  .icon-xs { width: 12px; height: 12px; }
  .icon-sm { width: 16px; height: 16px; }
  .icon-md { width: 24px; height: 24px; }
  .icon-lg { width: 32px; height: 32px; }
  .icon-xl { width: 48px; height: 48px; }

  .icon[style*="rotate(90deg)"],
  .icon[style*="rotate(180deg)"],
  .icon[style*="rotate(270deg)"] {
    display: inline-flex;
  }
</style>