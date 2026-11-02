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
    'mock-alert',
    `mock-alert-${type}`,
    banner ? 'mock-alert-banner' : '',
    closable ? 'mock-alert-closable' : ''
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

<div class={classes} role="alert" aria-live={type === 'error' ? 'assertive' : 'polite'} data-testid={`mock-alert-${type}`}>
  {#if showIcon}
    <div class="mock-alert-icon" aria-hidden="true">
      {icon}
    </div>
  {/if}

  <div class="mock-alert-content">
    {#if title}
      <div class="mock-alert-title">{title}</div>
    {/if}
    {#if description}
      <div class="mock-alert-description">{description}</div>
    {/if}
  </div>

  {#if closable}
    <button
      class="mock-alert-close"
      onclick={handleClose}
      aria-label="Close alert"
      type="button"
      data-testid="mock-alert-close"
    >
      ✕
    </button>
  {/if}
</div>

<style>
  .mock-alert {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    border: 1px solid;
    font-size: 0.875rem;
    line-height: 1.4;
  }

  .mock-alert-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
  }

  .mock-alert-info {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1e40af;
  }

  .mock-alert-warning {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
  }

  .mock-alert-error {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
  }

  .mock-alert-banner {
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-top: none;
  }

  .mock-alert-icon {
    flex-shrink: 0;
    font-size: 1rem;
    font-weight: bold;
    margin-top: 0.125rem;
  }

  .mock-alert-content {
    flex: 1;
  }

  .mock-alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .mock-alert-description {
    margin-bottom: 0.5rem;
  }

  .mock-alert-close {
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

  .mock-alert-close:hover {
    opacity: 1;
  }
</style>