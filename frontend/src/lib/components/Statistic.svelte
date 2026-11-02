<script lang="ts">
  export let value = 0;
  export let title = '';
  export let prefix = '';
  export let suffix = '';
  export let precision = 0;
  export let valueStyle = {};
  export let className = '';

  $: formattedValue = formatValue(value, precision);

  function formatValue(val, prec: number) {
    if (prec > 0) {
      return val.toFixed(prec);
    }
    return val.toString();
  }

  $: classes = [
    'statistic',
    className
  ].filter(Boolean).join(' ');
</script>

<div class={classes}>
  {#if title}
    <div class="statistic-title">{title}</div>
  {/if}

  <div class="statistic-content">
    {#if prefix}
      <span class="statistic-prefix">{prefix}</span>
    {/if}

    <span class="statistic-value" style={Object.entries(valueStyle).map(([k, v]) => `${k}: ${v}`).join('; ')}>
      {formattedValue}
    </span>

    {#if suffix}
      <span class="statistic-suffix">{suffix}</span>
    {/if}
  </div>

  <div class="statistic-footer">
    <slot />
  </div>
</div>

<style>
  .statistic {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .statistic-title {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    font-weight: 500;
  }

  .statistic-content {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
  }

  .statistic-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
    line-height: 1;
  }

  .statistic-prefix,
  .statistic-suffix {
    font-size: 1rem;
    color: var(--text-secondary, #6b7280);
  }

  .statistic-footer {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
  }

  /* Mobile */
  @media (max-width: 640px) {
    .statistic-value {
      font-size: 1.25rem;
    }

    .statistic-prefix,
    .statistic-suffix {
      font-size: 0.875rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .statistic-value {
      font-weight: 700;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .statistic-title {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .statistic-value {
      color: var(--text-primary-dark, #f9fafb);
    }

    .statistic-prefix,
    .statistic-suffix,
    .statistic-footer {
      color: var(--text-secondary-dark, #9ca3af);
    }
  }
</style>