<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let position: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left' = 'bottom-right';
  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let variant: 'primary' | 'secondary' | 'danger' = 'primary';
  export let disabled = false;
  export let extended = false;
  export let label = '';
  export let icon = '+';

  const dispatch = createEventDispatcher();

  $: classes = [
    'fab',
    `fab-${position}`,
    `fab-${size}`,
    `fab-${variant}`,
    disabled ? 'fab-disabled' : '',
    extended ? 'fab-extended' : ''
  ].filter(Boolean).join(' ');

  function handleClick() {
    if (!disabled) {
      dispatch('click');
    }
  }
</script>

<button
  class={classes}
  {disabled}
  onclick={handleClick}
  aria-label={label || 'Action button'}
>
  <span class="fab-icon">{icon}</span>
  {#if extended && label}
    <span class="fab-label">{label}</span>
  {/if}
</button>

<style>
  .fab {
    position: fixed;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-family: inherit;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.2s ease;
    z-index: 1000;
    outline: none;
  }

  .fab:focus {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 0 0 3px rgba(59, 130, 246, 0.3);
  }

  .fab:hover:not(.fab-disabled) {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
  }

  .fab:active:not(.fab-disabled) {
    transform: scale(0.95);
  }

  /* Positions */
  .fab-bottom-right {
    bottom: 1.5rem;
    right: 1.5rem;
  }

  .fab-bottom-left {
    bottom: 1.5rem;
    left: 1.5rem;
  }

  .fab-top-right {
    top: 1.5rem;
    right: 1.5rem;
  }

  .fab-top-left {
    top: 1.5rem;
    left: 1.5rem;
  }

  /* Variants */
  .fab-primary {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .fab-primary:hover:not(.fab-disabled) {
    background: var(--primary-600, #2563eb);
  }

  .fab-secondary {
    background: var(--gray-600, #4b5563);
    color: white;
  }

  .fab-secondary:hover:not(.fab-disabled) {
    background: var(--gray-700, #374151);
  }

  .fab-danger {
    background: var(--error-500, #ef4444);
    color: white;
  }

  .fab-danger:hover:not(.fab-disabled) {
    background: var(--error-600, #dc2626);
  }

  /* Sizes */
  .fab-sm {
    padding: 0.75rem;
    font-size: 1rem;
  }

  .fab-md {
    padding: 1rem;
    font-size: 1.125rem;
  }

  .fab-lg {
    padding: 1.25rem;
    font-size: 1.25rem;
  }

  /* Extended state */
  .fab-extended {
    border-radius: 24px;
    padding: 0.75rem 1.5rem;
  }

  .fab-extended.fab-sm {
    padding: 0.5rem 1rem;
  }

  .fab-extended.fab-lg {
    padding: 1rem 2rem;
  }

  /* States */
  .fab-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
  }

  .fab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25em;
    line-height: 1;
  }

  .fab-label {
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
  }

  /* Mobile adjustments */
  @media (max-width: 768px) {
    .fab {
      bottom: 1rem;
      right: 1rem;
    }

    .fab-bottom-left,
    .fab-top-left {
      left: 1rem;
    }

    .fab-top-right,
    .fab-top-left {
      top: 1rem;
    }
  }

  /* High contrast mode */
  @media (prefers-contrast: high) {
    .fab {
      border: 2px solid;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .fab {
      transition: none;
    }

    .fab:hover {
      transform: none;
    }
  }
</style>