<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy, tick } from 'svelte';

  interface Props {
    value?: string | string[];
    options?: Array<{ value: string; label: string; description?: string }>;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    multiple?: boolean;
    searchable?: boolean;
    clearable?: boolean;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'default' | 'filled' | 'outlined';
    loading?: boolean;
    error?: string;
    label?: string;
    description?: string;
  }

  let {
    value = $bindable(''),
    options = [],
    placeholder = 'Select...',
    disabled = false,
    required = false,
    multiple = false,
    searchable = false,
    clearable = false,
    size = 'md',
    variant = 'default',
    loading = false,
    error = '',
    label = '',
    description = ''
  }: Props = $props();

  const dispatch = createEventDispatcher();

  let selectElement: HTMLElement;
  let dropdownElement: HTMLElement;
  let isOpen = $state(false);
  let searchQuery = $state('');
  let filteredOptions = $state(options);
  let selectedIndex = $state(-1);
  let selectId = `select-${Math.random().toString(36).substr(2, 9)}`;
  let mounted = $state(false);
  // Internal value state - sync from prop on mount, then uncontrolled
  let internalValue = $state(value);

  // Sync value from props only on mount
  $effect(() => {
    if (!mounted) {
      internalValue = value;
      mounted = true;
    }
  });

  const classes = $derived([
    'select',
    `select-${size}`,
    `select-${variant}`,
    isOpen ? 'select-open' : '',
    disabled ? 'select-disabled' : '',
    error ? 'select-error' : '',
    loading ? 'select-loading' : ''
  ].filter(Boolean).join(' '));

  const wrapperClasses = $derived([
    'select-wrapper',
    disabled ? 'select-wrapper-disabled' : '',
    error ? 'select-wrapper-error' : ''
  ].filter(Boolean).join(' '));

  const selectedOption = $derived(options.find(opt => opt.value === internalValue));
  
  const displayValue = $derived(selectedOption 
    ? selectedOption.label 
    : (multiple && Array.isArray(internalValue) && internalValue.length 
      ? `${internalValue.length} selected` 
      : placeholder));

  $effect(() => {
    if (searchable && searchQuery) {
      filteredOptions = options.filter(opt =>
        opt.label.toLowerCase().includes(searchQuery.toLowerCase())
      );
    } else {
      filteredOptions = options;
    }
  });

  async function toggleDropdown() {
    if (!disabled && !loading) {
      isOpen = !isOpen;
      if (isOpen) {
        selectedIndex = -1;
        searchQuery = '';
        await tick();
        if (searchable && dropdownElement) {
          const input = dropdownElement.querySelector('input');
          if (input) input.focus();
        }
      }
    }
  }

  function selectOption(option: { value: string; label: string; description?: string }) {
    if (multiple) {
      const currentValues = Array.isArray(internalValue) ? internalValue : [];
      const isSelected = currentValues.includes(option.value);
      if (isSelected) {
        internalValue = currentValues.filter(v => v !== option.value);
      } else {
        internalValue = [...currentValues, option.value];
      }
    } else {
      internalValue = option.value;
      isOpen = false;
    }
    dispatch('change', { value: internalValue, option });
  }

  function handleClearClick(event: MouseEvent) {
    event.stopPropagation();
    clearSelection();
  }

  function handleClearKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' || event.key === ' ') {
      clearSelection();
      event.preventDefault();
    }
  }

  function clearSelection() {
    internalValue = multiple ? [] : '';
    dispatch('clear');
  }

  function handleKeydown(event: KeyboardEvent) {
    if (!isOpen) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length - 1);
        break;
      case 'ArrowUp':
        event.preventDefault();
        selectedIndex = Math.max(selectedIndex - 1, -1);
        break;
      case 'Enter':
        event.preventDefault();
        if (selectedIndex >= 0) {
          selectOption(filteredOptions[selectedIndex]);
        }
        break;
      case 'Escape':
        event.preventDefault();
        isOpen = false;
        break;
    }
  }

  function handleSearch(event: Event) {
    const target = event.target as HTMLInputElement;
    searchQuery = target.value;
  }

  // Close dropdown when clicking outside
  onMount(() => {
    function handleClickOutside(event: MouseEvent) {
      if (selectElement && !selectElement.contains(event.target as Node)) {
        isOpen = false;
      }
    }

    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });
</script>

