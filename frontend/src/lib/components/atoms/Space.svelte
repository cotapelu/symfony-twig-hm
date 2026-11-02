<script lang="ts">
  export let direction = 'horizontal'; // horizontal, vertical
  export let size = 'md'; // xs, sm, md, lg, xl, or number
  export let wrap = false;
  export let align = 'center'; // start, center, end, stretch, baseline
  export let justify = 'start'; // start, center, end, between, around, evenly

  $: classes = [
    'space',
    `space-${direction}`,
    `space-align-${align}`,
    `space-justify-${justify}`,
    wrap ? 'space-wrap' : ''
  ].filter(Boolean).join(' ');

  $: style = typeof size === 'number' ? `--space-size: ${size}px` : '';
</script>

<div class={classes} {style}>
  <slot />
</div>

<style>
  .space {
    display: flex;
    width: 100%;
  }

  .space-horizontal {
    flex-direction: row;
  }

  .space-vertical {
    flex-direction: column;
  }

  .space-wrap {
    flex-wrap: wrap;
  }

  /* Size variants */
  .space > :global(*) {
    margin: 0;
  }

  .space-horizontal > :global(*:not(:last-child)) {
    margin-right: var(--space-size, 1rem);
  }

  .space-vertical > :global(*:not(:last-child)) {
    margin-bottom: var(--space-size, 1rem);
  }

  /* Custom sizes */
  .space-xs > :global(*:not(:last-child)) {
    --space-size: 0.25rem; /* 4px */
  }

  .space-sm > :global(*:not(:last-child)) {
    --space-size: 0.5rem; /* 8px */
  }

  .space-md > :global(*:not(:last-child)) {
    --space-size: 1rem; /* 16px */
  }

  .space-lg > :global(*:not(:last-child)) {
    --space-size: 1.5rem; /* 24px */
  }

  .space-xl > :global(*:not(:last-child)) {
    --space-size: 2rem; /* 32px */
  }

  .space-2xl > :global(*:not(:last-child)) {
    --space-size: 3rem; /* 48px */
  }

  .space-3xl > :global(*:not(:last-child)) {
    --space-size: 4rem; /* 64px */
  }

  /* Align items */
  .space-align-start { align-items: flex-start; }
  .space-align-center { align-items: center; }
  .space-align-end { align-items: flex-end; }
  .space-align-stretch { align-items: stretch; }
  .space-align-baseline { align-items: baseline; }

  /* Justify content */
  .space-justify-start { justify-content: flex-start; }
  .space-justify-center { justify-content: center; }
  .space-justify-end { justify-content: flex-end; }
  .space-justify-between { justify-content: space-between; }
  .space-justify-around { justify-content: space-around; }
  .space-justify-evenly { justify-content: space-evenly; }

  /* Responsive spacing */
  @media (max-width: 640px) {
    .space-horizontal > :global(*:not(:last-child)) {
      margin-right: calc(var(--space-size, 1rem) * 0.75);
    }

    .space-vertical > :global(*:not(:last-child)) {
      margin-bottom: calc(var(--space-size, 1rem) * 0.75);
    }
  }

  /* High contrast support */
  @media (prefers-contrast: high) {
    .space > :global(*) {
      border: 1px solid transparent;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .space {
      transition: none;
    }
  }
</style>