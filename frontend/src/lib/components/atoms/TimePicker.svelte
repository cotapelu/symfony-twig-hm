<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  export let value = '';
  export let placeholder = 'Select time';
  export let disabled = false;
  export let required = false;
  export let size = 'md';
  export let variant = 'default';
  export let format = 'HH:mm';
  export let step = 15;
  export const minTime: Date | null = null;
  export const maxTime: Date | null = null;
  export let label = '';
  export let error = '';

  const dispatch = createEventDispatcher();

  let inputElement: HTMLInputElement;
  let timePickerElement: HTMLElement;
  let isOpen = false;
  let selectedHour = 12;
  let selectedMinute = 0;
  let isAM = true;
  let inputValue = '';

  $: if (value) {
    parseTime(value);
    inputValue = formatTime();
  } else {
    inputValue = '';
  }

  function parseTime(timeString: string) {
    if (!timeString) return;

    const [time, period] = timeString.split(' ');
    const [hourStr, minuteStr] = time.split(':');
    let hour = parseInt(hourStr, 10);
    const minute = parseInt(minuteStr, 10);

    if (format.includes('a') && period) {
      if (period.toLowerCase() === 'pm' && hour !== 12) {
        hour += 12;
      } else if (period.toLowerCase() === 'am' && hour === 12) {
        hour = 0;
      }
      isAM = period.toLowerCase() === 'am';
    }

    selectedHour = hour;
    selectedMinute = minute;
  }

  function formatTime() {
    let hour = selectedHour;
    let period = '';

    if (format.includes('a')) {
      if (hour === 0) {
        hour = 12;
        period = ' AM';
      } else if (hour < 12) {
        period = ' AM';
      } else if (hour === 12) {
        period = ' PM';
      } else {
        hour -= 12;
        period = ' PM';
      }
    }

    const hourStr = hour.toString().padStart(2, '0');
    const minuteStr = selectedMinute.toString().padStart(2, '0');

    return `${hourStr}:${minuteStr}${period}`;
  }

  function toggleTimePicker() {
    if (!disabled) {
      isOpen = !isOpen;
    }
  }

  function selectHour(hour: number) {
    selectedHour = hour;
    updateValue();
  }

  function selectMinute(minute: number) {
    selectedMinute = minute;
    updateValue();
  }

  function togglePeriod() {
    isAM = !isAM;
    if (isAM) {
      if (selectedHour >= 12) selectedHour -= 12;
    } else {
      if (selectedHour < 12) selectedHour += 12;
    }
    updateValue();
  }

  function updateValue() {
    const timeString = formatTime();
    value = timeString;
    inputValue = timeString;
    dispatch('change', { value: timeString });
  }

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const inputTime = target.value;
    if (inputTime) {
      value = inputTime;
      parseTime(inputTime);
      dispatch('change', { value: inputTime });
    }
  }

  interface TimeOption {
    value: number;
    label: string;
  }

  function getHours(): TimeOption[] {
    return Array.from({ length: format.includes('a') ? 12 : 24 }, (_, i) => {
      const hour = format.includes('a') ? (i === 0 ? 12 : i) : i;
      return { value: hour, label: hour.toString().padStart(2, '0') };
    });
  }

  function getMinutes(): TimeOption[] {
    return Array.from({ length: 60 / step }, (_, i) => {
      const minute = i * step;
      return { value: minute, label: minute.toString().padStart(2, '0') };
    });
  }

  $: hours = getHours();
  $: minutes = getMinutes();

  onMount(() => {
    function handleClickOutside(event: MouseEvent) {
      if (inputElement && !inputElement.contains(event.target as Node) &&
          timePickerElement && !timePickerElement.contains(event.target as Node)) {
        isOpen = false;
      }
    }

    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  $: classes = [
    'timepicker',
    `timepicker-${size}`,
    `timepicker-${variant}`,
    isOpen ? 'timepicker-open' : '',
    disabled ? 'timepicker-disabled' : '',
    error ? 'timepicker-error' : ''
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'timepicker-wrapper',
    disabled ? 'timepicker-wrapper-disabled' : ''
  ].filter(Boolean).join(' ');
</script>

<div class={wrapperClasses}>
  {#if label}
    <label for="timepicker-input" class="timepicker-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}

  <div class="timepicker-input-wrapper">
    <input
      bind:this={inputElement}
      id="timepicker-input"
      type="text"
      {placeholder}
      bind:value={inputValue}
      {disabled}
      {required}
      class={classes}
      onclick={toggleTimePicker}
      oninput={handleInput}
      autocomplete="off"
    />

    <button
      type="button"
      class="timepicker-toggle"
      onclick={toggleTimePicker}
      disabled={disabled}
      aria-label="Toggle time picker"
    >
      🕐
    </button>
  </div>

  {#if isOpen}
    <div bind:this={timePickerElement} class="timepicker-dropdown" role="dialog" aria-modal="true">
      <div class="timepicker-content">
        <!-- Hours -->
        <div class="timepicker-section">
          <label for="timepicker-hour" class="timepicker-section-label">Hour</label>
          <div class="timepicker-grid">
            {#each hours as hour}
              <button
                type="button"
                id="timepicker-hour"
                class="timepicker-option"
                class:selected={hour.value === (format.includes('a') ? (selectedHour === 0 ? 12 : selectedHour > 12 ? selectedHour - 12 : selectedHour) : selectedHour)}
                onclick={() => selectHour(hour.value)}
              >
                {hour.label}
              </button>
            {/each}
          </div>
        </div>

        <!-- Minutes -->
        <div class="timepicker-section">
          <label for="timepicker-minute" class="timepicker-section-label">Minute</label>
          <div class="timepicker-grid">
            {#each minutes as minute}
              <button
                type="button"
                id="timepicker-minute"
                class="timepicker-option"
                class:selected={minute.value === selectedMinute}
                onclick={() => selectMinute(minute.value)}
              >
                {minute.label}
              </button>
            {/each}
          </div>
        </div>

        <!-- AM/PM -->
        {#if format.includes('a')}
          <div class="timepicker-section">
            <label for="timepicker-period" class="timepicker-section-label">Period</label>
            <div class="timepicker-period">
              <button
                type="button"
                id="timepicker-period"
                class="timepicker-option"
                class:selected={isAM}
                onclick={togglePeriod}
              >
                AM
              </button>
              <button
                type="button"
                class="timepicker-option"
                class:selected={!isAM}
                onclick={togglePeriod}
              >
                PM
              </button>
            </div>
          </div>
        {/if}
      </div>
    </div>
  {/if}

  {#if error}
    <p class="timepicker-error-message" role="alert">
      {error}
    </p>
  {/if}
</div>

<style>
  .timepicker-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
  }

  .timepicker-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    margin-bottom: 0.5rem;
  }

  .timepicker-input-wrapper {
    position: relative;
  }

  .timepicker {
    width: 100%;
    padding: 0.75rem 3rem 0.75rem 1rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .timepicker:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .timepicker:hover:not(.timepicker-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .timepicker-open {
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Variants */
  .timepicker-filled {
    background: var(--gray-50, #f9fafb);
    border-color: var(--gray-300, #d1d5db);
  }

  .timepicker-outlined {
    border-width: 2px;
  }

  /* Sizes */
  .timepicker-sm {
    padding: 0.5rem 2.5rem 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .timepicker-lg {
    padding: 1rem 3.5rem 1rem 1.25rem;
    font-size: 1.125rem;
  }

  /* States */
  .timepicker-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--gray-50, #f3f4f6);
  }

  .timepicker-error {
    border-color: var(--error-500, #ef4444);
  }

  .timepicker-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    color: var(--text-secondary, #6b7280);
  }

  .timepicker-toggle:hover {
    color: var(--text-primary, #111827);
  }

  /* Dropdown */
  .timepicker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    margin-top: 0.25rem;
    background: var(--bg-primary, white);
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 1rem;
  }

  .timepicker-content {
    display: flex;
    gap: 1rem;
  }

  .timepicker-section {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .timepicker-section-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary, #6b7280);
    margin-bottom: 0.5rem;
  }

  .timepicker-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.25rem;
    max-height: 200px;
    overflow-y: auto;
  }

  .timepicker-option {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border: 1px solid var(--border-light, #f0f0f0);
    border-radius: 4px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
  }

  .timepicker-option:hover {
    background: var(--gray-50, #f9fafb);
    border-color: var(--primary-500, #3b82f6);
  }

  .timepicker-option.selected {
    background: var(--primary-500, #3b82f6);
    color: white;
    border-color: var(--primary-500, #3b82f6);
  }

  .timepicker-period {
    display: flex;
    gap: 0.25rem;
  }

  .timepicker-error-message {
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
    color: var(--error-500, #ef4444);
    font-weight: 500;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .timepicker-dropdown {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      margin: 0;
      border-radius: 0;
      max-height: 50vh;
      overflow-y: auto;
    }

    .timepicker-content {
      flex-direction: column;
      gap: 0.5rem;
    }

    .timepicker-grid {
      grid-template-columns: repeat(6, 1fr);
      max-height: 120px;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .timepicker {
      border-width: 2px;
    }

    .timepicker-outlined {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .timepicker,
    .timepicker-option {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .timepicker-filled {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .timepicker-dropdown {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .timepicker-option:hover {
      background: var(--gray-700, #374151);
    }

    .timepicker-option.selected {
      background: var(--primary-600, #2563eb);
    }
  }
</style>