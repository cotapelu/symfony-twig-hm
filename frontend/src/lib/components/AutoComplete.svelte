<script lang="ts">
  import { createEventDispatcher, onMount, tick } from 'svelte';

  export let value: string = '';
  export let options: Array<{ value: string; label: string; description?: string; icon?: string }> = [];
  export let placeholder = 'Search...';
  export let disabled = false;
  export let loading = false;
  export let multiple = false;
  export let maxSuggestions = 10;
  export let filterFunction: ((option: { value: string; label: string; description?: string }, query: string) => boolean) | null = null;
  export let size: 'sm' | 'md' | 'lg' = 'md';
  export let variant: 'default' | 'filled' | 'outlined' = 'default';

  const dispatch = createEventDispatcher();

  let inputElement: HTMLInputElement;
  let dropdownElement: HTMLElement;
  let isOpen = false;
  let selectedIndex = -1;
  let filteredOptions: Array<{ value: string; label: string; description?: string; icon?: string }> = [];
  let selectedValues: Array<{ value: string; label: string; description?: string; icon?: string }> | null = multiple ? [] : null;

  $: if (multiple) {
    selectedValues = Array.isArray(value) ? value as unknown as Array<{ value: string; label: string; description?: string; icon?: string }> : [];
  }

  $: classes = [
    'autocomplete',
    `autocomplete-${size}`,
    `autocomplete-${variant}`,
    isOpen ? 'autocomplete-open' : '',
    disabled ? 'autocomplete-disabled' : '',
    loading ? 'autocomplete-loading' : ''
  ].filter(Boolean).join(' ');

  $: wrapperClasses = [
    'autocomplete-wrapper',
    disabled ? 'autocomplete-wrapper-disabled' : ''
  ].filter(Boolean).join(' ');

  function filterOptions(query: string) {
    if (!query) return options.slice(0, maxSuggestions);

    const filter = filterFunction || ((option: { value: string; label: string; description?: string }, q: string) =>
      option.label.toLowerCase().includes(q.toLowerCase())
    );

    return options
      .filter(option => filter(option, query))
      .slice(0, maxSuggestions);
  }

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const query = target.value;
    value = query;
    filteredOptions = filterOptions(query);
    selectedIndex = -1;
    isOpen = filteredOptions.length > 0;
    dispatch('input', { value: query });
  }

  function handleFocus() {
    if (!disabled && !loading) {
      filteredOptions = filterOptions(value);
      isOpen = filteredOptions.length > 0;
    }
  }

  function handleBlur() {
    setTimeout(() => {
      isOpen = false;
      selectedIndex = -1;
    }, 150);
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
        selectedIndex = -1;
        break;
    }
  }

  function selectOption(option: { value: string; label: string; description?: string; icon?: string }) {
    if (multiple) {
      const currentValues = selectedValues || [];
      const isSelected = currentValues.some(v => v.value === option.value);

      if (isSelected) {
        selectedValues = currentValues.filter(v => v.value !== option.value);
      } else {
        selectedValues = [...currentValues, option];
      }

      value = selectedValues as unknown as string;
      inputElement.value = (selectedValues as Array<{ label: string }>).map(v => v.label).join(', ');
    } else {
      value = option.value;
      inputElement.value = option.label;
      isOpen = false;
    }

    dispatch('select', { option, value });
  }

  function removeTag(option: { value: string; label: string; description?: string; icon?: string }) {
    if (multiple && selectedValues) {
      selectedValues = selectedValues.filter(v => v.value !== option.value);
      value = selectedValues as unknown as string;
      inputElement.value = selectedValues.map(v => v.label).join(', ');
      dispatch('remove', { option });
    }
  }

  onMount(() => {
    function handleClickOutside(event: MouseEvent) {
      if (inputElement && !inputElement.contains(event.target as Node) &&
          dropdownElement && !dropdownElement.contains(event.target as Node)) {
        isOpen = false;
      }
    }

    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
  });
</script>

