<script lang="ts">
  import { onMount } from 'svelte';
  import { slide } from 'svelte/transition';

  interface CalendarDay {
    date: Date;
    day: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    isSelected: boolean;
    isDisabled: boolean;
  }

  export let value: Date | null = null;
  export let min: Date | null = null;
  export let max: Date | null = null;
  export let disabled = false;
  export let placeholder = 'Select date';
  export const format = 'YYYY-MM-DD';
  export const locale = 'en';
  export let showTime = false;
  export const timeFormat = 'HH:mm';

  let root: HTMLElement;

  let showCalendar = false;
  let currentMonth = new Date();
  let selectedDate = value ? new Date(value) : null;
  let inputValue = '';

  $: days = generateCalendarDays(currentMonth);
  $: monthName = currentMonth.toLocaleDateString(locale, { month: 'long', year: 'numeric' });

  onMount(() => {
    if (value) {
      selectedDate = new Date(value);
      inputValue = formatDate(selectedDate);
    }
  });

  function generateCalendarDays(date: Date): CalendarDay[] {
    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    const days: CalendarDay[] = [];

    for (let i = 0; i < 42; i++) {
      const day = new Date(startDate);
      day.setDate(startDate.getDate() + i);

      days.push({
        date: day,
        day: day.getDate(),
        isCurrentMonth: day.getMonth() === month,
        isToday: !!isSameDay(day, new Date()),
        isSelected: !!(selectedDate && isSameDay(day, selectedDate)),
        isDisabled: isDateDisabled(day),
      });
    }

    return days;
  }

  function isSameDay(date1: Date, date2: Date) {
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
  }

  function isDateDisabled(date: Date) {
    if (min && date < new Date(min)) return true;
    if (max && date > new Date(max)) return true;
    return false;
  }

  function formatDate(date: Date | null) {
    if (!date) return '';

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

    try {
      return new Date(dateString);
    } catch {
      return null;
    }
  }

  function previousMonth() {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
  }

  function nextMonth() {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
  }

  function selectDate(day: CalendarDay) {
    if (day.isDisabled || !day.isCurrentMonth) return;

    selectedDate = day.date;
    inputValue = formatDate(selectedDate);
    showCalendar = false;

    // Dispatch change event
    root?.dispatchEvent(new CustomEvent('change', {
      detail: { value: selectedDate, formatted: inputValue },
      bubbles: true,
      cancelable: true
    }));
  }

  function handleInputChange(event: Event) {
    const target = event.target as HTMLInputElement;
    inputValue = target.value;
    const parsed = parseDate(inputValue);

    if (parsed && !isDateDisabled(parsed)) {
      selectedDate = parsed;
      // Dispatch change event
      root?.dispatchEvent(new CustomEvent('change', {
        detail: { value: selectedDate, formatted: inputValue },
        bubbles: true,
        cancelable: true
      }));
    }
  }

  function toggleCalendar() {
    if (!disabled) {
      showCalendar = !showCalendar;
    }
  }

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      showCalendar = false;
    }
  }
</script>

<svelte:window onkeydown={handleKeydown} />

