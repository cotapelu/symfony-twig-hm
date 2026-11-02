<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Tree from './Tree.svelte';

  export let treeData = [];
  export let value = null;
  export let multiple = false;
  export let checkable = false;
  export let disabled = false;
  export let placeholder = 'Please select';
  export let allowClear = true;
  export let showSearch = true;
  export let treeDefaultExpandAll = false;
  export let treeDefaultExpandedKeys = [];
  export let dropdownClassName = '';
  export let className = '';

  const dispatch = createEventDispatcher();

  let dropdownOpen = false;
  let searchValue = '';
  let expandedKeys = [...treeDefaultExpandedKeys];
  let selectedKeys = multiple ? (Array.isArray(value) ? [...value] : []) : (value ? [value] : []);
  let checkedKeys = checkable ? (Array.isArray(value) ? [...value] : []) : [];

  $: displayValue = getDisplayValue();
  $: filteredTreeData = filterTreeData(treeData, searchValue);

  function getDisplayValue() {
    if (multiple) {
      const selectedItems = findItemsByKeys(treeData, selectedKeys);
      return selectedItems.map(item => item.title).join(', ');
    } else {
      const selectedItem = findItemByKey(treeData, selectedKeys[0]);
      return selectedItem ? selectedItem.title : '';
    }
  }

  function findItemByKey(data, key) {
    for (const item of data) {
      if (item.key === key) return item;
      if (item.children) {
        const found = findItemByKey(item.children, key);
        if (found) return found;
      }
    }
    return null;
  }

  function findItemsByKeys(data, keys) {
    const items = [];
    for (const key of keys) {
      const item = findItemByKey(data, key);
      if (item) items.push(item);
    }
    return items;
  }

  function filterTreeData(data, search) {
    if (!search) return data;

    return data.filter(item => {
      const matches = item.title.toLowerCase().includes(search.toLowerCase());
      if (matches) return true;
      if (item.children) {
        item.children = filterTreeData(item.children, search);
        return item.children.length > 0;
      }
      return false;
    });
  }

  function handleSelect(key, selected) {
    if (multiple) {
      if (selected) {
        selectedKeys = [...selectedKeys, key];
      } else {
        selectedKeys = selectedKeys.filter(k => k !== key);
      }
    } else {
      selectedKeys = selected ? [key] : [];
      dropdownOpen = false;
    }

    const newValue = multiple ? selectedKeys : selectedKeys[0] || null;
    value = newValue;
    dispatch('change', { value: newValue, selectedKeys });
  }

  function handleCheck(key, checked) {
    if (checked) {
      checkedKeys = [...checkedKeys, key];
    } else {
      checkedKeys = checkedKeys.filter(k => k !== key);
    }

    const newValue = checkedKeys;
    value = newValue;
    dispatch('change', { value: newValue, checkedKeys });
  }

  function handleExpand(key, expanded) {
    if (expanded) {
      expandedKeys = [...expandedKeys, key];
    } else {
      expandedKeys = expandedKeys.filter(k => k !== key);
    }
  }

  function handleClear() {
    selectedKeys = [];
    checkedKeys = [];
    value = multiple ? [] : null;
    dispatch('change', { value: multiple ? [] : null });
  }

  function toggleDropdown() {
    if (!disabled) {
      dropdownOpen = !dropdownOpen;
    }
  }

  $: classes = [
    'tree-select',
    disabled ? 'tree-select-disabled' : '',
    dropdownOpen ? 'tree-select-open' : '',
    className
  ].filter(Boolean).join(' ');

  $: dropdownClasses = [
    'tree-select-dropdown',
    dropdownClassName
  ].filter(Boolean).join(' ');
</script>

<div class={classes}>
  <div class="tree-select-selector" onclick={toggleDropdown}>
    <span class="tree-select-selection-placeholder" class:filled={displayValue}>
      {displayValue || placeholder}
    </span>
    <span class="tree-select-arrow">
      {#if dropdownOpen}▲{:else}▼{/if}
    </span>
    {#if allowClear && displayValue && !disabled}
      <!-- svelte-ignore a11y_click_events_have_key_events -->
      <!-- svelte-ignore a11y_no_static_element_interactions -->
      <span class="tree-select-clear" onclick={(e) => { e.stopPropagation(); handleClear(); }}>✕</span>
    {/if}
  </div>

  {#if dropdownOpen}
    <div class={dropdownClasses}>
      {#if showSearch}
        <div class="tree-select-search">
          <input
            type="text"
            placeholder="Search..."
            bind:value={searchValue}
            class="tree-select-search-input"
          />
        </div>
      {/if}

      <div class="tree-select-tree">
        <Tree
          options={filteredTreeData}
          value={selectedKeys[0] || ''}
          className="tree-select-tree-inner"
        />
      </div>
    </div>
  {/if}
</div>

<style>
  .tree-select {
    position: relative;
    display: inline-block;
    width: 200px;
    font-family: inherit;
    font-size: 0.875rem;
  }

  .tree-select-selector {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.25rem 0.75rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .tree-select-selector:hover:not(.tree-select-disabled) {
    border-color: var(--primary-500, #3b82f6);
  }

  .tree-select-open .tree-select-selector {
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
  }

  .tree-select-selection-placeholder {
    flex: 1;
    color: var(--text-secondary, #6b7280);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .tree-select-selection-placeholder.filled {
    color: var(--text-primary, #111827);
  }

  .tree-select-arrow {
    margin-left: 0.5rem;
    color: var(--text-secondary, #6b7280);
    transition: transform 0.2s ease;
  }

  .tree-select-clear {
    margin-left: 0.5rem;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    padding: 0.125rem;
    border-radius: 2px;
    transition: all 0.2s ease;
  }

  .tree-select-clear:hover {
    background: var(--gray-100, #f3f4f6);
    color: var(--text-primary, #111827);
  }

  .tree-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    margin-top: 4px;
    padding: 0.5rem 0;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-height: 200px;
    overflow-y: auto;
  }

  .tree-select-search {
    padding: 0 1rem 0.5rem;
    border-bottom: 1px solid var(--border-primary, #d1d5db);
  }

  .tree-select-search-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    font-size: inherit;
  }

  .tree-select-search-input:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .tree-select-tree {
    padding: 0.5rem 0;
  }

  .tree-select-disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .tree-select-disabled .tree-select-selector {
    cursor: not-allowed;
  }

  /* Mobile */
  @media (max-width: 640px) {
    .tree-select {
      width: 100%;
    }

    .tree-select-dropdown {
      position: fixed;
      top: auto;
      bottom: 0;
      left: 0;
      right: 0;
      margin-top: 0;
      border-radius: 6px 6px 0 0;
      max-height: 50vh;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .tree-select-selector,
    .tree-select-dropdown,
    .tree-select-search-input {
      border-width: 2px;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .tree-select-selector {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .tree-select-selection-placeholder {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .tree-select-selection-placeholder.filled {
      color: var(--text-primary-dark, #f9fafb);
    }

    .tree-select-arrow,
    .tree-select-clear {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .tree-select-selector:hover:not(.tree-select-disabled) {
      border-color: var(--primary-400, #60a5fa);
    }

    .tree-select-open .tree-select-selector {
      border-color: var(--primary-400, #60a5fa);
      box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.1);
    }

    .tree-select-dropdown {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .tree-select-search {
      border-color: var(--gray-600, #4b5563);
    }

    .tree-select-search-input {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .tree-select-clear:hover {
      background: var(--gray-700, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }
  }
</style>