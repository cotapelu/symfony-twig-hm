<script lang="ts">
  import DOMPurify from 'dompurify';

  export let dataSource = [];
  export let targetKeys = [];
  export let selectedKeys = [];
  export let disabled = false;
  export let showSearch = false;
  export let showSelectAll = true;
  export let titles = ['Source', 'Target'];
  export let operations = ['>', '<'];
  export let oneWay = false;
  export let pagination = false;
  export let filterOption = null;
  export let render = null;
  export let className = '';

  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  let sourceSearch = '';
  let targetSearch = '';
  let sourceSelected = [];
  let targetSelected = [];

  $: sourceItems = dataSource.filter(item => !targetKeys.includes(item.key));
  $: targetItems = dataSource.filter(item => targetKeys.includes(item.key));

  $: filteredSourceItems = sourceItems.filter(item =>
    !showSearch || !sourceSearch ||
    item.title.toLowerCase().includes(sourceSearch.toLowerCase()) ||
    item.description?.toLowerCase().includes(sourceSearch.toLowerCase())
  );

  $: filteredTargetItems = targetItems.filter(item =>
    !showSearch || !targetSearch ||
    item.title.toLowerCase().includes(targetSearch.toLowerCase()) ||
    item.description?.toLowerCase().includes(targetSearch.toLowerCase())
  );

  $: sourceCheckAll = sourceSelected.length === filteredSourceItems.length && filteredSourceItems.length > 0;
  $: sourceIndeterminate = sourceSelected.length > 0 && sourceSelected.length < filteredSourceItems.length;

  $: targetCheckAll = targetSelected.length === filteredTargetItems.length && filteredTargetItems.length > 0;
  $: targetIndeterminate = targetSelected.length > 0 && targetSelected.length < filteredTargetItems.length;

  function handleSourceSelect(key, checked) {
    if (checked) {
      sourceSelected = [...sourceSelected, key];
    } else {
      sourceSelected = sourceSelected.filter(k => k !== key);
    }
  }

  function handleTargetSelect(key, checked) {
    if (checked) {
      targetSelected = [...targetSelected, key];
    } else {
      targetSelected = targetSelected.filter(k => k !== key);
    }
  }

  function handleSourceCheckAll(checked) {
    sourceSelected = checked ? filteredSourceItems.map(item => item.key) : [];
  }

  function handleTargetCheckAll(checked) {
    targetSelected = checked ? filteredTargetItems.map(item => item.key) : [];
  }

  function moveToTarget() {
    const newTargetKeys = [...targetKeys, ...sourceSelected];
    targetKeys = newTargetKeys;
    sourceSelected = [];
    dispatch('change', { targetKeys: newTargetKeys, direction: 'right', moveKeys: sourceSelected });
  }

  function moveToSource() {
    const newTargetKeys = targetKeys.filter(key => !targetSelected.includes(key));
    targetKeys = newTargetKeys;
    targetSelected = [];
    dispatch('change', { targetKeys: newTargetKeys, direction: 'left', moveKeys: targetSelected });
  }

  function handleSelectChange(source, target) {
    sourceSelected = source;
    targetSelected = target;
    selectedKeys = [...source, ...target];
    dispatch('selectChange', { sourceSelected: source, targetSelected: target });
  }

  $: classes = [
    'transfer',
    disabled ? 'transfer-disabled' : '',
    className
  ].filter(Boolean).join(' ');

  function renderItem(item) {
    if (render) {
      return render(item);
    }
    return item.title;
  }
</script>

