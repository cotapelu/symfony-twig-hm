<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DataTable from './organisms/DataTable.svelte';

  const dispatch = createEventDispatcher();

  interface Column {
    key: string;
    title: string;
    sortable?: boolean;
    filterable?: boolean;
    width?: string;
    align?: 'left' | 'center' | 'right';
    render?: (value: any, record: any) => string;
  }

  export let data: any[] = [];
  export let rowFields: string[] = [];
  export let columnFields: string[] = [];
  export let valueField: string = '';
  export let aggregation: 'sum' | 'count' | 'avg' | 'min' | 'max' = 'sum';
  export let filters: { [key: string]: any } = {};
  export let showRowTotals: boolean = true;
  export let showColumnTotals: boolean = true;
  export let className: string = '';

  let sortColumn: string = '';
  let sortOrder: 'asc' | 'desc' = 'asc';
  let expandedRows: Set<string> = new Set();

  $: classes = [
    'pivot-table',
    className
  ].filter(Boolean).join(' ');

  $: pivotedData = pivotData(data, rowFields, columnFields, valueField, aggregation, filters);
  $: columns = generateColumns(pivotedData, rowFields, columnFields, showRowTotals, showColumnTotals);

  function pivotData(
    data: any[],
    rowFields: string[],
    columnFields: string[],
    valueField: string,
    aggregation: string,
    filters: { [key: string]: any }
  ) {
    if (!data.length || !rowFields.length || !columnFields.length || !valueField) {
      return [];
    }

    // Apply filters first
    let filteredData = data.filter(item => {
      return Object.entries(filters).every(([key, value]) => {
        if (value === null || value === undefined || value === '') return true;
        return item[key] === value;
      });
    });

    // Create pivot structure
    const pivotMap = new Map();

    filteredData.forEach(item => {
      const rowKey = rowFields.map(field => item[field]).join('|');
      const colKey = columnFields.map(field => item[field]).join('|');
      const pivotKey = `${rowKey}::${colKey}`;

      if (!pivotMap.has(pivotKey)) {
        pivotMap.set(pivotKey, {
          _rowValues: rowFields.reduce((acc, field) => ({ ...acc, [field]: item[field] }), {}),
          _colValues: columnFields.reduce((acc, field) => ({ ...acc, [field]: item[field] }), {}),
          _values: []
        });
      }

      pivotMap.get(pivotKey)._values.push(item[valueField]);
    });

    // Aggregate values
    const result = [];
    for (const [key, item] of pivotMap.entries()) {
      const aggregatedValue = aggregateValues(item._values, aggregation);
      result.push({
        ...item._rowValues,
        ...item._colValues,
        _aggregatedValue: aggregatedValue,
        _count: item._values.length
      });
    }

    return result;
  }

  function aggregateValues(values: any[], aggregation: string): number {
    if (!values.length) return 0;

    const numericValues = values.map(v => {
      const num = typeof v === 'number' ? v : parseFloat(v);
      return isNaN(num) ? 0 : num;
    });

    switch (aggregation) {
      case 'sum':
        return numericValues.reduce((a, b) => a + b, 0);
      case 'count':
        return values.length;
      case 'avg':
        return numericValues.reduce((a, b) => a + b, 0) / numericValues.length;
      case 'min':
        return Math.min(...numericValues);
      case 'max':
        return Math.max(...numericValues);
      default:
        return 0;
    }
  }

  function generateColumns(
    pivotedData: any[],
    rowFields: string[],
    columnFields: string[],
    showRowTotals: boolean,
    showColumnTotals: boolean
  ) {
    const columns = [];

    // Row field columns
    rowFields.forEach(field => {
      columns.push({
        key: field,
        title: field,
        sortable: true,
        width: '150px'
      });
    });

    // Value column
    if (valueField) {
      columns.push({
        key: '_aggregatedValue',
        title: `${aggregation.toUpperCase()}(${valueField})`,
        sortable: true,
        align: 'right',
        render: (value: number) => {
          if (aggregation === 'count') return value.toString();
          return value.toLocaleString(undefined, { maximumFractionDigits: 2 });
        }
      });
    }

    // Count column
    columns.push({
      key: '_count',
      title: 'Count',
      sortable: true,
      align: 'right',
      width: '80px'
    });

    return columns;
  }

  function handleSort(event: any) {
    sortColumn = event.detail.column;
    sortOrder = event.detail.order;
    dispatch('sort', event.detail);
  }

  function handleFilter(event: any) {
    dispatch('filter', event.detail);
  }

  function toggleRowExpansion(rowId: string) {
    if (expandedRows.has(rowId)) {
      expandedRows.delete(rowId);
    } else {
      expandedRows.add(rowId);
    }
    expandedRows = new Set(expandedRows);
  }

  // Drill down functionality
  function drillDown(row: any, field: string) {
    dispatch('drillDown', { row, field });
  }
</script>

