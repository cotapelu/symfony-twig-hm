<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  const dispatch = createEventDispatcher();

  export let filters: FilterGroup[] = [];
  export let appliedFilters: { [key: string]: any } = {};
  export let className: string = '';

  interface FilterOption {
    label: string;
    value: any;
    count?: number;
    disabled?: boolean;
  }

  interface FilterGroup {
    key: string;
    label: string;
    type: 'select' | 'multiselect' | 'range' | 'checkbox' | 'radio' | 'date' | 'daterange';
    options?: FilterOption[];
    placeholder?: string;
    min?: number;
    max?: number;
    step?: number;
    allowCustom?: boolean;
  }

  let expandedGroups: Set<string> = new Set();
  let customValues: { [key: string]: any } = {};

  $: classes = [
    'filter-panel',
    className
  ].filter(Boolean).join(' ');

  function toggleGroup(key: string) {
    if (expandedGroups.has(key)) {
      expandedGroups.delete(key);
    } else {
      expandedGroups.add(key);
    }
    expandedGroups = new Set(expandedGroups); // trigger reactivity
  }

  function handleFilterChange(groupKey: string, value: any) {
    const newFilters = { ...appliedFilters };

    if (Array.isArray(value) && value.length === 0) {
      delete newFilters[groupKey];
    } else if (value === null || value === undefined || value === '') {
      delete newFilters[groupKey];
    } else {
      newFilters[groupKey] = value;
    }

    dispatch('change', { filters: newFilters, changed: groupKey });
  }

  function clearFilter(groupKey: string) {
    handleFilterChange(groupKey, null);
  }

  function clearAllFilters() {
    dispatch('change', { filters: {}, changed: 'all' });
  }

  function applyCustomFilter(group: FilterGroup) {
    const value = customValues[group.key];
    if (value !== undefined && value !== '') {
      handleFilterChange(group.key, value);
    }
  }

  function renderFilterGroup(group: FilterGroup) {
    const isExpanded = expandedGroups.has(group.key);
    const hasValue = appliedFilters[group.key] !== undefined;

    return `
      <div class="filter-group">
        <button
          class="filter-group-header"
          onclick={() => toggleGroup(group.key)}
          aria-expanded="${isExpanded}"
        >
          <span class="filter-group-label">${group.label}</span>
          ${hasValue ? '<span class="filter-group-indicator">●</span>' : ''}
          <span class="filter-group-toggle">${isExpanded ? '−' : '+'}</span>
        </button>

        ${isExpanded ? `
          <div class="filter-group-content">
            ${renderFilterContent(group)}
            ${hasValue ? `
              <button class="filter-clear-btn" onclick={() => clearFilter(group.key)}>
                Clear
              </button>
            ` : ''}
          </div>
        ` : ''}
      </div>
    `;
  }

  function renderFilterContent(group: FilterGroup) {
    switch (group.type) {
      case 'select':
        return `
          <select
            class="filter-select"
            onchange={(e) => handleFilterChange(group.key, (e.target as HTMLSelectElement).value)}
            value="${appliedFilters[group.key] || ''}"
          >
            <option value="">${group.placeholder || 'Select...'}</option>
            ${group.options?.map(option => `
              <option
                value="${option.value}"
                ${option.disabled ? 'disabled' : ''}
              >
                ${option.label}${option.count !== undefined ? ` (${option.count})` : ''}
              </option>
            `).join('') || ''}
          </select>
        `;

      case 'multiselect':
        return `
          <div class="filter-multiselect">
            ${group.options?.map(option => `
              <label class="filter-checkbox">
                <input
                  type="checkbox"
                  value="${option.value}"
                  checked="${(appliedFilters[group.key] || []).includes(option.value)}"
                  onchange={(e) => {
                    const current = appliedFilters[group.key] || [];
                    const value = (e.target as HTMLInputElement).value;
                    let newValue;
                    if ((e.target as HTMLInputElement).checked) {
                      newValue = [...current, value];
                    } else {
                      newValue = current.filter(v => v !== value);
                    }
                    handleFilterChange(group.key, newValue);
                  }}
                  ${option.disabled ? 'disabled' : ''}
                />
                <span class="filter-checkbox-label">
                  ${option.label}${option.count !== undefined ? ` (${option.count})` : ''}
                </span>
              </label>
            `).join('') || ''}
          </div>
        `;

      case 'range':
        return `
          <div class="filter-range">
            <input
              type="range"
              class="filter-range-input"
              min="${group.min || 0}"
              max="${group.max || 100}"
              step="${group.step || 1}"
              value="${appliedFilters[group.key] || group.min || 0}"
              oninput={(e) => handleFilterChange(group.key, parseFloat((e.target as HTMLInputElement).value))}
            />
            <div class="filter-range-values">
              <span>${appliedFilters[group.key] || group.min || 0}</span>
              <span>(${group.min || 0} - ${group.max || 100})</span>
            </div>
          </div>
        `;

      case 'checkbox':
        return `
          <div class="filter-checkboxes">
            ${group.options?.map(option => `
              <label class="filter-checkbox">
                <input
                  type="checkbox"
                  checked="${appliedFilters[group.key] === option.value}"
                  onchange={(e) => handleFilterChange(group.key, (e.target as HTMLInputElement).checked ? option.value : null)}
                  ${option.disabled ? 'disabled' : ''}
                />
                <span class="filter-checkbox-label">
                  ${option.label}${option.count !== undefined ? ` (${option.count})` : ''}
                </span>
              </label>
            `).join('') || ''}
          </div>
        `;

      case 'radio':
        return `
          <div class="filter-radios">
            ${group.options?.map(option => `
              <label class="filter-radio">
                <input
                  type="radio"
                  name="filter-${group.key}"
                  value="${option.value}"
                  checked="${appliedFilters[group.key] === option.value}"
                  onchange={(e) => handleFilterChange(group.key, option.value)}
                  ${option.disabled ? 'disabled' : ''}
                />
                <span class="filter-radio-label">
                  ${option.label}${option.count !== undefined ? ` (${option.count})` : ''}
                </span>
              </label>
            `).join('') || ''}
          </div>
        `;

      case 'date':
        return `
          <input
            type="date"
            class="filter-date"
            value="${appliedFilters[group.key] || ''}"
            onchange={(e) => handleFilterChange(group.key, (e.target as HTMLInputElement).value)}
          />
        `;

      case 'daterange':
        return `
          <div class="filter-daterange">
            <input
              type="date"
              class="filter-date"
              placeholder="From"
              value="${(appliedFilters[group.key] || [])[0] || ''}"
              onchange={(e) => {
                const current = appliedFilters[group.key] || [];
                handleFilterChange(group.key, [(e.target as HTMLInputElement).value, current[1]]);
              }}
            />
            <input
              type="date"
              class="filter-date"
              placeholder="To"
              value="${(appliedFilters[group.key] || [])[1] || ''}"
              onchange={(e) => {
                const current = appliedFilters[group.key] || [];
                handleFilterChange(group.key, [current[0], (e.target as HTMLInputElement).value]);
              }}
            />
          </div>
        `;

      default:
        if (group.allowCustom) {
          return `
            <div class="filter-custom">
              <input
                type="text"
                class="filter-input"
                placeholder="${group.placeholder || 'Enter value...'}"
                bind:value={customValues[group.key]}
                onkeydown={(e) => e.key === 'Enter' && applyCustomFilter(group)}
              />
              <button
                class="filter-apply-btn"
                onclick={() => applyCustomFilter(group)}
                disabled={!customValues[group.key]?.trim()}
              >
                Apply
              </button>
            </div>
          `;
        }
        return '';
    }
  }
