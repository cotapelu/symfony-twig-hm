<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let checked = false;
  export let indeterminate = false;
  export let disabled = false;
  export let required = false;
  export let size = 'md'; // sm, md, lg
  export let label = '';
  export let description = '';
  export let error = '';

  const dispatch = createEventDispatcher();

  let inputElement: HTMLInputElement;
  let checkboxId = `checkbox-${Math.random().toString(36).substr(2, 9)}`;

  function handleChange(event: Event) {
    const target = event.target as HTMLInputElement;
    checked = target.checked;
    dispatch('change', { checked, indeterminate: false });
  }

  $: if (indeterminate) {
    checked = false;
  }

  $: classes = [
    'checkbox',
    `checkbox-${size}`,
    disabled ? 'checkbox-disabled' : '',
    error ? 'checkbox-error' : '',
    indeterminate ? 'checkbox-indeterminate' : ''
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'checkbox-wrapper',
    disabled ? 'checkbox-wrapper-disabled' : '',
    error ? 'checkbox-wrapper-error' : ''
  ].filter(Boolean).join(' ');
</script>

<div class={wrapperClasses}>
  <div class="checkbox-input-wrapper">
    <input
      bind:this={inputElement}
      type="checkbox"
      id={checkboxId}
      {checked}
      {disabled}
      {required}
      class="checkbox-input"
      onchange={handleChange}
      aria-describedby={description || error ? `${checkboxId}-description` : undefined}
      aria-invalid={error ? 'true' : 'false'}
    />

    <label for={checkboxId} class={classes}>
      <span class="checkbox-checkmark" aria-hidden="true">
        {#if indeterminate}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M5 12h14" />
          </svg>
        {:else if checked}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M5 13l4 4L19 7" />
          </svg>
        {/if}
      </span>
    </label>
  </div>

  {#if label || description || error}
    <div class="checkbox-content">
      {#if label}
        <label for={checkboxId} class="checkbox-label">
          {label}
          {#if required}<span class="required" aria-label="required">*</span>{/if}
        </label>
      {/if}

      {#if description}
        <p id={`${checkboxId}-description`} class="checkbox-description">
          {description}
        </p>
      {/if}

      {#if error}
        <p id={`${checkboxId}-description`} class="checkbox-error-message" role="alert">
          {error}
        </p>
      {/if}
    </div>
  {/if}
</div>

<style>
  .checkbox-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
  }

  .checkbox-wrapper-disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }

  .checkbox-input-wrapper {
    position: relative;
    flex-shrink: 0;
  }

  .checkbox-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
  }

  .checkbox {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: var(--bg-primary, white);
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .checkbox:hover:not(.checkbox-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .checkbox:focus-within {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .checkbox-checkmark {
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    transition: color 0.2s ease;
  }

  .checkbox-input:checked + .checkbox .checkbox-checkmark,
  .checkbox-indeterminate .checkbox-checkmark {
    color: var(--primary-500, #3b82f6);
  }

  .checkbox-input:checked + .checkbox {
    background: var(--primary-500, #3b82f6);
    border-color: var(--primary-500, #3b82f6);
  }

  .checkbox-indeterminate {
    background: var(--primary-500, #3b82f6);
    border-color: var(--primary-500, #3b82f6);
  }

  /* Sizes */
  .checkbox-sm {
    width: 1rem;
    height: 1rem;
  }

  .checkbox-sm .checkbox-checkmark svg {
    width: 0.75rem;
    height: 0.75rem;
  }

  .checkbox-md {
    width: 1.25rem;
    height: 1.25rem;
  }

  .checkbox-md .checkbox-checkmark svg {
    width: 1rem;
    height: 1rem;
  }

  .checkbox-lg {
    width: 1.5rem;
    height: 1.5rem;
  }

  .checkbox-lg .checkbox-checkmark svg {
    width: 1.25rem;
    height: 1.25rem;
  }

  /* States */
  .checkbox-disabled {
    cursor: not-allowed;
    background: var(--gray-100, #f3f4f6);
    border-color: var(--gray-300, #d1d5db);
  }

  .checkbox-error {
    border-color: var(--error-500, #ef4444);
  }

  .checkbox-wrapper-error .checkbox {
    border-color: var(--error-500, #ef4444);
  }

  /* Content */
  .checkbox-content {
    flex: 1;
    min-width: 0;
  }

  .checkbox-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary, #111827);
    cursor: pointer;
    margin-bottom: 0.25rem;
  }

  .checkbox-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    line-height: 1.4;
  }

  .checkbox-error-message {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: var(--error-500, #ef4444);
    font-weight: 500;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  /* Touch targets for mobile */
  @media (max-width: 768px) {
    .checkbox-wrapper {
      min-height: 44px;
      align-items: center;
    }

    .checkbox {
      margin-top: 2px; /* Align with text */
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .checkbox {
      border-width: 3px;
    }

    .checkbox-input:checked + .checkbox {
      background: var(--primary-700, #1d4ed8);
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .checkbox,
    .checkbox-checkmark {
      transition: none;
    }
  }
</style>