import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Transfer from './Transfer.svelte';

const sampleData = [
  { key: '1', title: 'Books', description: 'Reading material' },
  { key: '2', title: 'Music', description: 'Audio content' },
  { key: '3', title: 'Games', description: 'Interactive media' },
];

describe('Transfer Component', () => {
  it('renders wrapper with transfer class', async () => {
    const { container } = render(Transfer, { props: { dataSource: [] } });
    await tick();
    expect(container.querySelector('.transfer')).not.toBeNull();
  });

  it('renders two transfer-list panels (source + target)', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    const lists = container.querySelectorAll('.transfer-list');
    expect(lists.length).toBe(2);
    expect(container.querySelector('.transfer-list-source')).not.toBeNull();
    expect(container.querySelector('.transfer-list-target')).not.toBeNull();
  });

  it('renders custom className on wrapper', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, className: 'my-transfer' },
    });
    await tick();
    expect(container.querySelector('.my-transfer')).not.toBeNull();
  });

  it('applies transfer-disabled class when disabled=true', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, disabled: true },
    });
    await tick();
    expect(container.querySelector('.transfer-disabled')).not.toBeNull();
  });

  it('renders default titles Source and Target', async () => {
    render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    expect(screen.getByText('Source')).not.toBeNull();
    expect(screen.getByText('Target')).not.toBeNull();
  });

  it('renders custom titles array', async () => {
    render(Transfer, {
      props: { dataSource: sampleData, titles: ['Available', 'Selected'] },
    });
    await tick();
    expect(screen.getByText('Available')).not.toBeNull();
    expect(screen.getByText('Selected')).not.toBeNull();
  });

  it('renders all source items when targetKeys is empty', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, targetKeys: [] },
    });
    await tick();
    const items = container.querySelectorAll('.transfer-list-source .transfer-list-item');
    expect(items.length).toBe(3);
  });

  it('removes target items from source list', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, targetKeys: ['1', '2'] },
    });
    await tick();
    const sourceItems = container.querySelectorAll('.transfer-list-source .transfer-list-item');
    const targetItems = container.querySelectorAll('.transfer-list-target .transfer-list-item');
    expect(sourceItems.length).toBe(1);
    expect(targetItems.length).toBe(2);
  });

  it('renders No data message when source is empty', async () => {
    render(Transfer, { props: { dataSource: [] } });
    await tick();
    const empties = screen.getAllByText('No data');
    expect(empties.length).toBe(2);
  });

  it('renders default operation buttons with > and < labels', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    const buttons = container.querySelectorAll('.transfer-operation-btn');
    expect(buttons.length).toBe(2);
    expect(buttons[0]?.textContent?.trim()).toBe('>');
    expect(buttons[1]?.textContent?.trim()).toBe('<');
  });

  it('renders custom operation labels', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, operations: ['→', '←'] },
    });
    await tick();
    const buttons = container.querySelectorAll('.transfer-operation-btn');
    expect(buttons[0]?.textContent?.trim()).toBe('→');
    expect(buttons[1]?.textContent?.trim()).toBe('←');
  });

  it('renders only one operation button when oneWay=true', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, oneWay: true },
    });
    await tick();
    const buttons = container.querySelectorAll('.transfer-operation-btn');
    expect(buttons.length).toBe(1);
  });

  it('operation buttons are disabled when no items selected', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    const buttons = container.querySelectorAll('.transfer-operation-btn');
    expect((buttons[0] as HTMLButtonElement)?.disabled).toBe(true);
    expect((buttons[1] as HTMLButtonElement)?.disabled).toBe(true);
  });

  it('renders search input when showSearch=true', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, showSearch: true },
    });
    await tick();
    const searchInputs = container.querySelectorAll('.transfer-search-input');
    expect(searchInputs.length).toBe(2);
  });

  it('does not render search input by default', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    expect(container.querySelector('.transfer-search-input')).toBeNull();
  });

  it('renders select-all checkbox when showSelectAll=true', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, targetKeys: ['1', '2', '3'] },
    });
    await tick();
    // select-all renders only when the panel has items
    const checkboxes = container.querySelectorAll('.transfer-list-header input[type="checkbox"]');
    expect(checkboxes.length).toBe(1);
  });

  it('does not render select-all checkbox when showSelectAll=false', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, showSelectAll: false },
    });
    await tick();
    expect(container.querySelector('.transfer-list-header input[type="checkbox"]')).toBeNull();
  });

  it('renders item titles in source list', async () => {
    render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    expect(screen.getByText('Books')).not.toBeNull();
    expect(screen.getByText('Music')).not.toBeNull();
    expect(screen.getByText('Games')).not.toBeNull();
  });

  it('uses render prop to customize item content', async () => {
    const renderFn = (item: any) => `<strong>${item.title}</strong>`;
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, render: renderFn },
    });
    await tick();
    expect(container.querySelector('.transfer-list-source strong')).not.toBeNull();
  });

  it('renders all items initially in source when target is empty', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, targetKeys: [] },
    });
    await tick();
    const sourceItems = container.querySelectorAll('.transfer-list-source .transfer-list-item');
    expect(sourceItems.length).toBe(3);
  });

  it('hides source No data when there are items', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    const sourceBody = container.querySelector('.transfer-list-source .transfer-list-body');
    expect(sourceBody?.querySelector('.transfer-list-empty')).toBeNull();
  });

  it('renders item checkboxes for each source item', async () => {
    const { container } = render(Transfer, { props: { dataSource: sampleData } });
    await tick();
    const itemCheckboxes = container.querySelectorAll('.transfer-list-item input[type="checkbox"]');
    expect(itemCheckboxes.length).toBe(3);
  });

  it('operation buttons are disabled when component is disabled', async () => {
    const { container } = render(Transfer, {
      props: { dataSource: sampleData, disabled: true, targetKeys: ['1'] },
    });
    await tick();
    const buttons = container.querySelectorAll('.transfer-operation-btn');
    expect((buttons[0] as HTMLButtonElement)?.disabled).toBe(true);
    expect((buttons[1] as HTMLButtonElement)?.disabled).toBe(true);
  });
});
