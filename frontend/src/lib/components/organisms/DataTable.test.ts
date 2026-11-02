import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, fireEvent, screen, within } from '@testing-library/svelte';
import { tick } from 'svelte';
import DataTable from './DataTable.svelte';

const mockColumns = [
  { key: 'id', title: 'ID' },
  { key: 'name', title: 'Name' },
  { key: 'email', title: 'Email' },
];

const mockData = [
  { id: 1, name: 'Alice', email: 'alice@example.com' },
  { id: 2, name: 'Bob', email: 'bob@example.com' },
  { id: 3, name: 'Charlie', email: 'charlie@example.com' },
  { id: 4, name: 'David', email: 'david@example.com' },
  { id: 5, name: 'Eve', email: 'eve@example.com' },
];

describe('DataTable Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Rendering', () => {
    it('renders table with headers', () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData } });
      expect(screen.getByText('ID')).toBeInTheDocument();
      expect(screen.getByText('Name')).toBeInTheDocument();
      expect(screen.getByText('Email')).toBeInTheDocument();
    });

    it('renders data rows', () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData } });
      expect(screen.getByText('Alice')).toBeInTheDocument();
      expect(screen.getByText('Bob')).toBeInTheDocument();
      expect(screen.getByText('Eve')).toBeInTheDocument();
    });

    it('renders correct number of rows', () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData } });
      const rows = screen.getAllByRole('row'); // includes header row
      expect(rows).toHaveLength(mockData.length + 1);
    });

    it('applies custom className', () => {
      const { container } = render(DataTable, { props: { columns: mockColumns, data: mockData, className: 'my-table' } });
      expect(container.firstChild).toHaveClass('my-table');
    });
  });

  describe('Sorting', () => {
    it('sorts ascending when clicking column header', async () => {
      const sortableColumns = mockColumns.map(col => ({ ...col, sortable: true }));
      render(DataTable, { props: { columns: sortableColumns, data: mockData } });
      const sortBtn = screen.getByRole('button', { name: /Sort by Name/i });
      await fireEvent.click(sortBtn);
      await tick();
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows[0]).toHaveTextContent('Alice');
      expect(rows[4]).toHaveTextContent('Eve');
    });

    it('toggles sort order on subsequent clicks', async () => {
      const sortableColumns = mockColumns.map(col => ({ ...col, sortable: true }));
      render(DataTable, { props: { columns: sortableColumns, data: mockData } });
      const sortBtn = screen.getByRole('button', { name: /Sort by Name/i });
      // initial: unsorted, button shows ↕
      expect(sortBtn).toHaveTextContent('↕');
      await fireEvent.click(sortBtn); // asc
      await tick();
      expect(sortBtn).toHaveTextContent('↑');
      await fireEvent.click(sortBtn); // desc
      await tick();
      expect(sortBtn).toHaveTextContent('↓');
    });

    it('does not sort when sortable=false', () => {
      const unsortableColumns = mockColumns.map(col => ({ ...col, sortable: false }));
      render(DataTable, { props: { columns: unsortableColumns, data: mockData, sortable: false } });
      // No sort button should be rendered
      expect(screen.queryByRole('button', { name: /Sort by Name/i })).not.toBeInTheDocument();
      // Order remains unchanged
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows[0]).toHaveTextContent('Alice');
      expect(rows[4]).toHaveTextContent('Eve');
    });
  });

  describe('Filtering', () => {
    it('filters rows based on input', async () => {
      const filterableColumns = mockColumns.map(col => ({ ...col, filterable: true }));
      render(DataTable, { props: { columns: filterableColumns, data: mockData } });
      const nameHeaderCell = screen.getByText('Name').closest('th')!;
      const nameFilterInput = within(nameHeaderCell).getByRole('textbox');
      expect(nameFilterInput).toBeInTheDocument();
      // Enter filter text
      await fireEvent.input(nameFilterInput, { target: { value: 'Ali' } });
      await tick();
      // Input value should update
      expect(nameFilterInput).toHaveValue('Ali');
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows).toHaveLength(1);
      expect(rows[0]).toHaveTextContent('Alice');
    });

    it('shows all rows when filter cleared', async () => {
      const filterableColumns = mockColumns.map(col => ({ ...col, filterable: true }));
      render(DataTable, { props: { columns: filterableColumns, data: mockData } });
      const nameHeaderCell = screen.getByText('Name').closest('th')!;
      const nameFilterInput = within(nameHeaderCell).getByRole('textbox');
      // Set filter
      await fireEvent.input(nameFilterInput, { target: { value: 'Ali' } });
      await tick();
      // Clear filter
      await fireEvent.input(nameFilterInput, { target: { value: '' } });
      await tick();
      expect(nameFilterInput).toHaveValue('');
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows).toHaveLength(mockData.length);
    });
  });

  describe('Pagination', () => {
    const largeData = Array.from({ length: 25 }, (_, i) => ({
      id: i + 1,
      name: `User ${i + 1}`,
      email: `user${i + 1}@example.com`
    }));

    it('shows only pageSize items when paginated', async () => {
      render(DataTable, { props: { columns: mockColumns, data: largeData, paginated: true, pageSize: 10 } });
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows).toHaveLength(10);
    });

    it('changes page when clicking pagination', async () => {
      render(DataTable, { props: { columns: mockColumns, data: largeData, paginated: true, pageSize: 10 } });
      const nextBtn = screen.getByRole('button', { name: /next|›/i });
      await fireEvent.click(nextBtn);
      await tick();
      const rows = screen.getAllByRole('row').slice(1);
      expect(rows[0]).toHaveTextContent('User 11');
      expect(rows[9]).toHaveTextContent('User 20');
    });
  });

  describe('Selection', () => {
    it('renders checkboxes when selectable', () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData, selectable: true } });
      const checkboxes = screen.getAllByRole('checkbox');
      // One checkbox per row + maybe "select all" in header
      expect(checkboxes.length).toBeGreaterThanOrEqual(mockData.length);
    });

    it('toggles row selection on checkbox click', async () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData, selectable: true } });
      const checkboxes = screen.getAllByRole('checkbox');
      const firstRowCheckbox = checkboxes[1]; // first data row (after header)
      await fireEvent.click(firstRowCheckbox);
      await tick();
      expect(firstRowCheckbox).toBeChecked();
    });
  });

  describe('Loading', () => {
    it('shows loading message', () => {
      render(DataTable, { props: { columns: mockColumns, data: mockData, loading: true } });
      expect(screen.getByText('Loading...')).toBeInTheDocument();
    });
  });

  describe('Custom rendering', () => {
    it('uses render function for custom cell', () => {
      const columnsWithRender = [
        { key: 'id', title: 'ID', render: (value: number) => `#${value}` }
      ];
      render(DataTable, { props: { columns: columnsWithRender, data: mockData } });
      expect(screen.getByText('#1')).toBeInTheDocument();
    });
  });
});
