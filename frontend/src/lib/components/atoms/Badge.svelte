<script lang="ts">
  export let count = 0;
  export let showZero = false;
  export let maxCount = 99;
  export let dot = false;
  export let status: 'success' | 'error' | 'warning' | 'info' | 'default' | null = null;
  export let text = '';
  export let size: 'sm' | 'md' | 'lg' = 'md';
  // Semantic colors are accepted alongside the visual styles (Cycle 24): consumers
  // use Badge as a status atom; the matching CSS classes already exist.
  export let variant: 'default' | 'outlined' | 'filled' | 'success' | 'error' | 'warning' | 'info' | 'secondary' = 'default';
  export let position: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' = 'top-right';

  $: displayCount = count > maxCount ? `${maxCount}+` : count.toString();
  $: shouldShow = showZero || count > 0;
  $: classes = [
    'badge',
    `badge-${size}`,
    `badge-${variant}`,
    status ? `badge-${status}` : '',
    dot ? 'badge-dot' : '',
    `badge-${position}`
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'badge-wrapper',
    shouldShow ? 'badge-has-content' : ''
  ].filter(Boolean).join(' ');
</script>

<div class={wrapperClasses}>
  <slot />

  {#if shouldShow}
    <span class={classes} aria-label={text || `${count} items`}>
      {#if !dot}
        {displayCount}
      {/if}
    </span>
  {/if}
</div>

<style>
  .badge-wrapper {
    position: relative;
    display: inline-block;
  }

  .badge {
    position: absolute;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    border-radius: 10px;
    z-index: 10;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.375rem;
  }

  /* Sizes */
  .badge-sm {
    font-size: 0.625rem;
    min-width: 1rem;
    height: 1rem;
    padding: 0 0.25rem;
  }

  .badge-lg {
    font-size: 0.875rem;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 0.5rem;
  }

  /* Variants */
  .badge-default {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .badge-outlined {
    background: white;
    color: var(--primary-500, #3b82f6);
    border: 2px solid var(--primary-500, #3b82f6);
  }

  .badge-filled {
    background: var(--primary-600, #2563eb);
    color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  /* Status colors */
  .badge-success {
    background: var(--success-500, #10b981);
    color: white;
  }

  .badge-success.badge-outlined {
    background: white;
    color: var(--success-500, #10b981);
    border-color: var(--success-500, #10b981);
  }

  .badge-secondary {
    background: var(--gray-500, #6b7280);
    color: white;
  }

  .badge-secondary.badge-outlined {
    background: white;
    color: var(--gray-500, #6b7280);
    border-color: var(--gray-500, #6b7280);
  }

  .badge-error {
    background: var(--error-500, #ef4444);
    color: white;
  }

  .badge-error.badge-outlined {
    background: white;
    color: var(--error-500, #ef4444);
    border-color: var(--error-500, #ef4444);
  }

  .badge-warning {
    background: var(--warning-500, #f59e0b);
    color: white;
  }

  .badge-warning.badge-outlined {
    background: white;
    color: var(--warning-500, #f59e0b);
    border-color: var(--warning-500, #f59e0b);
  }

  .badge-info {
    background: var(--info-500, #3b82f6);
    color: white;
  }

  .badge-info.badge-outlined {
    background: white;
    color: var(--info-500, #3b82f6);
    border-color: var(--info-500, #3b82f6);
  }

  /* Dot variant */
  .badge-dot {
    width: 0.5rem;
    height: 0.5rem;
    min-width: 0.5rem;
    padding: 0;
    border-radius: 50%;
  }

  .badge-sm.badge-dot {
    width: 0.375rem;
    height: 0.375rem;
    min-width: 0.375rem;
  }

  .badge-lg.badge-dot {
    width: 0.625rem;
    height: 0.625rem;
    min-width: 0.625rem;
  }

  /* Positions */
  .badge-top-right {
    top: -0.25rem;
    right: -0.25rem;
  }

  .badge-top-left {
    top: -0.25rem;
    left: -0.25rem;
  }

  .badge-bottom-right {
    bottom: -0.25rem;
    right: -0.25rem;
  }

  .badge-bottom-left {
    bottom: -0.25rem;
    left: -0.25rem;
  }

  /* Responsive adjustments */
  @media (max-width: 640px) {
    .badge {
      font-size: 0.625rem;
      min-width: 1rem;
      height: 1rem;
      padding: 0 0.25rem;
    }

    .badge-top-right,
    .badge-top-left,
    .badge-bottom-right,
    .badge-bottom-left {
      top: -0.125rem;
      right: -0.125rem;
      bottom: -0.125rem;
      left: -0.125rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .badge {
      border: 2px solid;
    }

    .badge-outlined {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .badge {
      transition: none;
    }
  }

  /* Animation for new badges */
  .badge-wrapper:not(.badge-has-content) .badge {
    animation: badge-appear 0.3s ease;
  }

  @keyframes badge-appear {
    from {
      transform: scale(0);
      opacity: 0;
    }
    to {
      transform: scale(1);
      opacity: 1;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .badge-outlined {
      background: var(--gray-800, #1f2937);
      color: var(--primary-400, #60a5fa);
      border-color: var(--primary-400, #60a5fa);
    }
  }
</style>