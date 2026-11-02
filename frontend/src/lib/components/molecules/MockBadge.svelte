<script lang="ts">
  export let children;
  export let text = '';
  export let variant = 'outlined';
  export let size = 'md';
  export let className = '';

  const variants = {
    filled: 'badge-filled',
    default: 'badge-default',
    outlined: 'badge-outlined',
    error: 'badge-error',
    warning: 'badge-warning',
    success: 'badge-success'
  };

  const sizes = {
    sm: 'badge-sm',
    md: 'badge-md',
    lg: 'badge-lg'
  };
</script>

<span
  class="badge {variants[variant]} {sizes[size]} {className}"
  data-testid="mock-badge">
  {#if text}
    {text}
  {:else if children}
    {@render children?.()}
  {/if}
</span>

<style>
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
  }

  .badge-sm {
    padding: 0.125rem 0.375rem;
    font-size: 0.625rem;
  }

  .badge-lg {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
  }

  .badge-filled {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .badge-default {
    background: var(--gray-100, #f3f4f6);
    color: var(--gray-700, #374151);
    border: 1px solid var(--gray-300, #d1d5db);
  }

  .badge-outlined {
    background: transparent;
    color: var(--primary-500, #3b82f6);
    border: 1px solid var(--primary-500, #3b82f6);
  }

  .badge-error {
    background: var(--error-500, #ef4444);
    color: white;
  }

  .badge-warning {
    background: var(--warning-500, #f59e0b);
    color: white;
  }

  .badge-success {
    background: var(--success-500, #10b981);
    color: white;
  }
</style>