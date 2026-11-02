<script lang="ts">
  export let orientation: 'horizontal' | 'vertical' = 'horizontal';
  export let variant: 'solid' | 'dashed' | 'dotted' | 'double' = 'solid';
  export let thickness: 'thin' | 'medium' | 'thick' = 'thin';
  export let color: 'default' | 'muted' | 'accent' = 'default';
  export let spacing: 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' = 'md';

  $: classes = [
    'divider',
    `divider-${orientation}`,
    `divider-${variant}`,
    `divider-${thickness}`,
    `divider-${color}`,
    spacing !== 'none' ? `divider-spacing-${spacing}` : ''
  ].filter(Boolean).join(' ');
</script>

{#if orientation === 'vertical'}
  <div class={classes} aria-orientation="vertical"></div>
{:else}
  <hr class={classes} />
{/if}

<style>
  .divider {
    border: none;
    margin: 0;
  }

  /* Horizontal dividers */
  .divider-horizontal {
    width: 100%;
    height: 0;
  }

  .divider-horizontal.divider-solid {
    border-top-style: solid;
  }

  .divider-horizontal.divider-dashed {
    border-top-style: dashed;
  }

  .divider-horizontal.divider-dotted {
    border-top-style: dotted;
  }

  .divider-horizontal.divider-double {
    border-top-style: double;
    height: 3px;
  }

  /* Vertical dividers */
  .divider-vertical {
    width: 0;
    height: 100%;
    align-self: stretch;
  }

  .divider-vertical.divider-solid {
    border-left-style: solid;
  }

  .divider-vertical.divider-dashed {
    border-left-style: dashed;
  }

  .divider-vertical.divider-dotted {
    border-left-style: dotted;
  }

  .divider-vertical.divider-double {
    border-left-style: double;
    width: 3px;
  }

  /* Thickness */
  .divider-thin {
    border-width: 1px;
  }

  .divider-medium {
    border-width: 2px;
  }

  .divider-thick {
    border-width: 4px;
  }

  /* Colors */
  .divider-default {
    border-color: var(--border-primary, #e5e7eb);
  }

  .divider-muted {
    border-color: var(--border-light, #f3f4f6);
  }

  .divider-accent {
    border-color: var(--primary-500, #3b82f6);
  }

  /* Spacing */
  .divider-spacing-xs {
    margin: 0.5rem 0;
  }

  .divider-spacing-sm {
    margin: 1rem 0;
  }

  .divider-spacing-md {
    margin: 1.5rem 0;
  }

  .divider-spacing-lg {
    margin: 2rem 0;
  }

  .divider-spacing-xl {
    margin: 3rem 0;
  }

  /* Vertical spacing */
  .divider-vertical.divider-spacing-xs {
    margin: 0 0.5rem;
  }

  .divider-vertical.divider-spacing-sm {
    margin: 0 1rem;
  }

  .divider-vertical.divider-spacing-md {
    margin: 0 1.5rem;
  }

  .divider-vertical.divider-spacing-lg {
    margin: 0 2rem;
  }

  .divider-vertical.divider-spacing-xl {
    margin: 0 3rem;
  }

  /* Responsive adjustments */
  @media (max-width: 640px) {
    .divider-spacing-md {
      margin: 1rem 0;
    }

    .divider-spacing-lg {
      margin: 1.5rem 0;
    }

    .divider-spacing-xl {
      margin: 2rem 0;
    }

    .divider-vertical.divider-spacing-md {
      margin: 0 1rem;
    }

    .divider-vertical.divider-spacing-lg {
      margin: 0 1.5rem;
    }

    .divider-vertical.divider-spacing-xl {
      margin: 0 2rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .divider {
      border-color: currentColor;
    }

    .divider-thick {
      border-width: 6px;
    }
  }

  /* Print styles */
  @media print {
    .divider {
      border-color: #000;
    }
  }
</style>