<div class="date-picker" class:disabled bind:this={root}>
  <div class="input-container">
    <input
      type="text"
      {placeholder}
      {disabled}
      bind:value={inputValue}
      oninput={handleInputChange}
      onfocus={toggleCalendar}
      class="date-input"
    />
    <button
      type="button"
      class="calendar-toggle"
      {disabled}
      onclick={toggleCalendar}
      aria-label="Toggle calendar"
    >
      📅
    </button>
  </div>

  {#if showCalendar}
    <div class="calendar-dropdown" transition:slide={{ duration: 200 }}>
      <div class="calendar-header">
        <button
          type="button"
          class="nav-button"
          onclick={previousMonth}
          aria-label="Previous month"
        >
          ‹
        </button>
        <span class="month-year">{monthName}</span>
        <button
          type="button"
          class="nav-button"
          onclick={nextMonth}
          aria-label="Next month"
        >
          ›
        </button>
      </div>

      <div class="calendar-grid">
        <div class="weekday-header">
          {#each ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as day}
            <div class="weekday">{day}</div>
          {/each}
        </div>

        <div class="days-grid">
          {#each days as day}
            <button
              type="button"
              class="day-button"
              class:current-month={day.isCurrentMonth}
              class:today={day.isToday}
              class:selected={day.isSelected}
              class:disabled={day.isDisabled}
              disabled={day.isDisabled}
              onclick={() => selectDate(day)}
            >
              {day.day}
            </button>
          {/each}
        </div>
      </div>

      {#if showTime && selectedDate}
        <div class="time-picker">
          <label for="calendar-time">Time:</label>
          <input
            id="calendar-time"
            type="time"
            value={`${String(selectedDate.getHours()).padStart(2, '0')}:${String(selectedDate.getMinutes()).padStart(2, '0')}`}
            oninput={(e: Event) => {
              const target = e.target as HTMLInputElement;
              const [hours, minutes] = target.value.split(':');
              if (selectedDate) {
                selectedDate.setHours(parseInt(hours), parseInt(minutes));
                inputValue = formatDate(selectedDate);
                root?.dispatchEvent(new CustomEvent('change', {
                  detail: { value: selectedDate, formatted: inputValue },
                  bubbles: true,
                  cancelable: true
                }));
              }
            }}
            class="time-input"
          />
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .date-picker {
    position: relative;
    display: inline-block;
    width: 100%;
  }

  .date-picker.disabled {
    opacity: 0.6;
    pointer-events: none;
  }

  .input-container {
    position: relative;
    display: flex;
  }

  .date-input {
    flex: 1;
    padding: 0.5rem 2.5rem 0.5rem 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--bg-input, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .date-input:focus {
    outline: none;
    border-color: var(--focus-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .calendar-toggle {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: color 0.2s ease;
  }

  .calendar-toggle:hover {
    color: var(--text-primary, #111827);
    background: var(--bg-hover, #f3f4f6);
  }

  .calendar-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    margin-top: 0.25rem;
    background: var(--bg-dropdown, #ffffff);
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    padding: 1rem;
  }

  .calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .month-year {
    font-weight: 600;
    color: var(--text-primary, #111827);
  }

  .nav-button {
    background: none;
    border: none;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: color 0.2s ease, background 0.2s ease;
    font-size: 1.25rem;
  }

  .nav-button:hover {
    color: var(--text-primary, #111827);
    background: var(--bg-hover, #f3f4f6);
  }

  .calendar-grid {
    display: flex;
    flex-direction: column;
  }

  .weekday-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
    margin-bottom: 0.5rem;
  }

  .weekday {
    padding: 0.5rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary, #6b7280);
    text-transform: uppercase;
  }

  .days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
  }

  .day-button {
    padding: 0.5rem;
    border: none;
    background: none;
    color: var(--text-primary, #111827);
    cursor: pointer;
    border-radius: 0.375rem;
    transition: background 0.2s ease, color 0.2s ease;
    font-size: 0.875rem;
  }

  .day-button:hover:not(:disabled) {
    background: var(--bg-hover, #f3f4f6);
  }

  .day-button.current-month.today {
    background: var(--bg-today, #dbeafe);
    color: var(--text-today, #1d4ed8);
    font-weight: 600;
  }

  .day-button.selected {
    background: var(--bg-selected, #3b82f6);
    color: var(--text-selected, #ffffff);
  }

  .day-button.disabled {
    color: var(--text-disabled, #9ca3af);
    cursor: not-allowed;
  }

  .day-button:not(.current-month) {
    color: var(--text-muted, #d1d5db);
  }

  .time-picker {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .time-picker label {
    font-size: 0.875rem;
    color: var(--text-primary, #111827);
  }

  .time-input {
    padding: 0.25rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    background: var(--bg-input, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .date-input {
      background: var(--bg-input-dark, #1f2937);
      border-color: var(--border-color-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }

    .calendar-dropdown {
      background: var(--bg-dropdown-dark, #1f2937);
      border-color: var(--border-color-dark, #374151);
    }

    .calendar-toggle {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .calendar-toggle:hover {
      color: var(--text-primary-dark, #f9fafb);
      background: var(--bg-hover-dark, #374151);
    }

    .nav-button {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .nav-button:hover {
      color: var(--text-primary-dark, #f9fafb);
      background: var(--bg-hover-dark, #374151);
    }

    .weekday {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .day-button {
      color: var(--text-primary-dark, #f9fafb);
    }

    .day-button:hover:not(:disabled) {
      background: var(--bg-hover-dark, #374151);
    }

    .day-button.current-month.today {
      background: var(--bg-today-dark, #1e3a8a);
      color: var(--text-today-dark, #93c5fd);
    }

    .day-button.selected {
      background: var(--bg-selected-dark, #2563eb);
    }

    .time-input {
      background: var(--bg-input-dark, #1f2937);
      border-color: var(--border-color-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .date-input {
      border: 2px solid;
    }

    .calendar-dropdown {
      border: 2px solid;
    }

    .day-button.selected {
      border: 2px solid var(--text-selected, #ffffff);
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .calendar-dropdown {
      transition: none;
    }

    .day-button,
    .nav-button,
    .calendar-toggle {
      transition: none;
    }
  }
</style>