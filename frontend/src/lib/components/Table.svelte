<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import LoadingSkeleton from './LoadingSkeleton.svelte';

  interface Column {
    key: string;
    title: string;
    align?: 'left' | 'center' | 'right';
    format?: (value: any, row: any) => any;
    component?: any;
  }

  export let data: any[] = [];
  export let columns: Column[] = [];
  export let selectable = false;
  export let sortable = false;
  export let loading = false;
  export let emptyMessage = 'No data available';
  export let striped = false;
  export let hover = true;
  export let border = true;

  const dispatch = createEventDispatcher();

  let selectedRows: any[] = [];
  let sortColumn = '';
  let sortDirection = 'asc';

  $: sortedData = sortData(data, sortColumn, sortDirection);

  function sortData(data: any[], column: string, direction: string) {
    if (!sortable || !column) return data;

    return [...data].sort((a, b) => {
      const aVal = getNestedValue(a, column);
      const bVal = getNestedValue(b, column);

      if (aVal < bVal) return direction === 'asc' ? -1 : 1;
      if (aVal > bVal) return direction === 'asc' ? 1 : -1;
      return 0;
    });
  }

  function getNestedValue(obj: any, path: string) {
    return path.split('.').reduce((current: any, key: string) => current?.[key], obj);
  }

  function handleSort(column: Column) {
    if (!sortable) return;

    if (sortColumn === column.key) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = column.key;
      sortDirection = 'asc';
    }
  }

  function handleRowSelect(row: any, index: number, selected: boolean) {
    if (!selectable) return;

    if (selected) {
      selectedRows = [...selectedRows, row];
    } else {
      selectedRows = selectedRows.filter((r: any) => r !== row);
    }

    dispatch('select', { row, index, selected, selectedRows });
  }

  function handleRowClick(row: any, index: number) {
    dispatch('rowClick', { row, index });
  }

  function getColumnValue(row: any, column: Column) {
    const value = getNestedValue(row, column.key);
    if (column.format) {
      return column.format(value, row);
    }
    return value;
  }
</script>

<div class="table-container" class:border>
  {#if loading}
    <div class="table-loading">
      <LoadingSkeleton height="40px" />
      <LoadingSkeleton height="40px" />
      <LoadingSkeleton height="40px" />
    </div>
  {:else if sortedData.length === 0}
    <div class="table-empty">
      <p>{emptyMessage}</p>
    </div>
  {:else}
    <table class="table" class:striped class:hover>
      <thead>
        <tr>
          {#if selectable}
            <th class="select-column">
              <input
                type="checkbox"
                onchange={(e: Event) => {
                  const target = e.target as HTMLInputElement;
                  if (target.checked) {
                    selectedRows = [...sortedData];
                  } else {
                    selectedRows = [];
                  }
                  dispatch('selectAll', { selected: target.checked, selectedRows });
                }}
                checked={selectedRows.length === sortedData.length && sortedData.length > 0}
              />
            </th>
          {/if}
          {#each columns as column}
            <th
              class="table-header"
              class:sortable
              onclick={() => handleSort(column)}
            >
              <div class="header-content">
                <span>{column.title}</span>
                {#if sortable && sortColumn === column.key}
                  <span class="sort-indicator">
                    {sortDirection === 'asc' ? '↑' : '↓'}
                  </span>
                {/if}
              </div>
            </th>
          {/each}
        </tr>
      </thead>
      <tbody>
        {#each sortedData as row, index}
          <tr
            class="table-row"
            class:selected={selectable && selectedRows.includes(row)}
            onclick={() => handleRowClick(row, index)}
          >
            {#if selectable}
              <td class="select-column">
                <input
                  type="checkbox"
                  checked={selectedRows.includes(row)}
                  onchange={(e: Event) => {
                    const target = e.target as HTMLInputElement;
                    handleRowSelect(row, index, target.checked)
                  }}
                />
              </td>
            {/if}
            {#each columns as column}
              <td class="table-cell" class:left={column.align === 'left'} class:center={column.align === 'center'} class:right={column.align === 'right'}>
                {#if column.component}
                  <svelte:component this={column.component} {row} column={column.key} value={getColumnValue(row, column)} />
                {:else}
                  {getColumnValue(row, column)}
                {/if}
              </td>
            {/each}
          </tr>
        {/each}
      </tbody>
    </table>
  {/if}
</div>

<style>
  .table-container {
    overflow-x: auto;
    border-radius: 8px;
  }

  .table-container.border {
    border: 1px solid var(--border-color, #e5e7eb);
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }

  .table-loading,
  .table-empty {
    padding: 2rem;
    text-align: center;
    color: var(--text-secondary, #6b7280);
  }

  .table-empty p {
    margin: 0;
  }

  .table-header {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary, #111827);
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-secondary, #f9fafb);
  }

  .table-header.sortable {
    cursor: pointer;
    user-select: none;
  }

  .table-header.sortable:hover {
    background: var(--bg-hover, #f3f4f6);
  }

  .header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .sort-indicator {
    margin-left: 0.5rem;
    opacity: 0.7;
  }

  .table-row {
    transition: background-color 0.2s ease;
  }

  .table.hover .table-row:hover {
    background: var(--bg-hover, #f9fafb);
  }

  .table.striped .table-row:nth-child(even) {
    background: var(--bg-striped, #f9fafb);
  }

  .table-row.selected {
    background: var(--bg-selected, #dbeafe);
  }

  .table-cell {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    vertical-align: middle;
  }

  .table-cell.left {
    text-align: left;
  }

  .table-cell.center {
    text-align: center;
  }

  .table-cell.right {
    text-align: right;
  }

  .select-column {
    width: 48px;
    text-align: center;
  }

  .select-column input[type="checkbox"] {
    margin: 0;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .table-cell {
      padding: 0.5rem;
    }

    .table-header {
      padding: 0.5rem;
      font-size: 0.8125rem;
    }
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .table-header {
      background: var(--bg-secondary-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
      border-bottom-color: var(--border-color-dark, #374151);
    }

    .table-cell {
      border-bottom-color: var(--border-color-dark, #374151);
    }

    .table.hover .table-row:hover {
      background: var(--bg-hover-dark, #374151);
    }

    .table.striped .table-row:nth-child(even) {
      background: var(--bg-striped-dark, #1f2937);
    }

    .table-row.selected {
      background: var(--bg-selected-dark, #1e3a8a);
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .table-container.border {
      border: 2px solid;
    }

    .table-header {
      border-bottom: 2px solid;
    }

    .table-cell {
      border-bottom: 1px solid;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .table-row {
      transition: none;
    }
  }
</style>