<div class={wrapperClasses}>
  {#if multiple && selectedValues && selectedValues.length > 0}
    <div class="autocomplete-tags">
      {#each selectedValues as tag}
        <span class="autocomplete-tag">
          {tag.label}
          <button
            type="button"
            class="autocomplete-tag-remove"
            onclick={() => removeTag(tag)}
            aria-label="Remove {tag.label}"
          >
            ✕
          </button>
        </span>
      {/each}
    </div>
  {/if}

  <div class="autocomplete-input-wrapper">
    <input
      bind:this={inputElement}
      type="text"
      {placeholder}
      {disabled}
      class={classes}
      oninput={handleInput}
      onfocus={handleFocus}
      onblur={handleBlur}
      onkeydown={handleKeydown}
      autocomplete="off"
      role="combobox"
      aria-expanded={isOpen}
      aria-controls="autocomplete-list"
      aria-haspopup="listbox"
      aria-autocomplete="list"
    />

    {#if loading}
      <span class="autocomplete-spinner" aria-hidden="true">⟳</span>
    {:else}
      <span class="autocomplete-arrow" aria-hidden="true">
        {isOpen ? '▲' : '▼'}
      </span>
    {/if}
  </div>

  {#if isOpen}
    <div
      bind:this={dropdownElement}
      class="autocomplete-dropdown"
      role="listbox"
      id="autocomplete-list"
    >
      {#each filteredOptions as option, index}
        <div
          class="autocomplete-option"
          class:selected={index === selectedIndex}
          role="option"
          aria-selected={index === selectedIndex}
          tabindex="0"
          onclick={() => selectOption(option)}
          onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { selectOption(option); e.preventDefault(); } }}
        >
          {#if option.icon}
            <span class="autocomplete-option-icon">{option.icon}</span>
          {/if}
          <span class="autocomplete-option-label">{option.label}</span>
          {#if option.description}
            <span class="autocomplete-option-description">{option.description}</span>
          {/if}
        </div>
      {/each}

      {#if filteredOptions.length === 0}
        <div class="autocomplete-no-options">
          No options found
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .autocomplete-wrapper {
    position: relative;
    width: 100%;
  }

  .autocomplete-wrapper-disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .autocomplete-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }

  .autocomplete-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    background: var(--primary-100, #e0f2fe);
    color: var(--primary-800, #0c4a6e);
    border-radius: 4px;
    font-size: 0.875rem;
  }

  .autocomplete-tag-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    background: none;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    transition: opacity 0.2s ease;
  }

  .autocomplete-tag-remove:hover {
    opacity: 1;
  }

  .autocomplete-input-wrapper {
    position: relative;
  }

  .autocomplete {
    width: 100%;
    padding: 0.75rem 1rem;
    padding-right: 3rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    color: var(--text-primary, #111827);
    font-size: 1rem;
    transition: all 0.2s ease;
  }

  .autocomplete:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .autocomplete:hover:not(.autocomplete-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  /* Variants */
  .autocomplete-filled {
    background: var(--gray-50, #f9fafb);
    border-color: var(--gray-300, #d1d5db);
  }

  .autocomplete-outlined {
    border-width: 2px;
  }

  /* Sizes */
  .autocomplete-sm {
    padding: 0.5rem 0.75rem;
    padding-right: 2.5rem;
    font-size: 0.875rem;
  }

  .autocomplete-lg {
    padding: 1rem 1.25rem;
    padding-right: 3.5rem;
    font-size: 1.125rem;
  }

  /* States */
  .autocomplete-disabled {
    cursor: not-allowed;
    background: var(--gray-50, #f3f4f6);
  }

  .autocomplete-loading {
    padding-right: 3rem;
  }

  .autocomplete-spinner,
  .autocomplete-arrow {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
  }

  .autocomplete-spinner {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from { transform: translateY(-50%) rotate(0deg); }
    to { transform: translateY(-50%) rotate(360deg); }
  }

  /* Dropdown */
  .autocomplete-dropdown {
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
    overflow-y: auto;
  }

  .autocomplete-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .autocomplete-option:hover,
  .autocomplete-option.selected {
    background: var(--gray-50, #f9fafb);
  }

  .autocomplete-option-icon {
    flex-shrink: 0;
    font-size: 1rem;
  }

  .autocomplete-option-label {
    flex: 1;
    font-weight: 500;
  }

  .autocomplete-option-description {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }

  .autocomplete-no-options {
    padding: 1rem;
    text-align: center;
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .autocomplete-dropdown {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      margin: 0;
      border-radius: 0;
      max-height: 50vh;
    }

    .autocomplete-tags {
      gap: 0.375rem;
    }

    .autocomplete-tag {
      font-size: 0.8125rem;
      padding: 0.1875rem 0.375rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .autocomplete {
      border-width: 2px;
    }

    .autocomplete-outlined {
      border-width: 3px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .autocomplete,
    .autocomplete-option,
    .autocomplete-spinner {
      transition: none;
    }

    .autocomplete-spinner {
      animation: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .autocomplete-filled {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .autocomplete-dropdown {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .autocomplete-option:hover,
    .autocomplete-option.selected {
      background: var(--gray-700, #374151);
    }

    .autocomplete-tag {
      background: var(--primary-900, #1e3a8a);
      color: var(--primary-200, #bfdbfe);
    }
  }
</style>