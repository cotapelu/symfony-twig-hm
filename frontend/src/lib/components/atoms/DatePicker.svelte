<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  export let value: Date | null = null;
  export let placeholder = 'Select date';
  export let disabled = false;
  export let required = false;
  export let size = 'md';
  export let variant = 'default';
  export const format = 'YYYY-MM-DD';
  export const locale = 'en';
  export let minDate: Date | null = null;
  export let maxDate: Date | null = null;
  export let disabledDates: Date[] = [];
  export let showTime = false;
  export const timeFormat = 'HH:mm';
  export let label = '';
  export let error = '';

  const dispatch = createEventDispatcher();

  let inputElement: HTMLInputElement;
  let calendarElement: HTMLElement;
  let isOpen = false;
  let currentMonth = new Date();
  let selectedDate = value ? new Date(value) : null;
  let inputValue = '';

  $: if (selectedDate) {
    inputValue = formatDate(selectedDate);
  } else {
    inputValue = '';
  }

  $: daysInMonth = getDaysInMonth(currentMonth);
  $: firstDayOfMonth = getFirstDayOfMonth(currentMonth);
  $: calendarDays = generateCalendarDays();

  function formatDate(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    if (showTime) {
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      return `${year}-${month}-${day} ${hours}:${minutes}`;
    }

    return `${year}-${month}-${day}`;
  }

  function parseDate(dateString: string): Date | null {
    if (!dateString) return null;

    if (showTime) {
      const [datePart, timePart] = dateString.split(' ');
      const [year, month, day] = datePart.split('-').map(Number);
      const [hours, minutes] = timePart.split(':').map(Number);
      return new Date(year, month - 1, day, hours, minutes);
    } else {
      const [year, month, day] = dateString.split('-').map(Number);
      return new Date(year, month - 1, day);
    }
  }

  function getDaysInMonth(date: Date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    return new Date(year, month + 1, 0).getDate();
  }

  function getFirstDayOfMonth(date: Date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    return new Date(year, month, 1).getDay();
  }

  interface CalendarDay {
    date: Date;
    isCurrentMonth: boolean;
    isSelected: boolean;
    isDisabled: boolean;
    isToday: boolean;
  }

  function generateCalendarDays(): CalendarDay[] {
    const days: CalendarDay[] = [];
    const totalCells = 42; // 6 weeks * 7 days

    // Previous month days
    const prevMonthDays = firstDayOfMonth;
    const prevMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1);
    const prevMonthLastDay = getDaysInMonth(prevMonth);

    for (let i = prevMonthDays - 1; i >= 0; i--) {
      days.push({
        date: new Date(prevMonth.getFullYear(), prevMonth.getMonth(), prevMonthLastDay - i),
        isCurrentMonth: false,
        isSelected: false,
        isDisabled: true,
        isToday: false
      });
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
      const isSelected = selectedDate &&
        date.getDate() === selectedDate.getDate() &&
        date.getMonth() === selectedDate.getMonth() &&
        date.getFullYear() === selectedDate.getFullYear();

      const isToday = new Date().toDateString() === date.toDateString();

      const isDisabled = (minDate && date < minDate) ||
                        (maxDate && date > maxDate) ||
                        disabledDates.some(d => d.toDateString() === date.toDateString());

      days.push({
        date,
        isCurrentMonth: true as const,
        isSelected: !!isSelected,
        isDisabled: !!isDisabled,
        isToday
      });
    }

    // Next month days
    const remainingCells = totalCells - days.length;
    for (let day = 1; day <= remainingCells; day++) {
      days.push({
        date: new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, day),
        isCurrentMonth: false,
        isSelected: false,
        isDisabled: true,
        isToday: false
      });
    }

    return days;
  }

  function toggleCalendar() {
    if (!disabled) {
      isOpen = !isOpen;
    }
  }

  function selectDate(date: CalendarDay) {
    if (date.isDisabled) return;

    selectedDate = date.date;
    value = selectedDate;
    inputValue = formatDate(selectedDate);
    isOpen = false;
    dispatch('change', { value: selectedDate, formatted: inputValue });
  }

  function navigateMonth(direction: number) {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + direction, 1);
  }

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const inputDate = parseDate(target.value);
    if (inputDate) {
      selectedDate = inputDate;
      value = inputDate;
      currentMonth = new Date(inputDate.getFullYear(), inputDate.getMonth(), 1);
      dispatch('change', { value: inputDate, formatted: target.value });
    }
  }

  function handleInputBlur() {
    if (inputValue) {
      const parsed = parseDate(inputValue);
      if (parsed) {
        selectedDate = parsed;
        value = parsed;
        inputValue = formatDate(parsed);
      }
    }
  }

  onMount(() => {
    function handleClickOutside(event: MouseEvent) {
      if (inputElement && !inputElement.contains(event.target as Node) &&
          calendarElement && !calendarElement.contains(event.target as Node)) {
        isOpen = false;
      }
    }

    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });

  $: classes = [
    'datepicker',
    `datepicker-${size}`,
    `datepicker-${variant}`,
    isOpen ? 'datepicker-open' : '',
    disabled ? 'datepicker-disabled' : '',
    error ? 'datepicker-error' : ''
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'datepicker-wrapper',
    disabled ? 'datepicker-wrapper-disabled' : ''
  ].filter(Boolean).join(' ');
</script>