<div class={classes}>
  {#if rowFields.length === 0 || columnFields.length === 0 || !valueField}
    <div class="pivot-table-config-message">
      <p>Please configure pivot table:</p>
      <ul>
        <li>Select at least one row field</li>
        <li>Select at least one column field</li>
        <li>Select a value field to aggregate</li>
      </ul>
    </div>
  {:else if pivotedData.length === 0}
    <div class="pivot-table-empty">
      No data available for the current configuration
    </div>
  {:else}
    <div class="pivot-table-controls">
      <div class="pivot-table-info">
        <span class="pivot-table-summary">
          {pivotedData.length} rows × {columns.length} columns
        </span>
        <span class="pivot-table-aggregation">
          Aggregation: {aggregation.toUpperCase()} of {valueField}
        </span>
      </div>

      <div class="pivot-table-actions">
        <button
          class="pivot-table-refresh-btn"
          onclick={() => dispatch('refresh')}
          aria-label="Refresh pivot table"
        >
          🔄 Refresh
        </button>
      </div>
    </div>

    <DataTable
      data={pivotedData}
      columns={columns}
      sortable={true}
      filterable={false}
      selectable={false}
      expandable={true}
      on:sort={handleSort}
      on:filter={handleFilter}
      className="pivot-table-data-table"
    >
      <div slot="expanded" let:row let:index>
        <div class="pivot-table-expanded">
          <h4>Pivot Details</h4>
          <div class="pivot-table-drilldown">
            {#each rowFields as field}
              <button
                class="pivot-table-drill-btn"
                onclick={() => drillDown(row, field)}
                aria-label="Drill down on {field}"
              >
                Drill down: {field} = {row[field]}
              </button>
            {/each}
          </div>
          <pre class="pivot-table-raw-data">{JSON.stringify(row, null, 2)}</pre>
        </div>
      </div>
    </DataTable>
  {/if}
</div>

<style>
  .pivot-table {
    width: 100%;
    background: var(--background, #ffffff);
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .pivot-table-config-message,
  .pivot-table-empty {
    padding: 2rem;
    text-align: center;
    color: var(--text-secondary, #6b7280);
    background: var(--gray-50, #f9fafb);
    border-radius: 0.375rem;
    border: 1px solid var(--border-color, #d1d5db);
  }

  .pivot-table-config-message ul {
    text-align: left;
    margin: 1rem 0 0 0;
    padding-left: 1.5rem;
  }

  .pivot-table-config-message li {
    margin-bottom: 0.5rem;
  }

  .pivot-table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color, #d1d5db);
    background: var(--gray-50, #f9fafb);
    border-radius: 0.375rem 0.375rem 0 0;
  }

  .pivot-table-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .pivot-table-summary {
    font-weight: 600;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  .pivot-table-aggregation {
    color: var(--text-secondary, #6b7280);
    font-size: 0.75rem;
  }

  .pivot-table-actions {
    display: flex;
    gap: 0.5rem;
  }

  .pivot-table-refresh-btn {
    padding: 0.5rem 1rem;
    background: var(--primary-500, #3b82f6);
    color: white;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background-color 0.2s ease;
  }

  .pivot-table-refresh-btn:hover {
    background: var(--primary-600, #2563eb);
  }

  .pivot-table-data-table {
    border: none;
    box-shadow: none;
    border-radius: 0;
  }

  .pivot-table-expanded {
    padding: 1rem;
    background: var(--gray-50, #f9fafb);
    border-radius: 0.25rem;
    margin: 0.5rem 0;
  }

  .pivot-table-expanded h4 {
    margin: 0 0 1rem 0;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    font-weight: 600;
  }

  .pivot-table-drilldown {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .pivot-table-drill-btn {
    padding: 0.25rem 0.75rem;
    background: var(--primary-100, #dbeafe);
    color: var(--primary-700, #1d4ed8);
    border: 1px solid var(--primary-200, #bfdbfe);
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s ease;
  }

  .pivot-table-drill-btn:hover {
    background: var(--primary-200, #bfdbfe);
    border-color: var(--primary-300, #93c5fd);
  }

  .pivot-table-raw-data {
    background: var(--gray-100, #f3f4f6);
    padding: 1rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    overflow-x: auto;
    margin: 0;
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .pivot-table {
      background: var(--background-dark, #1f2937);
    }

    .pivot-table-config-message,
    .pivot-table-empty {
      background: var(--gray-800-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
      color: var(--text-secondary-dark, #9ca3af);
    }

    .pivot-table-controls {
      background: var(--gray-800-dark, #1f2937);
      border-color: var(--border-color-dark, #4b5563);
    }

    .pivot-table-summary {
      color: var(--text-primary-dark, #f9fafb);
    }

    .pivot-table-aggregation {
      color: var(--text-secondary-dark, #9ca3af);
    }

    .pivot-table-expanded {
      background: var(--gray-800-dark, #1f2937);
    }

    .pivot-table-expanded h4 {
      color: var(--text-primary-dark, #f9fafb);
    }

    .pivot-table-raw-data {
      background: var(--gray-900-dark, #111827);
      color: var(--text-primary-dark, #f9fafb);
    }
  }

  /* Accessibility */
  .pivot-table-refresh-btn:focus,
  .pivot-table-drill-btn:focus {
    outline: 2px solid var(--focus-ring, #3b82f6);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .pivot-table-refresh-btn,
    .pivot-table-drill-btn {
      transition: none;
    }
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .pivot-table-controls {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .pivot-table-drilldown {
      flex-direction: column;
    }

    .pivot-table-drill-btn {
      width: 100%;
      text-align: center;
    }
  }
</style>