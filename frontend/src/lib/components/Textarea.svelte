<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let value = '';
  export let placeholder = '';
  export let label = '';
  export let disabled = false;
  export let readonly = false;
  export let required = false;
  export let rows = 3;
  export let cols: number | null = null;
  export let maxlength: number | null = null;
  export let minlength: number | null = null;
  export let name = '';
  export let id = '';
  export let className = '';
  export let style = '';

  const dispatch = createEventDispatcher();

  function handleInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    value = target.value;
    dispatch('input', { value });
  }

  function handleChange(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    dispatch('change', { value: target.value });
  }

  function handleFocus(event: FocusEvent) {
    dispatch('focus', event);
  }

  function handleBlur(event: FocusEvent) {
    dispatch('blur', event);
  }
</script>

<div class="textarea-wrapper">
  {#if label}
    <label for={id || name} class="textarea-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}

  <textarea
    {value}
    {placeholder}
    {disabled}
    {readonly}
    {required}
    {rows}
    {cols}
    {maxlength}
    {minlength}
    {name}
    {id}
    class="textarea {className}"
    {style}
    oninput={handleInput}
    onchange={handleChange}
    onfocus={handleFocus}
    onblur={handleBlur}
  ></textarea>
</div>

<style>
  .textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 6px;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--text-primary, #374151);
    background-color: var(--bg-primary, #ffffff);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    font-family: inherit;
  }

  .textarea:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .textarea:disabled {
    background-color: var(--bg-disabled, #f9fafb);
    color: var(--text-disabled, #6b7280);
    cursor: not-allowed;
  }

  .textarea:invalid {
    border-color: var(--error-500, #ef4444);
  }

  .textarea::placeholder {
    color: var(--text-secondary, #9ca3af);
  }

  @media (prefers-color-scheme: dark) {
    .textarea {
      background-color: var(--bg-primary-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
      border-color: var(--border-color-dark, #374151);
    }

    .textarea::placeholder {
      color: var(--text-secondary-dark, #6b7280);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .textarea {
      transition: none;
    }
  }

  @media (prefers-contrast: high) {
    .textarea {
      border-width: 2px;
    }

    .textarea:focus {
      border-width: 2px;
    }
  }
</style>