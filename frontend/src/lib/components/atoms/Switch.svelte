<script lang="ts">
  export let checked = false;
  export let disabled = false;
  export let loading = false;
  export let size = 'default'; // small, default
  export let checkedText = '';
  export let uncheckedText = '';
  export let className = '';
  // Consumers use an `onchange` callback prop (svelte-check previously flagged it
  // as an unknown prop and it was silently dropped at runtime). Support it.
  export let onchange: (() => void) | undefined = undefined;

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  function handleClick() {
    if (disabled || loading) return;
    checked = !checked;
    dispatch('change', { checked });
    onchange?.();
  }

  function handleKeydown(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      handleClick();
    }
  }

  $: classes = [
    'switch',
    `switch-${size}`,
    checked ? 'switch-checked' : '',
    disabled ? 'switch-disabled' : '',
    loading ? 'switch-loading' : '',
    className
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'switch-wrapper',
    disabled ? 'switch-wrapper-disabled' : ''
  ].filter(Boolean).join(' ');
</script>

<button
  type="button"
  class={wrapperClasses}
  disabled={disabled || loading}
  onclick={handleClick}
  onkeydown={handleKeydown}
  role="switch"
  aria-checked={checked}
  tabindex={disabled ? -1 : 0}
>
  <div class={classes}>
    <div class="switch-handle">
      {#if loading}
        <span class="switch-spinner" aria-hidden="true">⟳</span>
      {/if}
    </div>
  </div>

  {#if checkedText || uncheckedText}
    <span class="switch-text">
      {checked ? checkedText : uncheckedText}
    </span>
  {/if}
</button>

<style>
  .switch-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-family: inherit;
    font-size: 0.875rem;
    cursor: pointer;
    user-select: none;
    transition: opacity 0.2s ease;
  }

  .switch-wrapper:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
    border-radius: 4px;
  }

  .switch-wrapper-disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }

  .switch {
    position: relative;
    display: inline-block;
    width: 2.75rem;
    height: 1.5rem;
    border-radius: 0.75rem;
    background: var(--gray-300, #d1d5db);
    border: 2px solid transparent;
    transition: all 0.2s ease;
    overflow: hidden;
  }

  .switch-handle {
    position: absolute;
    top: 1px;
    left: 1px;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .switch-checked {
    background: var(--primary-500, #3b82f6);
  }

  .switch-checked .switch-handle {
    left: calc(100% - 1rem - 1px);
    transform: translateX(-1px);
  }

  .switch-loading .switch-handle {
    left: calc(50% - 0.5rem);
  }

  .switch-spinner {
    animation: spin 1s linear infinite;
    font-size: 0.75rem;
    color: var(--primary-500, #3b82f6);
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  .switch-text {
    color: var(--text-primary, #111827);
    font-weight: 500;
  }

  /* Sizes */
  .switch-small {
    width: 2rem;
    height: 1rem;
  }

  .switch-small .switch-handle {
    width: 0.75rem;
    height: 0.75rem;
  }

  .switch-small.switch-checked .switch-handle {
    left: calc(100% - 0.75rem - 1px);
  }

  .switch-small.switch-loading .switch-handle {
    left: calc(50% - 0.375rem);
  }

  /* States */
  .switch-wrapper:hover:not(.switch-wrapper-disabled) .switch:not(.switch-checked) {
    background: var(--gray-400, #9ca3af);
  }

  .switch-wrapper:hover:not(.switch-wrapper-disabled) .switch-checked {
    background: var(--primary-600, #2563eb);
  }

  /* Focus */
  .switch-wrapper:focus .switch {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  }

  /* Mobile */
  @media (max-width: 640px) {
    .switch {
      width: 2.5rem;
      height: 1.25rem;
    }

    .switch-handle {
      width: 0.875rem;
      height: 0.875rem;
    }

    .switch-checked .switch-handle {
      left: calc(100% - 0.875rem - 1px);
    }

    .switch-loading .switch-handle {
      left: calc(50% - 0.4375rem);
    }

    .switch-small {
      width: 1.75rem;
      height: 0.875rem;
    }

    .switch-small .switch-handle {
      width: 0.625rem;
      height: 0.625rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .switch {
      border-width: 3px;
    }

    .switch-checked {
      border-color: var(--primary-700, #1d4ed8);
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .switch,
    .switch-handle,
    .switch-spinner {
      transition: none;
    }

    .switch-spinner {
      animation: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .switch {
      background: var(--gray-600, #4b5563);
    }

    .switch-checked {
      background: var(--primary-400, #60a5fa);
    }

    .switch-wrapper:hover:not(.switch-wrapper-disabled) .switch:not(.switch-checked) {
      background: var(--gray-500, #6b7280);
    }

    .switch-wrapper:hover:not(.switch-wrapper-disabled) .switch-checked {
      background: var(--primary-300, #93c5fd);
    }

    .switch-text {
      color: var(--text-primary-dark, #f9fafb);
    }

    .switch-spinner {
      color: var(--primary-400, #60a5fa);
    }
  }
</style>