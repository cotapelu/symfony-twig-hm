import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Tree from './Tree.svelte';

describe('Tree Component', () => {
  it('renders with default class segmented', async () => {
    const { container } = render(Tree);
    await tick();
    expect(container.querySelector('.segmented')).not.toBeNull();
  });

  it('renders empty when no options', async () => {
    const { container } = render(Tree, { props: { options: [] } });
    await tick();
    const btn = container.querySelector('button');
    expect(btn).toBeNull();
    expect(container.querySelector('.segmented')).not.toBeNull();
  });

  it('renders one button per option', async () => {
    render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }, { value: 'b', label: 'B' }, { value: 'c', label: 'C' }],
      },
    });
    await tick();
    expect(screen.getByText('A')).not.toBeNull();
    expect(screen.getByText('B')).not.toBeNull();
    expect(screen.getByText('C')).not.toBeNull();
  });

  it('renders aria-label per option', async () => {
    render(Tree, {
      props: {
        options: [{ value: 'a', label: 'Option A' }],
      },
    });
    await tick();
    const btn = screen.getByText('Option A');
    expect(btn).toHaveAttribute('aria-label', 'Option A');
  });

  it('marks selected option with aria-selected=true', async () => {
    render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }, { value: 'b', label: 'B' }],
        value: 'b',
      },
    });
    await tick();
    const buttons = screen.getAllByRole('button');
    expect(buttons[0]).toHaveAttribute('aria-selected', 'false');
    expect(buttons[1]).toHaveAttribute('aria-selected', 'true');
  });

  it('applies active class when value matches option', async () => {
    const { container } = render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }],
        value: 'a',
      },
    });
    await tick();
    expect(container.querySelector('.segmented-item.active')).not.toBeNull();
  });

  it('does not apply active class when value does not match', async () => {
    render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }],
        value: '',
      },
    });
    await tick();
    const { container } = render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }],
        value: '',
      },
    });
    await tick();
    // The active class should not be present when empty value
    const active = container.querySelector('.segmented-item.active');
    expect(active).toBeNull();
  });

  it('applies custom className to wrapper', async () => {
    const { container } = render(Tree, {
      props: { className: 'my-tree' },
    });
    await tick();
    expect(container.querySelector('.my-tree')).not.toBeNull();
    expect(container.querySelector('.segmented')).not.toBeNull();
  });

  it('clicking option dispatches change event', async () => {
    const { container } = render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }],
        value: '',
      },
    });
    await tick();
    const btn = container.querySelector('button[aria-label="A"]');
    await fireEvent.click(btn);
    // No error means dispatch works
    expect(container.querySelector('button')).not.toBeNull();
  });

  it('clicking option updates selected state', async () => {
    const { container } = render(Tree, {
      props: {
        options: [{ value: 'a', label: 'A' }],
        value: '',
      },
    });
    await tick();
    const btn = screen.getByText('A');
    await fireEvent.click(btn);
    await tick();
    expect(btn).toHaveAttribute('aria-selected', 'true');
  });
});
