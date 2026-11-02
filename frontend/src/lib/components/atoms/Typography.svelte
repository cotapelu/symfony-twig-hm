<script lang="ts">
   export let variant: 'display' | 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'body' | 'caption' | 'overline' = 'body';
   export let size: string | null = null;
   export let weight: number | null = null;
   export let align: 'left' | 'center' | 'right' | 'justify' = 'left';
   export let color: 'inherit' | 'primary' | 'secondary' | 'error' | 'warning' | 'success' | 'info' = 'inherit';
   export let truncate = false;
   export let lines: number | null = null;
   export let transform: 'uppercase' | 'lowercase' | 'capitalize' | null = null;
   export let decoration: 'underline' | 'line-through' | 'overline' | null = null;
   export let spacing: 'tight' | 'normal' | 'loose' | null = null;
   export let className = '';
   // Many consumers pass semantic `fontWeight` (silently dropped before -> svelte-check flagged). Support it via numeric font-weight in `style`.
   export let fontWeight: 'light' | 'normal' | 'medium' | 'semibold' | 'bold' | null = null;

   const fontWeightMap: Record<string, number> = { light: 300, normal: 400, medium: 500, semibold: 600, bold: 700 };

   $: classes = [
     'typography',
     `typography-${variant}`,
     size ? `typography-size-${size}` : '',
     weight ? `typography-weight-${weight}` : '',
     `typography-align-${align}`,
     `typography-color-${color}`,
     truncate ? 'typography-truncate' : '',
     lines ? 'typography-clamp' : '',
     transform ? `typography-transform-${transform}` : '',
     decoration ? `typography-decoration-${decoration}` : '',
     spacing ? `typography-spacing-${spacing}` : '',
     className
   ].filter(Boolean).join(' ');

   $: style = [lines ? `--lines: ${lines}` : '', fontWeight ? `font-weight: ${fontWeightMap[fontWeight] ?? 400}` : ''].filter(Boolean).join('; ');
</script>

{#if variant === 'display'}
  <h1 class={classes} style={style}>
    <slot />
  </h1>
{:else if variant === 'h1'}
  <h1 class={classes} style={style}>
    <slot />
  </h1>
{:else if variant === 'h2'}
  <h2 class={classes} style={style}>
    <slot />
  </h2>
{:else if variant === 'h3'}
  <h3 class={classes} style={style}>
    <slot />
  </h3>
{:else if variant === 'h4'}
  <h4 class={classes} style={style}>
    <slot />
  </h4>
{:else if variant === 'h5'}
  <h5 class={classes} style={style}>
    <slot />
  </h5>
{:else if variant === 'h6'}
  <h6 class={classes} style={style}>
    <slot />
  </h6>
{:else}
  <p class={classes} style={style}>
    <slot />
  </p>
{/if}

<style>
  .typography {
    margin: 0;
    line-height: 1.5;
    font-family: var(--font-family, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
  }

  /* Variants */
  .typography-display {
    font-size: var(--text-display, 3.5rem);
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.02em;
  }

  .typography-h1 {
    font-size: var(--text-h1, 2.25rem);
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: -0.01em;
  }

  .typography-h2 {
    font-size: var(--text-h2, 1.875rem);
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: -0.01em;
  }

  .typography-h3 {
    font-size: var(--text-h3, 1.5rem);
    font-weight: 600;
    line-height: 1.4;
  }

  .typography-h4 {
    font-size: var(--text-h4, 1.25rem);
    font-weight: 600;
    line-height: 1.4;
  }

  .typography-h5 {
    font-size: var(--text-h5, 1.125rem);
    font-weight: 600;
    line-height: 1.4;
  }

  .typography-h6 {
    font-size: var(--text-h6, 1rem);
    font-weight: 600;
    line-height: 1.4;
  }

  .typography-body {
    font-size: var(--text-body, 1rem);
    font-weight: 400;
    line-height: 1.5;
  }

  .typography-caption {
    font-size: var(--text-caption, 0.875rem);
    font-weight: 400;
    line-height: 1.4;
    opacity: 0.8;
  }

  .typography-overline {
    font-size: var(--text-overline, 0.75rem);
    font-weight: 500;
    line-height: 1.4;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.8;
  }

  /* Custom sizes */
  .typography-size-xs { font-size: 0.75rem; }
  .typography-size-sm { font-size: 0.875rem; }
  .typography-size-md { font-size: 1rem; }
  .typography-size-lg { font-size: 1.125rem; }
  .typography-size-xl { font-size: 1.25rem; }
  .typography-size-2xl { font-size: 1.5rem; }
  .typography-size-3xl { font-size: 1.875rem; }
  .typography-size-4xl { font-size: 2.25rem; }
  .typography-size-5xl { font-size: 3rem; }
  .typography-size-6xl { font-size: 3.75rem; }

  /* Font weights */
  .typography-weight-100 { font-weight: 100; }
  .typography-weight-200 { font-weight: 200; }
  .typography-weight-300 { font-weight: 300; }
  .typography-weight-400 { font-weight: 400; }
  .typography-weight-500 { font-weight: 500; }
  .typography-weight-600 { font-weight: 600; }
  .typography-weight-700 { font-weight: 700; }
  .typography-weight-800 { font-weight: 800; }
  .typography-weight-900 { font-weight: 900; }

  /* Text alignment */
  .typography-align-left { text-align: left; }
  .typography-align-center { text-align: center; }
  .typography-align-right { text-align: right; }
  .typography-align-justify { text-align: justify; }

  /* Colors */
  .typography-color-inherit { color: inherit; }
  .typography-color-primary { color: var(--text-primary, #111827); }
  .typography-color-secondary { color: var(--text-secondary, #6b7280); }
  .typography-color-error { color: var(--error-500, #ef4444); }
  .typography-color-warning { color: var(--warning-500, #f59e0b); }
  .typography-color-success { color: var(--success-500, #10b981); }
  .typography-color-info { color: var(--info-500, #3b82f6); }

  /* Text truncation */
  .typography-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .typography-clamp {
    display: -webkit-box;
    -webkit-line-clamp: var(--lines);
    -webkit-box-orient: vertical;
    line-clamp: var(--lines);
    overflow: hidden;
  }

  /* Text transforms */
  .typography-transform-uppercase { text-transform: uppercase; }
  .typography-transform-lowercase { text-transform: lowercase; }
  .typography-transform-capitalize { text-transform: capitalize; }

  /* Text decorations */
  .typography-decoration-underline { text-decoration: underline; }
  .typography-decoration-line-through { text-decoration: line-through; }
  .typography-decoration-overline { text-decoration: overline; }

  /* Letter spacing */
  .typography-spacing-tight { letter-spacing: -0.025em; }
  .typography-spacing-normal { letter-spacing: 0; }
  .typography-spacing-loose { letter-spacing: 0.025em; }

  /* Responsive typography */
  @media (max-width: 640px) {
    .typography-display { font-size: 2.5rem; }
    .typography-h1 { font-size: 1.875rem; }
    .typography-h2 { font-size: 1.5rem; }
    .typography-h3 { font-size: 1.25rem; }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .typography {
      font-weight: 600;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .typography {
      transition: none;
    }
  }
</style>