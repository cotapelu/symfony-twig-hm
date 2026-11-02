import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import PivotTable from './PivotTable.svelte';

describe('PivotTable', () => {
  const sampleData = [
    { region: 'North', product: 'A', sales: 100 },
    { region: 'North', product: 'B', sales: 200 },
    { region: 'South', product: 'A', sales: 150 },
    { region: 'South', product: 'B', sales: 250 }
  ];

  it('renders pivoted data correctly for sum aggregation', () => {
    render(PivotTable, {
      props: {
        data: sampleData,
        rowFields: ['region'],
        columnFields: ['product'],
        valueField: 'sales',
        aggregation: 'sum'
      }
    });

    // The pivoted table should have values for each combination
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('200')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument();
    expect(screen.getByText('250')).toBeInTheDocument();
  });

  it('filters data based on filters prop', () => {
    render(PivotTable, {
      props: {
        data: sampleData,
        rowFields: ['region'],
        columnFields: ['product'],
        valueField: 'sales',
        aggregation: 'sum',
        filters: { region: 'North' }
      }
    });

    // Only North rows: 100, 200
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('200')).toBeInTheDocument();
    expect(screen.queryByText('150')).not.toBeInTheDocument();
    expect(screen.queryByText('250')).not.toBeInTheDocument();
  });

  // Note: Row totals and column totals are not yet implemented in the component.
});
