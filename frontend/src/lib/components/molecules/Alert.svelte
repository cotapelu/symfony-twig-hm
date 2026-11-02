<script lang="ts">
  export let type: 'success' | 'info' | 'warning' | 'error' = 'info';
  export let title = '';
  export let description = '';
  export let closable = false;
  export let showIcon = true;
  export let banner = false;
  export let action: any = null;

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  $: icon = getIcon(type);
  $: classes = [
    'alert',
    `alert-${type}`,
    banner ? 'alert-banner' : '',
    closable ? 'alert-closable' : ''
  ].filter(Boolean).join(' ');

  function getIcon(type: string) {
    const icons: Record<string, string> = {
      success: '✓',
      info: 'ℹ',
      warning: '⚠',
      error: '✕'
    };
    return icons[type] || 'ℹ';
  }

  function handleClose() {
    dispatch('close');
  }
</script>

<div class={classes} role="alert" aria-live={type === 'error' ? 'assertive' : 'polite'}>
  {#if showIcon}
    <div class="alert-icon" aria-hidden="true">
      {icon}
    </div>
  {/if}

  <div class="alert-content">
    {#if title}
      <div class="alert-title">{title}</div>
    {/if}
    {#if description}
      <div class="alert-description">{description}</div>
    {/if}
    {#if action}
      <div class="alert-action">
        <slot name="action" />
      </div>
    {/if}
  </div>

  {#if closable}
    <button
      class="alert-close"
      onclick={handleClose}
      aria-label="Close alert"
      type="button"
    >
      ✕
    </button>
  {/if}
</div>

<style>
  .alert {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: var(--border-radius, 6px);
    border: 1px solid;
    font-size: 0.875rem;
    line-height: 1.4;
  }

  /* Types */
  .alert-success {
    background: var(--success-50, #f0fdf4);
    border-color: var(--success-200, #bbf7d0);
    color: var(--success-800, #166534);
  }

  .alert-info {
    background: var(--info-50, #eff6ff);
    border-color: var(--info-200, #bfdbfe);
    color: var(--info-800, #1e40af);
  }

  .alert-warning {
    background: var(--warning-50, #fffbeb);
    border-color: var(--warning-200, #fde68a);
    color: var(--warning-800, #92400e);
  }

  .alert-error {
    background: var(--error-50, #fef2f2);
    border-color: var(--error-200, #fecaca);
    color: var(--error-800, #991b1b);
  }

  /* Banner variant */
  .alert-banner {
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-top: none;
  }

  .alert-banner.alert-success {
    border-color: var(--success-500, #10b981);
  }

  .alert-banner.alert-info {
    border-color: var(--info-500, #3b82f6);
  }

  .alert-banner.alert-warning {
    border-color: var(--warning-500, #f59e0b);
  }

  .alert-banner.alert-error {
    border-color: var(--error-500, #ef4444);
  }

  /* Icon */
  .alert-icon {
    flex-shrink: 0;
    font-size: 1rem;
    font-weight: bold;
    margin-top: 0.125rem;
  }

  /* Content */
  .alert-content {
    flex: 1;
  }

  .alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .alert-description {
    margin-bottom: 0.5rem;
  }

  .alert-action {
    margin-top: 0.5rem;
  }

  /* Close button */
  .alert-close {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    background: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    transition: opacity 0.2s ease;
  }

  .alert-close:hover {
    opacity: 1;
  }

  .alert-close:focus {
    outline: 2px solid currentColor;
    outline-offset: 2px;
  }

  /* Responsive */
  @media (max-width: 640px) {
    .alert {
      padding: 0.75rem 1rem;
      gap: 0.5rem;
    }

    .alert-icon {
      font-size: 0.875rem;
    }

    .alert-title {
      font-size: 0.875rem;
    }

    .alert-description {
      font-size: 0.8125rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .alert {
      border-width: 2px;
    }

    .alert-banner {
      border-bottom-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .alert-close {
      transition: none;
    }
  }

  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    .alert-success {
      background: var(--success-900, #14532d);
      border-color: var(--success-700, #15803d);
      color: var(--success-100, #dcfce7);
    }

    .alert-info {
      background: var(--info-900, #1e3a8a);
      border-color: var(--info-700, #1d4ed8);
      color: var(--info-100, #dbeafe);
    }

    .alert-warning {
      background: var(--warning-900, #451a03);
      border-color: var(--warning-700, #a16207);
      color: var(--warning-100, #fef3c7);
    }

    .alert-error {
      background: var(--error-900, #450a0a);
      border-color: var(--error-700, #b91c1c);
      color: var(--error-100, #fee2e2);
    }
  }
</style>