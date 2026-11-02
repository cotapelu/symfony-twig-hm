<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let label = '';
  export let value = '';
  export let options: { value: string; label: string }[] = [];
  export let required = false;
  export let disabled = false;
  export let name = '';
  export let size = 'md';
  export let variant = 'default';
  export let error = '';
  export let className = '';
  export let id = `select-${Math.random().toString(36).slice(2)}`;

  const dispatch = createEventDispatcher();

  function handleChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    const newValue = target.value;
    dispatch('change', newValue);
  }

  function handleBlur(event: FocusEvent) {
    // blur event
  }

  function handleFocus(event: FocusEvent) {
    // focus event
  }
</script>

<div class="select-wrapper">
  {#if label}
    <label for={id} class="select-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}
  <select
    id={id}
    value={value}
    {disabled}
    {required}
    {name}
    class="select select-{size} select-{variant} {className} {error ? 'select-error' : ''}"
    aria-invalid={error ? 'true' : 'false'}
    on:change={handleChange}
    on:blur={handleBlur}
    on:focus={handleFocus}
    data-testid="mock-input"
  >
    {#each options as option}
      <option value={option.value}>{option.label}</option>
    {/each}
  </select>

  {#if error}
    <span class="select-error-message" role="alert">
      {error}
    </span>
  {/if}
</div>

<style>
  .select-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .select-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  .select {
    padding: 0.75rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    font-size: 1rem;
    color: var(--text-primary, #111827);
    background: var(--bg-primary, #ffffff);
    transition: border-color 0.15s, box-shadow 0.15s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
  }

  .select:focus {
    outline: none;
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px var(--primary-100, #dbeafe);
  }

  .select:disabled {
    background: var(--gray-100, #f3f4f6);
    cursor: not-allowed;
  }

  .select-error {
    border-color: var(--error-500, #ef4444);
  }

  .select-error-message {
    color: var(--error-500, #ef4444);
    font-size: 0.875rem;
  }

  .select-sm { padding: 0.5rem; font-size: 0.875rem; }
  .select-lg { padding: 1rem; font-size: 1.125rem; }
</style>