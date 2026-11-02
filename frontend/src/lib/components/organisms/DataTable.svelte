<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  interface Column {
    key: string;
    title: string;
    align?: 'left' | 'center' | 'right';
    format?: (value: any, row: any) => any;
    component?: any;
    sortable?: boolean;
    filterable?: boolean;
    render?: (value: any, row: any) => any;
    allowHtml?: boolean;
    width?: string;
  }

  const dispatch = createEventDispatcher();

  export let data: any[] = [];
  export let columns: Column[] = [];
  export let sortable: boolean = true;
  export let filterable: boolean = true;
  export let paginated: boolean = false;
  export let pageSize: number = 10;
  export let selectable: boolean = false;
  export let expandable: boolean = false;
  export let loading: boolean = false;
  export let className: string = '';

  let sortColumn: string = '';
  let sortOrder: 'asc' | 'desc' = 'asc';
  let filters: { [key: string]: any } = {};
  let currentPage = 1;
  let selectedRows: Set<string | number> = new Set();
  let expandedRows: Set<string | number> = new Set();

  $: classes = [
    'data-table',
    className
  ].filter(Boolean).join(' ');

  $: processedData = processData(data, filters, sortColumn, sortOrder);
  $: totalPages = Math.ceil(processedData.length / pageSize);
  $: paginatedData = paginated ? processedData.slice((currentPage - 1) * pageSize, currentPage * pageSize) : processedData;

  function processData(data: any[], filters: Record<string, any>, sortColumn: string, sortOrder: 'asc' | 'desc') {
    let result = [...data];

    // Apply filters
    Object.entries(filters).forEach(([key, value]) => {
      if (value && value !== '') {
        result = result.filter(item => {
          const itemValue = item[key];
          if (typeof itemValue === 'string') {
            return itemValue.toLowerCase().includes(value.toLowerCase());
          }
          return itemValue === value;
        });
      }
    });

    // Apply sorting
    if (sortColumn) {
      result.sort((a, b) => {
        const aVal = a[sortColumn];
        const bVal = b[sortColumn];

        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;

        return sortOrder === 'asc' ? comparison : -comparison;
      });
    }

    return result;
  }

  function handleSort(column: Column) {
    if (!sortable || !column.sortable) return;

    if (sortColumn === column.key) {
      sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = column.key;
      sortOrder = 'asc';
    }

    dispatch('sort', { column: sortColumn, order: sortOrder });
  }

  function handleFilter(column: Column, value: string) {
    if (!filterable || !column.filterable) return;

    if (value === '') {
      delete filters[column.key];
    } else {
      filters[column.key] = value;
    }

    filters = { ...filters };
    currentPage = 1;

    dispatch('filter', { column: column.key, value, filters });
  }

  function handlePageChange(page: number) {
    currentPage = page;
    dispatch('pageChange', { page, pageSize });
  }

  function toggleRowSelection(rowId: string | number) {
    if (selectedRows.has(rowId)) {
      selectedRows.delete(rowId);
    } else {
      selectedRows.add(rowId);
    }
    selectedRows = new Set(selectedRows);

    dispatch('selectionChange', { selected: Array.from(selectedRows) });
  }

  function toggleRowExpansion(rowId: string | number) {
    if (expandedRows.has(rowId)) {
      expandedRows.delete(rowId);
    } else {
      expandedRows.add(rowId);
    }
    expandedRows = new Set(expandedRows);
  }

  function selectAllRows() {
    if (selectedRows.size === paginatedData.length) {
      selectedRows = new Set();
    } else {
      selectedRows = new Set(paginatedData.map(row => row.id || row));
    }

    dispatch('selectionChange', { selected: Array.from(selectedRows) });
  }

  function renderCell(value: any, column: Column, record: any) {
    if (column.render) {
      const rendered = column.render(value, record);
      // SECURITY: Always sanitize to prevent XSS attacks
      // If allowHtml is true, use more permissive sanitization with allowed tags
      if (column.allowHtml === true) {
        // Allow specific HTML tags for trusted content
        return DOMPurify.sanitize(rendered, {
          ALLOWED_TAGS: ['b', 'i', 'u', 'strong', 'em', 'a', 'br', 'p', 'span', 'div'],
          ALLOWED_ATTR: ['href', 'title', 'class', 'style']
        });
      }
      return DOMPurify.sanitize(rendered);
    }
    return value;
  }