<div class={classes}>
  <div class="transfer-list transfer-list-source">
    {#if titles[0]}
      <div class="transfer-list-header">
        <span class="transfer-list-title">{titles[0]}</span>
        {#if showSelectAll && filteredSourceItems.length > 0}
          <label class="transfer-checkbox-wrapper">
            <input
              type="checkbox"
              {disabled}
              checked={sourceCheckAll}
              indeterminate={sourceIndeterminate}
              onchange={(e) => handleSourceCheckAll((e.target as HTMLInputElement).checked)}
            />
            <span class="transfer-checkbox"></span>
          </label>
        {/if}
      </div>
    {/if}

    {#if showSearch}
      <div class="transfer-list-search">
        <input
          type="text"
          placeholder="Search..."
          bind:value={sourceSearch}
          {disabled}
          class="transfer-search-input"
        />
      </div>
    {/if}

    <div class="transfer-list-body">
      {#each filteredSourceItems as item}
        <div class="transfer-list-item" class:selected={sourceSelected.includes(item.key)}>
          <label class="transfer-checkbox-wrapper">
            <input
              type="checkbox"
              {disabled}
              checked={sourceSelected.includes(item.key)}
              onchange={(e) => handleSourceSelect(item.key, (e.target as HTMLInputElement).checked)}
            />
            <span class="transfer-checkbox"></span>
          </label>
          <span class="transfer-list-item-content">
            {@html DOMPurify.sanitize(renderItem(item))}
          </span>
        </div>
      {/each}
      {#if filteredSourceItems.length === 0}
        <div class="transfer-list-empty">
          No data
        </div>
      {/if}
    </div>
  </div>

  <div class="transfer-operation">
    <button
      type="button"
      class="transfer-operation-btn"
      disabled={disabled || sourceSelected.length === 0}
      onclick={moveToTarget}
    >
      {operations[0]}
    </button>
    {#if !oneWay}
      <button
        type="button"
        class="transfer-operation-btn"
        disabled={disabled || targetSelected.length === 0}
        onclick={moveToSource}
      >
        {operations[1]}
      </button>
    {/if}
  </div>

  <div class="transfer-list transfer-list-target">
    {#if titles[1]}
      <div class="transfer-list-header">
        <span class="transfer-list-title">{titles[1]}</span>
        {#if showSelectAll && filteredTargetItems.length > 0}
          <label class="transfer-checkbox-wrapper">
            <input
              type="checkbox"
              {disabled}
              checked={targetCheckAll}
              indeterminate={targetIndeterminate}
              onchange={(e) => handleTargetCheckAll((e.target as HTMLInputElement).checked)}
            />
            <span class="transfer-checkbox"></span>
          </label>
        {/if}
      </div>
    {/if}

    {#if showSearch}
      <div class="transfer-list-search">
        <input
          type="text"
          placeholder="Search..."
          bind:value={targetSearch}
          {disabled}
          class="transfer-search-input"
        />
      </div>
    {/if}

    <div class="transfer-list-body">
      {#each filteredTargetItems as item}
        <div class="transfer-list-item" class:selected={targetSelected.includes(item.key)}>
          <label class="transfer-checkbox-wrapper">
            <input
              type="checkbox"
              {disabled}
              checked={targetSelected.includes(item.key)}
              onchange={(e) => handleTargetSelect(item.key, (e.target as HTMLInputElement).checked)}
            />
            <span class="transfer-checkbox"></span>
          </label>
          <span class="transfer-list-item-content">
            {@html DOMPurify.sanitize(renderItem(item))}
          </span>
        </div>
      {/each}
      {#if filteredTargetItems.length === 0}
        <div class="transfer-list-empty">
          No data
        </div>
      {/if}
    </div>
  </div>
</div>

<style>
  .transfer {
    display: flex;
    align-items: stretch;
    gap: 1rem;
    font-family: inherit;
    font-size: 0.875rem;
  }

  .transfer-list {
    flex: 1;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 6px;
    background: var(--bg-primary, white);
    display: flex;
    flex-direction: column;
    min-height: 200px;
  }

  .transfer-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-primary, #d1d5db);
    background: var(--gray-50, #f9fafb);
  }

  .transfer-list-title {
    font-weight: 500;
    color: var(--text-primary, #111827);
  }

  .transfer-list-search {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--border-primary, #d1d5db);
  }

  .transfer-search-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    font-size: inherit;
  }

  .transfer-search-input:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .transfer-list-body {
    flex: 1;
    overflow-y: auto;
    padding: 0.25rem 0;
  }

  .transfer-list-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .transfer-list-item:hover {
    background: var(--gray-50, #f9fafb);
  }

  .transfer-list-item.selected {
    background: var(--primary-50, #eff6ff);
  }

  .transfer-list-item-content {
    flex: 1;
    margin-left: 0.5rem;
  }

  .transfer-list-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--text-secondary, #6b7280);
  }

  .transfer-checkbox-wrapper {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
  }

  .transfer-checkbox {
    width: 1rem;
    height: 1rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 2px;
    background: white;
    margin-right: 0.5rem;
    position: relative;
    transition: all 0.2s ease;
  }

  .transfer-checkbox-wrapper input:checked + .transfer-checkbox::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--primary-500, #3b82f6);
    font-size: 0.75rem;
    font-weight: bold;
  }

  .transfer-operation {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0 0.5rem;
  }

  .transfer-operation-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid var(--border-primary, #d1d5db);
    border-radius: 4px;
    background: white;
    color: var(--text-primary, #111827);
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s ease;
  }

  .transfer-operation-btn:hover:not(:disabled) {
    border-color: var(--primary-500, #3b82f6);
    color: var(--primary-500, #3b82f6);
  }

  .transfer-operation-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .transfer-disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  /* Mobile */
  @media (max-width: 768px) {
    .transfer {
      flex-direction: column;
      gap: 0.5rem;
    }

    .transfer-operation {
      flex-direction: row;
      padding: 0.5rem 0;
    }

    .transfer-operation-btn {
      width: 2.5rem;
      height: 2.5rem;
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .transfer-list,
    .transfer-operation-btn,
    .transfer-checkbox,
    .transfer-search-input {
      border-width: 2px;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .transfer-list-item,
    .transfer-operation-btn,
    .transfer-checkbox {
      transition: none;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .transfer-list {
      background: var(--gray-800, #1f2937);
      border-color: var(--gray-600, #4b5563);
    }

    .transfer-list-header {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
    }

    .transfer-list-title {
      color: var(--text-primary-dark, #f9fafb);
    }

    .transfer-list-search {
      border-color: var(--gray-600, #4b5563);
    }

    .transfer-search-input {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .transfer-list-item:hover {
      background: var(--gray-700, #374151);
    }

    .transfer-list-item.selected {
      background: var(--primary-900, #1e3a8a);
    }

    .transfer-list-empty {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .transfer-checkbox {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
    }

    .transfer-operation-btn {
      background: var(--gray-700, #374151);
      border-color: var(--gray-600, #4b5563);
      color: var(--text-primary-dark, #f9fafb);
    }

    .transfer-operation-btn:hover:not(:disabled) {
      border-color: var(--primary-400, #60a5fa);
      color: var(--primary-400, #60a5fa);
    }
  }
</style>