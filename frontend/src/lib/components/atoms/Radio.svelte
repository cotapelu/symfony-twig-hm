<script lang="ts">
  export let value: string = '';
  export let group: string = '';
  export let disabled = false;
  export let required = false;
  export let label = '';
  export let description = '';
  export let size = 'md'; // sm, md, lg
  export let button = false; // button style

  import { createEventDispatcher } from 'svelte';
  import { getContext, setContext } from 'svelte';

  const dispatch = createEventDispatcher();
  interface RadioGroupContext {
    value?: string;
    disabled?: boolean;
    onChange?: (value: string) => void;
  }
  const radioGroup = getContext<RadioGroupContext>('radio-group') || {};

  let radioElement: HTMLInputElement;
  let radioId = `radio-${Math.random().toString(36).substr(2, 9)}`;

  $: isChecked = radioGroup.value === value || group === value;
  $: isDisabled = disabled || radioGroup.disabled;
  $: classes = [
    'radio',
    `radio-${size}`,
    button ? 'radio-button' : '',
    isChecked ? 'radio-checked' : '',
    isDisabled ? 'radio-disabled' : ''
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'radio-wrapper',
    button ? 'radio-wrapper-button' : '',
    isDisabled ? 'radio-wrapper-disabled' : ''
  ].filter(Boolean).join(' ');

  function handleChange() {
    if (!isDisabled) {
      if (radioGroup.onChange) {
        radioGroup.onChange(value);
      }
      dispatch('change', { value });
    }
  }

  // Set context for radio group
  if (group) {
    setContext('radio-group', {
      value: group,
      disabled: isDisabled,
      onChange: (newValue: string) => {
        group = newValue;
        dispatch('change', { value: newValue });
      }
    });
  }
</script>

{#if button}
  <label for={radioId} class={wrapperClasses}>
    <input
      bind:this={radioElement}
      type="radio"
      id={radioId}
      bind:group
      value={value}
      {disabled}
      {required}
      class="radio-input"
      onchange={handleChange}
    />
    <span class={classes}>
      <slot>{label}</slot>
    </span>
  </label>
{:else}
  <div class={wrapperClasses}>
    <div class="radio-input-wrapper">
      <input
        bind:this={radioElement}
        type="radio"
        id={radioId}
        bind:group
        value={value}
        {disabled}
        {required}
        class="radio-input"
        onchange={handleChange}
      />
      <label for={radioId} class={classes}></label>
    </div>

    {#if label || description}
      <div class="radio-content">
        {#if label}
          <label for={radioId} class="radio-label">
            {label}
            {#if required}<span class="required" aria-label="required">*</span>{/if}
          </label>
        {/if}
        {#if description}
          <p class="radio-description">{description}</p>
        {/if}
      </div>
    {/if}
  </div>
{/if}

<style>
  .radio-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
  }

  .radio-wrapper-disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }

  .radio-input-wrapper {
    position: relative;
    flex-shrink: 0;
  }

  .radio-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
  }

  .radio {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--border-primary, #d1d5db);
    background: var(--bg-primary, white);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .radio:focus-within {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .radio:hover:not(.radio-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .radio-checked {
    border-color: var(--primary-500, #3b82f6);
    background: var(--primary-500, #3b82f6);
  }

  .radio-checked::after {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: white;
  }

  /* Sizes */
  .radio-sm {
    width: 1rem;
    height: 1rem;
  }

  .radio-sm.radio-checked::after {
    width: 4px;
    height: 4px;
  }

  .radio-md {
    width: 1.25rem;
    height: 1.25rem;
  }

  .radio-md.radio-checked::after {
    width: 6px;
    height: 6px;
  }

  .radio-lg {
    width: 1.5rem;
    height: 1.5rem;
  }

  .radio-lg.radio-checked::after {
    width: 8px;
    height: 8px;
  }

  /* Button style */
  .radio-wrapper-button {
    margin: 0;
  }

  .radio-button {
    border-radius: 6px;
    border: 1px solid var(--border-primary, #d1d5db);
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    min-height: 2.5rem;
    border-radius: 6px;
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .radio-button:hover:not(.radio-disabled) {
    border-color: var(--primary-500, #3b82f6);
    background: var(--primary-50, #eff6ff);
  }

  .radio-button.radio-checked {
    border-color: var(--primary-500, #3b82f6);
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .radio-button.radio-checked::after {
    display: none;
  }

  /* States */
  .radio-disabled {
    cursor: not-allowed;
    background: var(--gray-100, #f3f4f6);
    border-color: var(--gray-300, #d1d5db);
  }

  .radio-disabled.radio-checked {
    background: var(--gray-400, #9ca3af);
    border-color: var(--gray-400, #9ca3af);
  }

  /* Content */
  .radio-content {
    flex: 1;
    min-width: 0;
  }

  .radio-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary, #111827);
    cursor: pointer;
    margin-bottom: 0.25rem;
  }

  .radio-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    line-height: 1.4;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  /* Touch targets for mobile */
  @media (max-width: 768px) {
    .radio-wrapper {
      min-height: 44px;
      align-items: center;
    }

    .radio-button {
      min-height: 44px;
      padding: 0.75rem 1.25rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .radio {
      border-width: 3px;
    }

    .radio-button {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .radio,
    .radio-button {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .radio-button {
      background: var(--gray-800, #1f2937);
      color: var(--gray-100, #f3f4f6);
      border-color: var(--gray-600, #4b5563);
    }

    .radio-button:hover:not(.radio-disabled) {
      background: var(--gray-700, #374151);
      border-color: var(--primary-400, #60a5fa);
    }

    .radio-button.radio-checked {
      background: var(--primary-600, #2563eb);
      border-color: var(--primary-600, #2563eb);
    }
  }
</style>