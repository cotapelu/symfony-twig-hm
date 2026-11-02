import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import VirtualList from './VirtualList.svelte';

describe('VirtualList Component', () => {
  it('renders without errors', async () => {
    const { component } = render(VirtualList, {
      props: { items: [1, 2, 3], itemHeight: 30 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts items array', async () => {
    const items = Array.from({ length: 10 }, (_, i) => ({ id: i, text: `Item ${i}` }));
    const { component } = render(VirtualList, {
      props: { items, itemHeight: 30 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts itemHeight prop', async () => {
    const { component } = render(VirtualList, {
      props: { items: [1, 2, 3], itemHeight: 50 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts height prop', async () => {
    const { component } = render(VirtualList, {
      props: { items: [1, 2, 3], itemHeight: 30, height: 500 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('handles empty items array', async () => {
    const { component } = render(VirtualList, {
      props: { items: [], itemHeight: 30 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { component: c1 } = render(VirtualList, {
      props: { items: [1, 2, 3], itemHeight: 30 },
    });
    const { component: c2 } = render(VirtualList, {
      props: { items: [4, 5, 6], itemHeight: 30 },
    });
    await tick();
    expect(c1).not.toBeNull();
    expect(c2).not.toBeNull();
  });

  it('handles large items array', async () => {
    const items = Array.from({ length: 1000 }, (_, i) => i);
    const { component } = render(VirtualList, {
      props: { items, itemHeight: 30 },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts custom className', async () => {
    const { component } = render(VirtualList, {
      props: { items: [1, 2, 3], itemHeight: 30, className: 'custom-vlist' },
    });
    await tick();
    expect(component).not.toBeNull();
  });
});
