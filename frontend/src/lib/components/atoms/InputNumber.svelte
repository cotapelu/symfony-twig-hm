<script lang="ts">
  export let value: number = 0;
  export let min: number = -Infinity;
  export let max: number = Infinity;
  export let step = 1;
  export let precision = 0;
  export let controls = true;
  export let disabled = false;
  export let readonly = false;
  export let placeholder = '';
  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let formatter: ((val: number) => string) | null = null;
  export let parser: ((str: string) => number) | null = null;
  export let className = '';

  import { createEventDispatcher } from 'svelte';
  const dispatch = createEventDispatcher();

  let inputElement: HTMLInputElement;

  $: displayValue = formatValue(value);

  function formatValue(val: number) {
    if (formatter) {
      return formatter(val);
    }
    if (precision > 0) {
      return val.toFixed(precision);
    }
    return val.toString();
  }

  function parseValue(str: string) {
    if (parser) {
      return parser(str);
    }
    const parsed = parseFloat(str);
    return isNaN(parsed) ? 0 : parsed;
  }

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = parseValue(target.value);
    updateValue(newValue);
  }

  function handleBlur(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = parseValue(target.value);
    const clampedValue = clampValue(newValue);
    updateValue(clampedValue);
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      increase();
    } else if (event.key === 'ArrowDown') {
      event.preventDefault();
      decrease();
    }
  }

  function increase() {
    const newValue = clampValue(value + step);
    updateValue(newValue);
  }

  function decrease() {
    const newValue = clampValue(value - step);
    updateValue(newValue);
  }

  function clampValue(val: number) {
    return Math.max(min, Math.min(max, val));
  }

  function updateValue(newValue: number) {
    if (newValue !== value) {
      value = newValue;
      dispatch('change', { value });
      if (inputElement) {
        inputElement.value = displayValue;
      }
    }
  }

  $: classes = [
    'input-number',
    `input-number-${size}`,
    disabled ? 'input-number-disabled' : '',
    readonly ? 'input-number-readonly' : '',
    className
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'input-number-wrapper',
    controls ? 'input-number-with-controls' : ''
  ].filter(Boolean).join(' ');
</script>

<div class={wrapperClasses}>
  {#if controls}
    <button
      type="button"
      class="input-number-handler input-number-handler-up"
      disabled={disabled || readonly || value >= max}
      onclick={increase}
      aria-label="Increase value"
    >
      +
    </button>
  {/if}

  <div class="input-number-input-wrapper">
    <input
      bind:this={inputElement}
      type="text"
      class={classes}
      {placeholder}
      {disabled}
      {readonly}
      value={displayValue}
      oninput={handleInput}
      onblur={handleBlur}
      onkeydown={handleKeydown}
      inputmode="decimal"
      pattern="[0-9]*[.,]?[0-9]*"
    />
  </div>

  {#if controls}
    <button
      type="button"
      class="input-number-handler input-number-handler-down"
      disabled={disabled || readonly || value <= min}
      onclick={decrease}
      aria-label="Decrease value"
    >
      -
    </button>
  {/if}
</div>

<style>
  .input-number-wrapper {
    display: inline-flex;
    align-items: stretch;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    overflow: hidden;
  }

  .input-number-with-controls .input-number-input-wrapper {
    flex: 1;
  }

  .input-number-handler {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    background: var(--gray-50, #f9fafb);
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    user-select: none;
  }

  .input-number-handler:hover:not(:disabled) {
    background: var(--primary-50, #eff6ff);
    color: var(--primary-600, #2563eb);
  }

  .input-number-handler:active:not(:disabled) {
    background: var(--primary-100, #dbeafe);
  }

  .input-number-handler:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .input-number-handler-up {
    border-bottom: 1px solid var(--border-primary, #d1d5db);
  }

  .input-number {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: none;
    background: transparent;
    color: var(--text-primary, #111827);
    font-size: 1rem;
    line-height: 1.5;
    text-align: center;
  }

  .input-number:focus {
    outline: none;
  }

  .input-number::placeholder {
    color: var(--text-secondary, #6b7280);
  }

  .input-number-disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .input-number-readonly {
    cursor: default;
  }

  /* Sizes */
  .input-number-sm .input-number {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  .input-number-sm .input-number-handler {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.875rem;
  }

  .input-number-lg .input-number {
    padding: 0.75rem 1rem;
    font-size: 1.125rem;
  }

  .input-number-lg .input-number-handler {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 1.125rem;
  }

  /* Without controls */
  .input-number-wrapper:not(.input-number-with-controls) {
    border-radius: 6px;
  }

  .input-number-wrapper:not(.input-number-with-controls) .input-number {
    text-align: left;
  }

  /* Focus styles */
  .input-number-wrapper:focus-within {
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Mobile */
  @media (max-width: 640px) {
    .input-number-handler {
      width: 2.5rem;
    }

    .input-number-sm .input-number-handler {
      width: 2rem;
    }

    .input-number-lg .input-number-handler {
      width: 3rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .input-number-wrapper {
      border-width: 2px;
    }

    .input-number-handler-up {
      border-bottom-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .input-number-handler {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .input-number-wrapper {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .input-number-handler {
      background: var(--gray-700, #374151);
      color: var(--text-secondary-dark, #9ca3af);
      border-color: var(--gray-600, #4b5563);
    }

    .input-number-handler:hover:not(:disabled) {
      background: var(--primary-900, #1e3a8a);
      color: var(--primary-300, #93c5fd);
    }

    .input-number-handler:active:not(:disabled) {
      background: var(--primary-800, #1e40af);
    }

    .input-number {
      background: transparent;
      color: var(--text-primary-dark, #f9fafb);
    }

    .input-number::placeholder {
      color: var(--text-secondary-dark, #9ca3af);
    }
  }
</style>