</script>

<div class={classes}>
  <div class="filter-header">
    <h3 class="filter-title">Filters</h3>
    {#if Object.keys(appliedFilters).length > 0}
      <button class="filter-clear-all" onclick={clearAllFilters}>
        Clear All
      </button>
    {/if}
  </div>

  <div class="filter-groups">
    {#each filters as group (group.key)}
      {@html DOMPurify.sanitize(renderFilterGroup(group))}
    {/each}
  </div>

  {#if Object.keys(appliedFilters).length > 0}
    <div class="filter-summary">
      <strong>Applied filters:</strong>
      <div class="filter-tags">
        {#each Object.entries(appliedFilters) as [key, value]}
          <span class="filter-tag">
            {filters.find(g => g.key === key)?.label || key}: {Array.isArray(value) ? value.join(', ') : value}
            <button onclick={() => clearFilter(key)}>×</button>
          </span>
        {/each}
      </div>
    </div>
  {/if}
</div>

<style>
  .filter-panel {
    width: 100%;
    max-width: 300px;
    padding: 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--background, #ffffff);
  }

  .filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color, #d1d5db);
  }

  .filter-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
  }

  .filter-clear-all {
    padding: 0.25rem 0.5rem;
    background: none;
    border: 1px solid var(--error-500, #ef4444);
    color: var(--error-500, #ef4444);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .filter-clear-all:hover {
    background: var(--error-50, #fef2f2);
  }

  .filter-groups {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-group {
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    overflow: hidden;
  }

  .filter-group-header {
    width: 100%;
    padding: 0.75rem;
    background: var(--gray-50, #f9fafb);
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary, #111827);
    transition: background-color 0.2s ease;
  }

  .filter-group-header:hover {
    background: var(--gray-100, #f3f4f6);
  }

  .filter-group-label {
    flex: 1;
  }

  .filter-group-indicator {
    color: var(--primary-500, #3b82f6);
    margin-right: 0.5rem;
  }

  .filter-group-toggle {
    font-size: 1rem;
    font-weight: bold;
    color: var(--text-secondary, #6b7280);
  }

  .filter-group-content {
    padding: 0.75rem;
  }

  .filter-select, .filter-input, .filter-date {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    font-size: 0.875rem;
    background: var(--background, #ffffff);
    color: var(--text-primary, #111827);
  }

  .filter-select:focus, .filter-input:focus, .filter-date:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .filter-multiselect, .filter-checkboxes, .filter-radios {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-checkbox, .filter-radio {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
  }

  .filter-checkbox input, .filter-radio input {
    margin: 0;
  }

  .filter-range {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-range-input {
    width: 100%;
  }

  .filter-range-values {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
  }

  .filter-daterange {
    display: flex;
    gap: 0.5rem;
  }

  .filter-daterange .filter-date {
    flex: 1;
  }

  .filter-custom {
    display: flex;
    gap: 0.5rem;
  }

  .filter-apply-btn {
    padding: 0.5rem 1rem;
    background: var(--primary-500, #3b82f6);
    color: white;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .filter-apply-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .filter-apply-btn:hover:not(:disabled) {
    background: var(--primary-600, #2563eb);
  }

  .filter-clear-btn {
    margin-top: 0.75rem;
    padding: 0.25rem 0.5rem;
    background: none;
    border: 1px solid var(--gray-300, #d1d5db);
    color: var(--text-secondary, #6b7280);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    cursor: pointer;
  }

  .filter-clear-btn:hover {
    background: var(--gray-50, #f9fafb);
  }

  .filter-summary {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #d1d5db);
    font-size: 0.875rem;
  }

  .filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
  }

  .filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: var(--primary-100, #dbeafe);
    color: var(--primary-700, #1d4ed8);
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .filter-tag button {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
  }

  .filter-tag button:hover {
    opacity: 0.7;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .filter-panel {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .filter-header {
      border-color: var(--border-color-dark, #4b5563);
    }

    .filter-title {
      color: var(--text-primary-dark, #f9fafb);
    }

    .filter-group-header {
      background: var(--gray-800-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
    }

    .filter-group-header:hover {
      background: var(--gray-700-dark, #374151);
    }

    .filter-select, .filter-input, .filter-date {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .filter-summary {
      border-color: var(--border-color-dark, #4b5563);
    }

    .filter-group {
      border-color: var(--border-color-dark, #4b5563);
    }
  }

  /* Accessibility */
  .filter-group-header:focus,
  .filter-select:focus,
  .filter-input:focus,
  .filter-date:focus,
  .filter-clear-all:focus,
  .filter-clear-btn:focus,
  .filter-apply-btn:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .filter-group-header,
    .filter-clear-all,
    .filter-clear-btn,
    .filter-apply-btn {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .filter-panel {
      max-width: none;
    }

    .filter-daterange {
      flex-direction: column;
    }

    .filter-custom {
      flex-direction: column;
    }
  }
</style>