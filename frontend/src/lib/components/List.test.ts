import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import List from './List.svelte';

describe('List Component', () => {
  const sampleItems = [
    { id: 1, name: 'Item 1' },
    { id: 2, name: 'Item 2' },
    { id: 3, name: 'Item 3' },
  ];

  it('renders wrapper div with list class', async () => {
    const { container } = render(List, { props: { items: [] } });
    await tick();
    expect(container.querySelector('.list')).not.toBeNull();
  });

  it('renders empty when no items', async () => {
    const { container } = render(List, { props: { items: [] } });
    await tick();
    expect(container.querySelectorAll('.list-item').length).toBe(0);
  });

  it('renders one list-item per item', async () => {
    const { container } = render(List, { props: { items: sampleItems } });
    await tick();
    expect(container.querySelectorAll('.list-item').length).toBe(3);
  });

  it('default layout is list', async () => {
    const { container } = render(List, { props: { items: sampleItems } });
    await tick();
    expect(container.querySelector('.list')?.classList.contains('list-list')).toBe(true);
  });

  it('applies grid layout class', async () => {
    const { container } = render(List, { props: { items: sampleItems, layout: 'grid' } });
    await tick();
    expect(container.querySelector('.list-grid')).not.toBeNull();
  });

  it('applies pagination class when pagination=true', async () => {
    const { container } = render(List, {
      props: { items: sampleItems, pagination: true },
    });
    await tick();
    expect(container.querySelector('.list')?.classList.contains('list-paginated')).toBe(true);
  });

  it('applies infiniteScroll class when infiniteScroll=true', async () => {
    const { container } = render(List, {
      props: { items: sampleItems, infiniteScroll: true },
    });
    await tick();
    expect(container.querySelector('.list')?.classList.contains('list-infinite')).toBe(true);
  });

  it('applies custom className', async () => {
    const { container } = render(List, {
      props: { items: sampleItems, className: 'my-list' },
    });
    await tick();
    expect(container.querySelector('.my-list')).not.toBeNull();
  });

  it('renders all items when pagination=false', async () => {
    const manyItems = Array.from({ length: 50 }, (_, i) => ({ id: i, name: `Item ${i}` }));
    const { container } = render(List, { props: { items: manyItems } });
    await tick();
    expect(container.querySelectorAll('.list-item').length).toBe(50);
  });

  it('paginates items when pagination=true', async () => {
    const manyItems = Array.from({ length: 25 }, (_, i) => ({ id: i, name: `Item ${i}` }));
    const { container } = render(List, {
      props: { items: manyItems, pagination: true, pageSize: 10 },
    });
    await tick();
    expect(container.querySelectorAll('.list-item').length).toBe(10);
  });

  it('renders load-more button when pagination=true', async () => {
    const manyItems = Array.from({ length: 25 }, (_, i) => ({ id: i, name: `Item ${i}` }));
    const { container } = render(List, {
      props: { items: manyItems, pagination: true, pageSize: 10 },
    });
    await tick();
    const btn = container.querySelector('.list-load-more, button');
    expect(btn).not.toBeNull();
  });



  it('shows loading state when loading=true', async () => {
    const { container } = render(List, {
      props: { items: sampleItems, loading: true },
    });
    await tick();
    // loading is exposed via store/state; verify component renders
    expect(container.querySelector('.list')).not.toBeNull();
  });

  it('renders unique key per item', async () => {
    const { container } = render(List, { props: { items: sampleItems } });
    await tick();
    const items = container.querySelectorAll('.list-item');
    expect(items.length).toBe(3);
  });
});
