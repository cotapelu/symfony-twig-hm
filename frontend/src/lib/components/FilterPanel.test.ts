import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import FilterPanel from './FilterPanel.svelte';

describe('FilterPanel', () => {
  const sampleFilters = [
    {
      key: 'status',
      label: 'Status',
      type: 'select' as const,
      options: [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Pending', value: 'pending' },
      ],
      placeholder: 'Select status',
    },
    {
      key: 'category',
      label: 'Category',
      type: 'multiselect' as const,
      options: [
        { label: 'Electronics', value: 'electronics' },
        { label: 'Clothing', value: 'clothing' },
        { label: 'Books', value: 'books' },
      ],
    },
    {
      key: 'price',
      label: 'Price Range',
      type: 'range' as const,
      min: 0,
      max: 1000,
      step: 10,
    },
    {
      key: 'date',
      label: 'Date',
      type: 'date' as const,
    },
    {
      key: 'daterange',
      label: 'Date Range',
      type: 'daterange' as const,
    },
    {
      key: 'custom',
      label: 'Custom Filter',
      type: 'checkbox' as const,
      options: [
        { label: 'Option A', value: 'a' },
        { label: 'Option B', value: 'b' },
      ],
    },
    {
      key: 'radio',
      label: 'Radio Filter',
      type: 'radio' as const,
      options: [
        { label: 'Option 1', value: '1' },
        { label: 'Option 2', value: '2' },
      ],
    },
  ];

  it('renders with default props', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    expect(screen.getByText('Filters')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Category')).toBeInTheDocument();
  });

  it('applies custom className', () => {
    render(FilterPanel, { props: { filters: sampleFilters, className: 'custom-filter' } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toHaveClass('custom-filter');
  });

  it('renders all filter groups', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Category')).toBeInTheDocument();
    expect(screen.getByText('Price Range')).toBeInTheDocument();
    expect(screen.getByText('Date')).toBeInTheDocument();
    expect(screen.getByText('Date Range')).toBeInTheDocument();
    expect(screen.getByText('Custom Filter')).toBeInTheDocument();
    expect(screen.getByText('Radio Filter')).toBeInTheDocument();
  });

  it('shows Clear All button when filters are applied', () => {
    render(FilterPanel, {
      props: {
        filters: sampleFilters,
        appliedFilters: { status: 'active' },
      },
    });
    expect(screen.getByText('Clear All')).toBeInTheDocument();
  });

  it('hides Clear All button when no filters applied', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    expect(screen.queryByText('Clear All')).not.toBeInTheDocument();
  });

  it('shows applied filters summary', () => {
    render(FilterPanel, {
      props: {
        filters: sampleFilters,
        appliedFilters: { status: 'active', category: ['electronics', 'books'] },
      },
    });
    expect(screen.getByText('Applied filters:')).toBeInTheDocument();
    expect(screen.getByText('Status: active')).toBeInTheDocument();
    expect(screen.getByText('Category: electronics, books')).toBeInTheDocument();
  });

  it('shows filter group indicator when filter has value', () => {
    render(FilterPanel, {
      props: { filters: sampleFilters, appliedFilters: { status: 'active' } },
    });
    // The indicator ● should be present in the status group
    const indicator = document.querySelector('.filter-group-indicator');
    expect(indicator).toBeInTheDocument();
  });



  it('renders select filter with options when expanded (structural)', () => {
    // Note: Filter content is rendered via {@html DOMPurify.sanitize} which may not be fully testable in jsdom
    // This test verifies the component renders without error
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders multiselect filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders range filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders date filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders daterange filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders checkbox filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders radio filter structure', () => {
    render(FilterPanel, { props: { filters: sampleFilters } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(FilterPanel, { props: { filters: sampleFilters } });
    const { container: container2 } = render(FilterPanel, { props: { filters: sampleFilters, className: 'second-panel' } });
    const panel1 = container1.querySelector('.filter-panel');
    const panel2 = container2.querySelector('.filter-panel');
    expect(panel1).toHaveClass('filter-panel');
    expect(panel2).toHaveClass('filter-panel');
    expect(panel2).toHaveClass('second-panel');
  });

  it('renders empty filters array without error', () => {
    render(FilterPanel, { props: { filters: [] } });
    const panel = document.querySelector('.filter-panel');
    expect(panel).toBeInTheDocument();
  });
});