</script>

<div class={classes}>
  {#if loading}
    <div class="data-table-loading">
      <div class="loading-spinner"></div>
      Loading...
    </div>
  {:else}
    <table class="data-table-table">
      <thead class="data-table-header">
        <tr>
          {#if selectable}
            <th class="data-table-cell data-table-cell-select">
              <input
                type="checkbox"
                checked={selectedRows.size === paginatedData.length && paginatedData.length > 0}
                indeterminate={selectedRows.size > 0 && selectedRows.size < paginatedData.length}
                onchange={selectAllRows}
              />
            </th>
          {/if}
          {#if expandable}
            <th class="data-table-cell data-table-cell-expand"></th>
          {/if}
          {#each columns as column}
            <th
              class="data-table-cell data-table-header-cell"
              style="width: {column.width || 'auto'}; text-align: {column.align || 'left'};"
            >
              <div class="data-table-header-content">
                <span class="data-table-header-title">{column.title}</span>

                {#if sortable && column.sortable}
                  <button
                    class="data-table-sort-btn"
                    onclick={() => handleSort(column)}
                    aria-label="Sort by {column.title}"
                  >
                    {#if sortColumn === column.key}
                      {#if sortOrder === 'asc'}
                        ↑
                      {:else}
                        ↓
                      {/if}
                    {:else}
                      ↕
                    {/if}
                  </button>
                {/if}
              </div>

              {#if filterable && column.filterable}
                <input
                  class="data-table-filter-input"
                  placeholder="Filter {column.title}"
                  value={filters[column.key] || ''}
                  oninput={(e) => handleFilter(column, (e.target as HTMLInputElement).value)}
                />
              {/if}
            </th>
          {/each}
        </tr>
      </thead>

      <tbody class="data-table-body">
        {#each paginatedData as row, index (row.id || index)}
          {@const rowId = row.id || index}
          <tr class="data-table-row" class:selected={selectedRows.has(rowId)}>
            {#if selectable}
              <td class="data-table-cell data-table-cell-select">
                <input
                  type="checkbox"
                  checked={selectedRows.has(rowId)}
                  onchange={() => toggleRowSelection(rowId)}
                />
              </td>
            {/if}
            {#if expandable}
              <td class="data-table-cell data-table-cell-expand">
                <button
                  class="data-table-expand-btn"
                  onclick={() => toggleRowExpansion(rowId)}
                  aria-expanded={expandedRows.has(rowId)}
                >
                  {expandedRows.has(rowId) ? '−' : '+'}
                </button>
              </td>
            {/if}
            {#each columns as column}
              <td
                class="data-table-cell"
                style="text-align: {column.align || 'left'};"
              >
                {#if column.allowHtml === true}
                  {@html renderCell(row[column.key], column, row)}
                {:else}
                  {renderCell(row[column.key], column, row)}
                {/if}
              </td>
            {/each}
          </tr>

          {#if expandable && expandedRows.has(rowId)}
            <tr class="data-table-row-expanded">
              <td colspan={columns.length + (selectable ? 1 : 0) + (expandable ? 1 : 0)} class="data-table-cell-expanded">
                <slot name="expanded" {row} {index}>
                  <div class="data-table-expanded-content">
                    <pre>{JSON.stringify(row, null, 2)}</pre>
                  </div>
                </slot>
              </td>
            </tr>
          {/if}
        {/each}
      </tbody>
    </table>

    {#if paginated && totalPages > 1}
      <div class="data-table-pagination">
        <button
          class="data-table-page-btn"
          disabled={currentPage === 1}
          onclick={() => handlePageChange(currentPage - 1)}
        >
          Previous
        </button>

        {#each Array(Math.min(5, totalPages)) as _, i}
          {@const page = Math.max(1, Math.min(totalPages - 4, currentPage - 2)) + i}
          {#if page <= totalPages}
            <button
              class="data-table-page-btn"
              class:active={page === currentPage}
              onclick={() => handlePageChange(page)}
            >
              {page}
            </button>
          {/if}
        {/each}

        <button
          class="data-table-page-btn"
          disabled={currentPage === totalPages}
          onclick={() => handlePageChange(currentPage + 1)}
        >
          Next
        </button>
      </div>
    {/if}

    {#if selectedRows.size > 0}
      <div class="data-table-selection-info">
        {selectedRows.size} row(s) selected
        <button class="data-table-clear-selection" onclick={() => selectedRows = new Set()}>
          Clear
        </button>
      </div>
    {/if}
  {/if}
</div>

<style>
  .data-table {
    width: 100%;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    overflow: hidden;
    background: var(--background, #ffffff);
  }

  .data-table-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--text-secondary, #6b7280);
  }

  .loading-spinner {
    width: 2rem;
    height: 2rem;
    border: 3px solid var(--gray-300, #d1d5db);
    border-top: 3px solid var(--primary-500, #3b82f6);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 0.5rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .data-table-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table-header {
    background: var(--gray-50, #f9fafb);
    border-bottom: 1px solid var(--border-color, #d1d5db);
  }

  .data-table-cell {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-color, #d1d5db);
    vertical-align: top;
  }

  .data-table-cell-select,
  .data-table-cell-expand {
    width: 3rem;
    text-align: center;
  }

  .data-table-header-cell {
    font-weight: 600;
    color: var(--text-primary, #111827);
    position: relative;
  }

  .data-table-header-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .data-table-header-title {
    flex: 1;
  }

  .data-table-sort-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
    padding: 0.25rem;
    border-radius: 0.25rem;
  }

  .data-table-sort-btn:hover {
    background: var(--gray-200, #e5e7eb);
  }

  .data-table-filter-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }

  .data-table-filter-input:focus {
    outline: none;
    border-color: var(--focus-ring, #3b82f6);
  }

  .data-table-row {
    transition: background-color 0.2s ease;
  }

  .data-table-row:hover {
    background: var(--gray-50, #f9fafb);
  }

  .data-table-row.selected {
    background: var(--primary-50, #eff6ff);
  }

  .data-table-row-expanded {
    background: var(--gray-25, #fafafa);
  }

  .data-table-cell-expanded {
    padding: 1rem;
  }

  .data-table-expanded-content {
    font-family: monospace;
    font-size: 0.75rem;
  }

  .data-table-expand-btn,
  .data-table-clear-selection {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    padding: 0.25rem;
    border-radius: 0.25rem;
  }

  .data-table-expand-btn:hover,
  .data-table-clear-selection:hover {
    background: var(--gray-200, #e5e7eb);
  }

  .data-table-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid var(--border-color, #d1d5db);
  }

  .data-table-page-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color, #d1d5db);
    background: var(--background, #ffffff);
    color: var(--text-primary, #111827);
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .data-table-page-btn:hover:not(:disabled) {
    background: var(--gray-50, #f9fafb);
  }

  .data-table-page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .data-table-page-btn.active {
    background: var(--primary-500, #3b82f6);
    color: white;
    border-color: var(--primary-500, #3b82f6);
  }

  .data-table-selection-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--primary-50, #eff6ff);
    color: var(--primary-700, #1d4ed8);
    font-size: 0.875rem;
    border-top: 1px solid var(--border-color, #d1d5db);
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .data-table {
      background: var(--background-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .data-table-header {
      background: var(--gray-800-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .data-table-cell {
      border-color: var(--border-color-dark, #4b5563);
    }

    .data-table-row:hover {
      background: var(--gray-700-dark, #374151);
    }

    .data-table-row.selected {
      background: var(--primary-900-dark, #1e3a8a);
    }

    .data-table-row-expanded {
      background: var(--gray-800-dark, #1f2937);
    }

    .data-table-header-cell {
      color: var(--text-primary-dark, #f9fafb);
    }

    .data-table-page-btn {
      background: var(--background-dark, #1f2937);
      color: var(--text-primary-dark, #f9fafb);
      border-color: var(--border-color-dark, #4b5563);
    }

    .data-table-page-btn:hover:not(:disabled) {
      background: var(--gray-700-dark, #374151);
    }
  }

  /* Accessibility */
  .data-table:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  .data-table-sort-btn:focus,
  .data-table-filter-input:focus,
  .data-table-expand-btn:focus,
  .data-table-page-btn:focus,
  .data-table-clear-selection:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .data-table-row,
    .data-table-sort-btn,
    .data-table-page-btn {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .data-table-cell {
      padding: 0.5rem;
      font-size: 0.875rem;
    }

    .data-table-pagination {
      flex-wrap: wrap;
    }

    .data-table-selection-info {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
  }
</style>