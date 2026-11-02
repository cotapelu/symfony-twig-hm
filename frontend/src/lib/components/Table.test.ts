import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import Table from './Table.svelte';

describe('Table Component', () => {
  const sampleData = [
    { id: 1, name: 'Alice', age: 30, role: 'Engineer' },
    { id: 2, name: 'Bob', age: 25, role: 'Designer' },
    { id: 3, name: 'Charlie', age: 35, role: 'Manager' },
  ];

  const sampleColumns = [
    { key: 'id', title: 'ID' },
    { key: 'name', title: 'Name' },
    { key: 'age', title: 'Age' },
    { key: 'role', title: 'Role' },
  ];

  it('renders without crashing', async () => {
    const { container } = render(Table);
    await tick();
    expect(container.querySelector('.table-container')).not.toBeNull();
  });

  it('shows empty message when no data', async () => {
    render(Table, { props: { data: [], columns: sampleColumns, emptyMessage: 'No items' } });
    await tick();
    expect(screen.getByText('No items')).not.toBeNull();
  });

  it('shows default empty message', async () => {
    render(Table, { props: { data: [], columns: sampleColumns } });
    await tick();
    expect(screen.getByText('No data available')).not.toBeNull();
  });

  it('renders table with headers', async () => {
    render(Table, { props: { data: sampleData, columns: sampleColumns } });
    await tick();
    expect(screen.getByText('ID')).not.toBeNull();
    expect(screen.getByText('Name')).not.toBeNull();
    expect(screen.getByText('Age')).not.toBeNull();
    expect(screen.getByText('Role')).not.toBeNull();
  });

  it('renders rows for each data item', async () => {
    const { container } = render(Table, { props: { data: sampleData, columns: sampleColumns } });
    await tick();
    const rows = container.querySelectorAll('tbody tr');
    expect(rows.length).toBe(3);
  });

  it('renders cell values', async () => {
    render(Table, { props: { data: sampleData, columns: sampleColumns } });
    await tick();
    expect(screen.getByText('Alice')).not.toBeNull();
    expect(screen.getByText('Bob')).not.toBeNull();
    expect(screen.getByText('Charlie')).not.toBeNull();
    expect(screen.getByText('Engineer')).not.toBeNull();
  });

  it('shows loading skeletons when loading=true', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, loading: true },
    });
    await tick();
    expect(container.querySelector('.table-loading')).not.toBeNull();
  });

  it('applies striped class when striped=true', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, striped: true },
    });
    await tick();
    expect(container.querySelector('table')?.classList.contains('striped')).toBe(true);
  });

  it('does not apply striped class by default', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();
    expect(container.querySelector('table')?.classList.contains('striped')).toBe(false);
  });

  it('applies hover class by default', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();
    expect(container.querySelector('table')?.classList.contains('hover')).toBe(true);
  });

  it('applies border class by default', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();
    expect(container.querySelector('.table-container')?.classList.contains('border')).toBe(true);
  });

  it('does not apply border when border=false', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, border: false },
    });
    await tick();
    expect(container.querySelector('.table-container')?.classList.contains('border')).toBe(false);
  });

  it('renders select column when selectable=true', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, selectable: true },
    });
    await tick();
    expect(container.querySelector('.select-column')).not.toBeNull();
  });

  it('does not render select column when selectable=false', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();
    expect(container.querySelector('.select-column')).toBeNull();
  });

  it('sorts data when sortable=true and header clicked', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, sortable: true },
    });
    await tick();

    const headers = container.querySelectorAll('.table-header.sortable');
    expect(headers.length).toBe(sampleColumns.length);

    // Click 'name' header
    const nameHeader = screen.getByText('Name').closest('th');
    expect(nameHeader).not.toBeNull();
    if (nameHeader) {
      await fireEvent.click(nameHeader);
      await tick();
    }

    // Sort indicator should appear
    expect(container.querySelector('.sort-indicator')).not.toBeNull();
  });

  it('toggles sort direction on second click of same column', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns, sortable: true },
    });
    await tick();

    const nameHeader = screen.getByText('Name').closest('th');
    if (nameHeader) {
      await fireEvent.click(nameHeader);
      await tick();
      expect(container.querySelector('.sort-indicator')?.textContent?.trim()).toBe('↑');

      await fireEvent.click(nameHeader);
      await tick();
      expect(container.querySelector('.sort-indicator')?.textContent?.trim()).toBe('↓');
    }
  });

  it('does not sort when sortable=false', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();

    const nameHeader = screen.getByText('Name').closest('th');
    expect(nameHeader).not.toBeNull();
    if (nameHeader) {
      await fireEvent.click(nameHeader);
      await tick();
    }

    expect(container.querySelector('.sort-indicator')).toBeNull();
  });

  it('handles row click without error', async () => {
    const { container } = render(Table, {
      props: { data: sampleData, columns: sampleColumns },
    });
    await tick();
    const firstRow = container.querySelector('tbody tr');
    expect(firstRow).not.toBeNull();
    if (firstRow) {
      await fireEvent.click(firstRow);
    }
  });

  it('formats cell value using column format function', async () => {
    const columnsWithFormat = [
      { key: 'age', title: 'Age', format: (v: number) => `${v} years` },
    ];
    render(Table, { props: { data: sampleData, columns: columnsWithFormat } });
    await tick();
    expect(screen.getByText('30 years')).not.toBeNull();
    expect(screen.getByText('25 years')).not.toBeNull();
    expect(screen.getByText('35 years')).not.toBeNull();
  });

  it('renders cell with center alignment', async () => {
    const centerColumns = [{ key: 'id', title: 'ID', align: 'center' as const }];
    const { container } = render(Table, {
      props: { data: sampleData, columns: centerColumns },
    });
    await tick();
    const cell = container.querySelector('.table-cell.center');
    expect(cell).not.toBeNull();
  });

  it('renders cell with right alignment', async () => {
    const rightColumns = [{ key: 'id', title: 'ID', align: 'right' as const }];
    const { container } = render(Table, {
      props: { data: sampleData, columns: rightColumns },
    });
    await tick();
    const cell = container.querySelector('.table-cell.right');
    expect(cell).not.toBeNull();
  });

  it('renders nested value using dot notation in key', async () => {
    const nestedData = [{ info: { name: 'Alice' } }];
    const nestedColumns = [{ key: 'info.name', title: 'Name' }];
    render(Table, { props: { data: nestedData, columns: nestedColumns } });
    await tick();
    expect(screen.getByText('Alice')).not.toBeNull();
  });
});