<div class={wrapperClasses} bind:this={selectElement}>
  {#if label}
    <label for={selectId} class="select-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}

  <div class="select-container">
    <button
      type="button"
      id={selectId}
      class={classes}
      {disabled}
      aria-expanded={isOpen}
      aria-haspopup="listbox"
      aria-describedby={description || error ? `${selectId}-description` : undefined}
      onclick={toggleDropdown}
      onkeydown={handleKeydown}
    >
      <span class="select-value">{displayValue}</span>

      <span
          class="select-clear"
          onclick={handleClearClick}
          onkeydown={handleClearKeydown}
          role="button"
          tabindex="0"
          aria-label="Clear selection"
        >
          ✕
        </span>

      {#if loading}
        <span class="select-spinner" aria-hidden="true">⟳</span>
      {:else}
        <span class="select-arrow" aria-hidden="true">
          {isOpen ? '▲' : '▼'}
        </span>
      {/if}
    </button>

    {#if isOpen}
      <div
        bind:this={dropdownElement}
        class="select-dropdown"
        role="listbox"
        aria-labelledby={selectId}
      >
        {#if searchable}
          <div class="select-search">
            <input
              type="text"
              placeholder="Search..."
              value={searchQuery}
              oninput={handleSearch}
              class="select-search-input"
            />
          </div>
        {/if}

        <div class="select-options">
          {#each filteredOptions as option, index}
            {@const isSelected = multiple ? (Array.isArray(internalValue) && internalValue.includes(option.value)) : internalValue === option.value}
            <div
              class="select-option"
              class:selected={isSelected}
              class:highlighted={index === selectedIndex}
              role="option"
              aria-selected={isSelected}
              tabindex="0"
              onclick={() => selectOption(option)}
              onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { selectOption(option); e.preventDefault(); } }}
            >
              {#if multiple}
                <span class="select-checkbox" aria-hidden="true">
                  {isSelected ? '☑' : '☐'}
                </span>
              {/if}
              <span class="select-option-label">{option.label}</span>
              {#if option.description}
                <span class="select-option-description">{option.description}</span>
              {/if}
            </div>
          {/each}

          {#if filteredOptions.length === 0}
            <div class="select-no-options">
              No options found
            </div>
          {/if}
        </div>
      </div>
    {/if}
  </div>

  {#if description}
    <p id={`${selectId}-description`} class="select-description">
      {description}
    </p>
  {/if}

  {#if error}
    <p id={`${selectId}-description`} class="select-error-message" role="alert">
      {error}
    </p>
  {/if}
</div>

<style>
  .select-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
  }

  .select-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    margin-bottom: 0.5rem;
  }

  .select-container {
    position: relative;
  }

  .select {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s ease;
    text-align: left;
  }

  .select:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .select:hover:not(.select-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .select-open {
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  /* Variants */
  .select-filled {
    background: var(--gray-50, #f9fafb);
    border-color: var(--gray-300, #d1d5db);
  }

  .select-outlined {
    border-width: 2px;
  }

  /* Sizes */
  .select-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
  }

  .select-lg {
    padding: 1rem 1.25rem;
    font-size: 1.125rem;
  }

  /* States */
  .select-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--gray-50, #f9fafb);
  }

  .select-error {
    border-color: var(--error-500, #ef4444);
  }

  .select-wrapper-error .select {
    border-color: var(--error-500, #ef4444);
  }

  .select-loading {
    pointer-events: none;
  }

  .select-value {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .select-clear,
  .select-arrow,
  .select-spinner {
    flex-shrink: 0;
    margin-left: 0.5rem;
  }

  .select-clear {
    background: none;
    border: none;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: color 0.2s ease;
  }

  .select-clear:hover {
    color: var(--text-primary, #111827);
    background: var(--gray-100, #f3f4f6);
  }

  .select-spinner {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* Dropdown */
  .select-dropdown {
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
    max-height: 200px;
    overflow: hidden;
  }

  .select-search {
    padding: 0.5rem;
    border-bottom: 1px solid var(--border-light, #f0f0f0);
  }

  .select-search-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    font-size: 0.875rem;
  }

  .select-options {
    max-height: 160px;
    overflow-y: auto;
  }

  .select-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .select-option:hover,
  .select-option.highlighted {
    background: var(--gray-50, #f9fafb);
  }

  .select-option.selected {
    background: var(--primary-50, #eff6ff);
    color: var(--primary-700, #1d4ed8);
  }

  .select-checkbox {
    font-size: 0.875rem;
  }

  .select-option-label {
    flex: 1;
    font-weight: 500;
  }

  .select-option-description {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    margin-left: auto;
  }

  .select-no-options {
    padding: 1rem;
    text-align: center;
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
  }

  /* Description and error */
  .select-description {
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }

  .select-error-message {
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
  @media (max-width: 768px) {
    .select-dropdown {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      margin: 0;
      border-radius: 0;
      max-height: 50vh;
    }

    .select-options {
      max-height: calc(50vh - 60px);
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .select {
      border-width: 2px;
    }

    .select-outlined {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .select,
    .select-option,
    .select-clear {
      transition: none;
    }

    .select-spinner {
      animation: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .select-filled {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .select-dropdown {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .select-option:hover,
    .select-option.highlighted {
      background: var(--gray-700, #374151);
    }

    .select-option.selected {
      background: var(--primary-900, #1e3a8a);
      color: var(--primary-200, #bfdbfe);
    }
  }
</style>