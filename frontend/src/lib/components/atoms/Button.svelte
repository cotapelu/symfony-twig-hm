<script lang="ts">
  // Props
  export let variant: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline' = 'primary';
  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let disabled = false;
  export let loading = false;
  export let type: 'button' | 'submit' | 'reset' = 'button';
  export let href: string | null = null;
  export let target: string | null = null;
  export let className = '';
  export let text = ''; // Optional plain text content (for simple use and testing)

  const restProps = $$restProps;

  $: classes = [
    'btn',
    `btn-${variant}`,
    `btn-${size}`,
    disabled || loading ? 'btn-disabled' : '',
    loading ? 'btn-loading' : '',
    className
  ].filter(Boolean).join(' ');
</script>

{#if href}
  <a {href} {target} class={classes} role="button" tabindex={disabled ? -1 : 0} on:click {...restProps}>
    {#if loading}
      <span class="btn-spinner" aria-hidden="true">⟳</span>
    {/if}
    {#if text}
      {text}
    {:else}
      <slot />
    {/if}
  </a>
{:else}
  <button {type} {disabled} class={classes} on:click {...restProps}>
    {#if loading}
      <span class="btn-spinner" aria-hidden="true">⟳</span>
    {/if}
    {#if text}
      {text}
    {:else}
      <slot />
    {/if}
  </button>
{/if}

<style>
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-family: inherit;
    font-weight: 500;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    position: relative;
  }

  .btn:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  /* Variants */
  .btn-primary {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .btn-primary:hover:not(.btn-disabled) {
    background: var(--primary-600, #2563eb);
  }

  .btn-secondary {
    background: var(--gray-100, #f3f4f6);
    color: var(--gray-900, #111827);
    border: 1px solid var(--gray-300, #d1d5db);
  }

  .btn-secondary:hover:not(.btn-disabled) {
    background: var(--gray-200, #e5e7eb);
  }

  .btn-danger {
    background: var(--error-500, #ef4444);
    color: white;
  }

  .btn-danger:hover:not(.btn-disabled) {
    background: var(--error-600, #dc2626);
  }

  .btn-ghost {
    background: transparent;
    color: var(--gray-700, #374151);
  }

  .btn-ghost:hover:not(.btn-disabled) {
    background: var(--gray-100, #f3f4f6);
  }

  .btn-outline {
    background: transparent;
    color: var(--primary-600, #2563eb);
    border: 1px solid var(--primary-600, #2563eb);
  }

  .btn-outline:hover:not(.btn-disabled) {
    background: var(--primary-50, #eff6ff);
  }

  /* Sizes */
  .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    height: 2rem;
  }

  .btn-md {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    height: 2.5rem;
  }

  .btn-lg {
    padding: 1rem 2rem;
    font-size: 1.125rem;
    height: 3rem;
  }

  /* States */
  .btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }

  .btn-loading {
    pointer-events: none;
  }

  .btn-spinner {
    animation: spin 1s linear infinite;
    font-size: 1em;
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  @media (prefers-color-scheme: dark) {
    .btn-primary {
      background: var(--primary-600-dark', '#1d4ed8);
    }

    .btn-primary:hover:not(.btn-disabled) {
      background: var(--primary-700-dark', '#1e40af);
    }

    .btn-secondary {
      background: var(--gray-700-dark', '#374151);
      color: var(--text-primary-dark', '#f9fafb);
      border-color: var(--border-color-dark', '#4b5563);
    }

    .btn-secondary:hover:not(.btn-disabled) {
      background: var(--gray-600-dark', '#4b5563);
    }

    .btn-danger {
      background: var(--error-600-dark', '#dc2626);
    }

    .btn-danger:hover:not(.btn-disabled) {
      background: var(--error-700-dark', '#b91c1c);
    }

    .btn-ghost {
      color: var(--primary-400-dark', '#60a5fa);
    }

    .btn-ghost:hover:not(.btn-disabled) {
      background: var(--primary-900-dark', '#1e3a8a);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .btn {
      transition: none;
    }
  }

  @media (prefers-contrast: high) {
    .btn {
      border-width: 2px;
    }

    .btn-ghost {
      border: 2px solid currentColor;
    }
  }
</style>
