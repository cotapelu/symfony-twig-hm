<script lang="ts">
  export let type = 'text';
  export let value = '';
  export let placeholder = '';
  export let label = '';
  export let error = '';
  export let disabled = false;
  export let required = false;
  export let readonly = false;
  export let size = 'md'; // sm, md, lg
  export let variant = 'default'; // default, filled, outlined
  export let name = '';
  // Optional leading icon/glyph (Cycle 27): consumers passed icon= and it was dropped.
  export let icon: string | null = null;

  let inputElement: HTMLInputElement;

  export function focus() {
    inputElement?.focus();
  }

  $: classes = [
    'input',
    `input-${size}`,
    `input-${variant}`,
    error ? 'input-error' : '',
    disabled ? 'input-disabled' : '',
    readonly ? 'input-readonly' : ''
  ].filter(Boolean).join(' ');
</script>

<div class="input-wrapper" class:input-has-icon={icon}>
  {#if icon}
    <span class="input-icon" aria-hidden="true">{icon}</span>
  {/if}
  {#if label}
    <label for={inputElement?.id} class="input-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}

  <input
    bind:this={inputElement}
    {type}
    bind:value
    {placeholder}
    {disabled}
    {required}
    {readonly}
    {name}
    class={classes}
    aria-invalid={error ? 'true' : 'false'}
    aria-describedby={error ? 'error-message' : undefined}
  />

  {#if error}
    <span id="error-message" class="input-error-message" role="alert">
      {error}
    </span>
  {/if}
</div>

<style>
  .input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .input-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  .input {
    padding: 0.75rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    font-family: inherit;
    font-size: 1rem;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    transition: all 0.2s ease;
  }

  .input:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Variants */
  .input-filled {
    background: var(--gray-50, #f9fafb);
    border-color: var(--gray-300, #d1d5db);
  }

  .input-outlined {
    border-width: 2px;
  }

  /* Sizes */
  .input-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .input-lg {
    padding: 1rem 1.25rem;
    font-size: 1.125rem;
  }

  /* States */
  .input-error {
    border-color: var(--error-500, #ef4444);
  }

  .input-error:focus {
    border-color: var(--error-500, #ef4444);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
  }

  .input-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--gray-50, #f9fafb);
  }

  .input-readonly {
    background: var(--gray-50, #f9fafb);
    cursor: default;
  }

  .input-error-message {
    color: var(--error-500, #ef4444);
    font-size: 0.875rem;
    font-weight: 500;
  }

  @media (prefers-color-scheme: dark) {
    .input {
      background-color: var(--bg-primary-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
      border-color: var(--border-color-dark, #374151);
    }

    .input-filled {
      background-color: var(--bg-secondary-dark, #111827);
    }

    .input-readonly {
      background-color: var(--bg-readonly-dark, #374151);
    }

    .input-disabled {
      background-color: var(--bg-disabled-dark, #111827);
      color: var(--text-disabled-dark, #6b7280);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .input {
      transition: none;
    }
  }

  @media (prefers-contrast: high) {
    .input {
      border-width: 2px;
    }

    .input:focus {
      border-width: 2px;
    }

    .input-outlined {
      border-width: 3px;
    }
  }
</style>