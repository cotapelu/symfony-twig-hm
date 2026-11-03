<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  interface Props {
    label?: string;
    type?: string;
    required?: boolean;
    placeholder?: string;
    value?: string;
    oninput?: (event: Event) => void;
    onchange?: (event: Event) => void;
    onblur?: (event: FocusEvent) => void;
    onfocus?: (event: FocusEvent) => void;
    disabled?: boolean;
    readonly?: boolean;
    name?: string;
    size?: string;
    variant?: string;
    error?: string;
    className?: string;
    id?: string;
    dataTestId?: string;
  }

  // Use $bindable() for two-way binding support
  let {
    label = '',
    type = 'text',
    required = false,
    placeholder = '',
    value = $bindable(''),
    oninput,
    onchange,
    onblur,
    onfocus,
    disabled = false,
    readonly = false,
    name = '',
    size = 'md',
    variant = 'default',
    error = '',
    className = '',
    id = `input-${Math.random().toString(36).slice(2)}`,
    dataTestId = 'mock-input',
  } = $props();

  const dispatch = createEventDispatcher();

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    value = newValue;
    dispatch('input', { target: { value: newValue } });
    if (oninput) {
      oninput({ ...event, target: { ...target, value: newValue } } as Event);
    }
  }

  function handleChange(event: Event) {
    if (onchange) onchange(event);
  }

  function handleBlur(event: FocusEvent) {
    if (onblur) onblur(event);
  }

  function handleFocus(event: FocusEvent) {
    if (onfocus) onfocus(event);
  }
</script>

<div class="input-wrapper">
  {#if label}
    <label for={id} class="input-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}
  <input
    id={id}
    type={type}
    value={value}
    placeholder={placeholder}
    disabled={disabled}
    required={required}
    readonly={readonly}
    name={name}
    class="input input-{size} input-{variant} {className} {disabled ? 'input-disabled' : ''} {error ? 'input-error' : ''}"
    aria-invalid={error ? 'true' : 'false'}
    aria-describedby={error ? `${id}-description` : undefined}
    oninput={handleInput}
    onchange={handleChange}
    onblur={handleBlur}
    onfocus={handleFocus}
    data-testid={dataTestId}
  />

  {#if error}
    <span class="input-error-message" role="alert">
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
    font-size: 1rem;
    color: var(--text-primary, #111827);
    background: var(--bg-primary, #ffffff);
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  .input:focus {
    outline: none;
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px var(--primary-100, #dbeafe);
  }

  .input:disabled {
    background: var(--gray-100, #f3f4f6);
    cursor: not-allowed;
  }

  .input-error {
    border-color: var(--error-500, #ef4444);
  }

  .input-error-message {
    color: var(--error-500, #ef4444);
    font-size: 0.875rem;
  }

  .input-sm { padding: 0.5rem; font-size: 0.875rem; }
  .input-lg { padding: 1rem; font-size: 1.125rem; }
</style>