<div class={wrapperClasses}>
  {#if label}
    <label for="datepicker-input" class="datepicker-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}

  <div class="datepicker-input-wrapper">
    <input
      bind:this={inputElement}
      id="datepicker-input"
      type="text"
      {placeholder}
      bind:value={inputValue}
      {disabled}
      {required}
      class={classes}
      onclick={toggleCalendar}
      oninput={handleInput}
      onblur={handleInputBlur}
      aria-haspopup="dialog"
      autocomplete="off"
    />

    <button
      type="button"
      class="datepicker-toggle"
      onclick={toggleCalendar}
      disabled={disabled}
      aria-label="Toggle calendar"
    >
      📅
    </button>
  </div>

  {#if isOpen}
    <div bind:this={calendarElement} class="datepicker-calendar" role="dialog" aria-modal="true">
      <div class="datepicker-header">
        <button
          type="button"
          class="datepicker-nav"
          onclick={() => navigateMonth(-1)}
          aria-label="Previous month"
        >
          ‹
        </button>

        <div class="datepicker-month-year">
          {currentMonth.toLocaleDateString(locale, { year: 'numeric', month: 'long' })}
        </div>

        <button
          type="button"
          class="datepicker-nav"
          onclick={() => navigateMonth(1)}
          aria-label="Next month"
        >
          ›
        </button>
      </div>

      <div class="datepicker-weekdays">
        {#each ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as day}
          <div class="datepicker-weekday">{day}</div>
        {/each}
      </div>

      <div class="datepicker-days" role="grid">
        {#each calendarDays as day, index}
          <button
            type="button"
            class="datepicker-day"
            class:current-month={day.isCurrentMonth}
            class:selected={day.isSelected}
            class:disabled={day.isDisabled}
            class:today={day.isToday}
            disabled={day.isDisabled}
            onclick={() => selectDate(day)}
            aria-label={day.date.toDateString()}
          >
            {day.date.getDate()}
          </button>
        {/each}
      </div>

      {#if showTime && selectedDate}
        <div class="datepicker-time">
          <input
            type="time"
            value={`${String(selectedDate.getHours()).padStart(2, '0')}:${String(selectedDate.getMinutes()).padStart(2, '0')}`}
            oninput={(e) => {
              const target = e.target as HTMLInputElement;
              const [hours, minutes] = target.value.split(':').map(Number);
              selectedDate!.setHours(hours, minutes);
              value = selectedDate;
              dispatch('change', { value: selectedDate, formatted: formatDate(selectedDate!) });
            }}
            class="datepicker-time-input"
          />
        </div>
      {/if}
    </div>
  {/if}

  {#if error}
    <p class="datepicker-error-message" role="alert">
      {error}
    </p>
  {/if}
</div>

<style>
  .datepicker-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
  }

  .datepicker-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    margin-bottom: 0.5rem;
  }

  .datepicker-input-wrapper {
    position: relative;
  }

  .datepicker {
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

  .datepicker:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .datepicker:hover:not(.datepicker-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .datepicker-open {
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Variants */
  .datepicker-filled {
    background: var(--gray-50, #f9fafb);
    border-color: var(--gray-300, #d1d5db);
  }

  .datepicker-outlined {
    border-width: 2px;
  }

  /* Sizes */
  .datepicker-sm {
    padding: 0.5rem 2.5rem 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .datepicker-lg {
    padding: 1rem 3.5rem 1rem 1.25rem;
    font-size: 1.125rem;
  }

  /* States */
  .datepicker-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--gray-50, #f3f4f6);
  }

  .datepicker-error {
    border-color: var(--error-500, #ef4444);
  }

  .datepicker-toggle {
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

  .datepicker-toggle:hover {
    color: var(--text-primary, #111827);
  }

  /* Calendar */
  .datepicker-calendar {
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

  .datepicker-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .datepicker-nav {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    color: var(--text-secondary, #6b7280);
  }

  .datepicker-nav:hover {
    background: var(--gray-100, #f3f4f6);
    color: var(--text-primary, #111827);
  }

  .datepicker-month-year {
    font-weight: 600;
    color: var(--text-primary, #111827);
  }

  .datepicker-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
    margin-bottom: 0.5rem;
  }

  .datepicker-weekday {
    text-align: center;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary, #6b7280);
    padding: 0.5rem;
  }

  .datepicker-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
  }

  .datepicker-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 4px;
    background: none;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--text-primary, #111827);
    transition: all 0.2s ease;
  }

  .datepicker-day:hover:not(:disabled) {
    background: var(--gray-100, #f3f4f6);
  }

  .datepicker-day.selected {
    background: var(--primary-500, #3b82f6);
    color: white;
  }

  .datepicker-day.today {
    font-weight: 600;
    color: var(--primary-600, #2563eb);
  }

  .datepicker-day.current-month:not(.selected):not(.today) {
    color: var(--text-primary, #111827);
  }

  .datepicker-day:not(.current-month) {
    color: var(--text-secondary, #6b7280);
  }

  .datepicker-day:disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }

  .datepicker-time {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-light, #f0f0f0);
  }

  .datepicker-time-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
  }

  .datepicker-error-message {
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
    .datepicker-calendar {
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

    .datepicker-days {
      gap: 0.125rem;
    }

    .datepicker-day {
      font-size: 0.8125rem;
      padding: 0.5rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .datepicker {
      border-width: 2px;
    }

    .datepicker-outlined {
      border-width: 3px;
    }

    .datepicker-day.selected {
      border: 2px solid white;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .datepicker,
    .datepicker-day,
    .datepicker-nav {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .datepicker-filled {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .datepicker-calendar {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .datepicker-day:hover:not(:disabled) {
      background: var(--gray-700, #374151);
    }

    .datepicker-day.selected {
      background: var(--primary-600, #2563eb);
    }
  }
</style>