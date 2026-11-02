<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let label = '';
  export let value = '';
  export let options: { value: string; label: string }[] = [];
  export let required = false;
  export let disabled = false;
  export let name = '';
  export let size = 'md';
  export let className = '';
  export let error = '';
  export let inline = false;

  const dispatch = createEventDispatcher();

  function handleChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.checked) return;
    dispatch('change', target.value);
  }
</script>

<div class="radio-group {className}" class:inline data-testid="mock-radio">
  {#if label}
    <legend class="radio-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </legend>
  {/if}

  <div class="radio-options" class:inline>
    {#each options as option}
      <label class="radio-option" class:inline>
        <input
          type="radio"
          name={name}
          value={option.value}
          checked={value === option.value}
          {disabled}
          {required}
          on:change={handleChange}
          class="radio-input"
        />
        <span class="radio-custom" aria-hidden="true">
          <span class="radio-dot" aria-hidden="true"></span>
        </span>
        <span class="radio-label-text">{option.label}</span>
      </label>
    {/each}
  </div>

  {#if error}
    <span class="radio-error-message" role="alert">
      {error}
    </span>
  {/if}
</div>

<style>
  .radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .radio-group.inline {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .radio-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  .radio-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .radio-options.inline {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
  }

  .radio-option.inline {
    flex-direction: row;
  }

  .radio-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
  }

  .radio-custom {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--border-primary, #d1d5db);
    border-radius: 50%;
    background: var(--bg-primary, #ffffff);
    transition: all 0.15s;
    flex-shrink: 0;
  }

  .radio-dot {
    position: absolute;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--primary-500, #3b82f6);
    opacity: 0;
    transform: scale(0);
    transition: all 0.15s;
  }

  .radio-input:checked + .radio-custom .radio-dot {
    opacity: 1;
    transform: scale(1);
  }

  .radio-input:checked + .radio-custom {
    border-color: var(--primary-500, #3b82f6);
  }

  .radio-input:focus + .radio-custom {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .radio-input:disabled + .radio-custom {
    background: var(--gray-100, #f3f4f6);
    border-color: var(--gray-300, #d1d5db);
    cursor: not-allowed;
  }

  .radio-label-text {
    font-size: 0.875rem;
    color: var(--text-primary, #111827);
    cursor: pointer;
  }

  .radio-error-message {
    color: var(--error-500, #ef4444);
    font-size: 0.875rem;
  }

  @media (max-width: 768px) {
    .radio-group.inline .radio-options {
      flex-direction: column;
    }
  }
</style>