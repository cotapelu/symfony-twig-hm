import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import VirtualizedTable from './VirtualizedTable.svelte';

describe('VirtualizedTable Component', () => {
  it('renders without errors', async () => {
    const { component } = render(VirtualizedTable, { props: { columns: [], data: [] } });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts columns prop', async () => {
    const columns = [
      { key: 'id', label: 'ID', width: 100 },
      { key: 'name', label: 'Name', width: 200 },
    ];
    const { component } = render(VirtualizedTable, {
      props: { columns, data: [{ id: 1, name: 'A' }] },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts data prop', async () => {
    const data = Array.from({ length: 20 }, (_, i) => ({ id: i, name: `Row ${i}` }));
    const { component } = render(VirtualizedTable, {
      props: { columns: [{ key: 'id', label: 'ID' }], data },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('handles empty data', async () => {
    const { component } = render(VirtualizedTable, {
      props: { columns: [{ key: 'id', label: 'ID' }], data: [] },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts rowHeight prop', async () => {
    const { component } = render(VirtualizedTable, {
      props: { columns: [], data: [], rowHeight: 50 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts height prop', async () => {
    const { component } = render(VirtualizedTable, {
      props: { columns: [], data: [], height: 500 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { component: c1 } = render(VirtualizedTable, { props: { columns: [], data: [] } });
    const { component: c2 } = render(VirtualizedTable, {
      props: { columns: [{ key: 'id', label: 'ID' }], data: [{ id: 1 }] },
    });
    await tick();
    expect(c1).not.toBeNull();
    expect(c2).not.toBeNull();
  });

  it('handles large data set', async () => {
    const data = Array.from({ length: 5000 }, (_, i) => ({ id: i }));
    const { component } = render(VirtualizedTable, {
      props: { columns: [{ key: 'id', label: 'ID' }], data },
    });
    await tick();
    expect(component).not.toBeNull();
  });
});
