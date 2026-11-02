<script lang="ts">
  export let cols: number | Record<string, number> = 1;
  export let gap: 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' = 'md';
  export let align: 'start' | 'center' | 'end' | 'stretch' = 'stretch';
  export let justify: 'start' | 'center' | 'end' | 'between' | 'around' | 'evenly' = 'start';
  export let wrap = true;

  $: gridCols = typeof cols === 'object' ? cols : { default: cols };
  $: classes = [
    'grid',
    `grid-gap-${gap}`,
    `grid-align-${align}`,
    `grid-justify-${justify}`,
    wrap ? 'grid-wrap' : 'grid-nowrap'
  ].filter(Boolean).join(' ');

  $: style = Object.entries(gridCols).map(([breakpoint, col]) => {
    const prefix = breakpoint === 'default' ? '' : `${breakpoint}:`;
    return `${prefix}grid-template-columns: repeat(${col}, minmax(0, 1fr))`;
  }).join('; ');
</script>

<div class={classes} {style}>
  <slot />
</div>

<style>
  .grid {
    display: grid;
    width: 100%;
  }

  .grid-wrap {
    flex-wrap: wrap;
  }

  .grid-nowrap {
    flex-wrap: nowrap;
  }

  /* Gap sizes */
  .grid-gap-none { gap: 0; }
  .grid-gap-xs { gap: 0.25rem; } /* 4px */
  .grid-gap-sm { gap: 0.5rem; }  /* 8px */
  .grid-gap-md { gap: 1rem; }    /* 16px */
  .grid-gap-lg { gap: 1.5rem; }  /* 24px */
  .grid-gap-xl { gap: 2rem; }    /* 32px */

  /* Align items */
  .grid-align-start { align-items: start; }
  .grid-align-center { align-items: center; }
  .grid-align-end { align-items: end; }
  .grid-align-stretch { align-items: stretch; }

  /* Justify content */
  .grid-justify-start { justify-content: start; }
  .grid-justify-center { justify-content: center; }
  .grid-justify-end { justify-content: end; }
  .grid-justify-between { justify-content: space-between; }
  .grid-justify-around { justify-content: space-around; }
  .grid-justify-evenly { justify-content: space-evenly; }

  /* Responsive breakpoints */
  @media (min-width: 640px) {
    .grid-cols-sm-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-sm-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-sm-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-sm-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-sm-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-sm-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-sm-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  }

  @media (min-width: 768px) {
    .grid-cols-md-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-md-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-md-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-md-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-md-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-md-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-md-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  }

  @media (min-width: 1024px) {
    .grid-cols-lg-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-lg-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-lg-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-lg-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-lg-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-lg-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-lg-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  }

  @media (min-width: 1280px) {
    .grid-cols-xl-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-xl-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-xl-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-xl-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-xl-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-xl-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-xl-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  }

  @media (min-width: 1536px) {
    .grid-cols-2xl-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-2xl-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-2xl-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-2xl-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-2xl-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-2xl-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-2xl-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
  }
</style>