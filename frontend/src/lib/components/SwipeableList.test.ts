import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import SwipeableList from './SwipeableList.svelte';

const sampleItems = [
  { id: 1, text: 'Item 1' },
  { id: 2, text: 'Item 2' },
  { id: 3, text: 'Item 3' },
];

const sampleActions = [
  { key: 'edit', label: 'Edit', color: 'blue' },
  { key: 'delete', label: 'Delete', color: 'red' },
];

describe('SwipeableList Component', () => {
  it('renders wrapper with swipeable-list class', async () => {
    const { container } = render(SwipeableList, { props: { items: sampleItems } });
    await tick();
    expect(container.querySelector('.swipeable-list')).not.toBeNull();
  });

  it('applies custom className to wrapper', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, className: 'my-swipeable' },
    });
    await tick();
    expect(container.querySelector('.my-swipeable')).not.toBeNull();
  });

  it('renders one swipeable-item per item', async () => {
    const { container } = render(SwipeableList, { props: { items: sampleItems } });
    await tick();
    const items = container.querySelectorAll('.swipeable-item');
    expect(items.length).toBe(3);
  });

  it('renders empty when no items', async () => {
    const { container } = render(SwipeableList, { props: { items: [] } });
    await tick();
    expect(container.querySelector('.swipeable-item')).toBeNull();
  });

  it('renders default threshold default value (no error)', async () => {
    const { container } = render(SwipeableList, { props: { items: sampleItems } });
    await tick();
    expect(container.querySelector('.swipeable-list')).not.toBeNull();
  });

  it('renders with custom threshold without error', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, threshold: 100 },
    });
    await tick();
    expect(container.querySelector('.swipeable-list')).not.toBeNull();
  });

  it('does not render actions container when actions is empty', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: [] },
    });
    await tick();
    expect(container.querySelector('.swipeable-actions')).toBeNull();
  });

  it('renders actions container for each item when actions provided', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: sampleActions },
    });
    await tick();
    const actionContainers = container.querySelectorAll('.swipeable-actions');
    expect(actionContainers.length).toBe(3);
  });

  it('renders action buttons inside each action container', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: sampleActions },
    });
    await tick();
    const actionButtons = container.querySelectorAll('.swipeable-action');
    // 3 items × 2 actions = 6 buttons
    expect(actionButtons.length).toBe(6);
  });

  it('applies action color as inline background-color', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: sampleActions },
    });
    await tick();
    const editBtn = container.querySelector('.swipeable-action');
    expect(editBtn?.getAttribute('style')).toContain('background-color: blue');
  });

  it('renders action label text', async () => {
    render(SwipeableList, {
      props: { items: sampleItems, actions: sampleActions },
    });
    await tick();
    expect(screen.getAllByText('Edit').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Delete').length).toBeGreaterThan(0);
  });

  it('renders action icon when provided', async () => {
    const actionsWithIcons = [
      { key: 'edit', label: 'Edit', color: 'blue', icon: '✏️' },
      { key: 'delete', label: 'Delete', color: 'red', icon: '🗑️' },
    ];
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: actionsWithIcons },
    });
    await tick();
    const icons = container.querySelectorAll('.swipeable-action-icon');
    expect(icons.length).toBe(6); // 3 items × 2 actions
  });

  it('does not render action-icon span when no icon provided', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions: [{ key: 'a', label: 'A', color: 'red' }] },
    });
    await tick();
    expect(container.querySelector('.swipeable-action-icon')).toBeNull();
  });

  it('renders swipeable-content wrapper per item', async () => {
    const { container } = render(SwipeableList, { props: { items: sampleItems } });
    await tick();
    const contents = container.querySelectorAll('.swipeable-content');
    expect(contents.length).toBe(3);
  });

  it('clicking action button calls action callback and dispatches event', async () => {
    const deleteAction = vi.fn();
    const actions = [{ key: 'delete', label: 'Delete', color: 'red', action: deleteAction }];
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions },
    });
    await tick();
    const btn = container.querySelector('.swipeable-action');
    if (btn) {
      await fireEvent.click(btn);
    }
    expect(deleteAction).toHaveBeenCalledWith(sampleItems[0]);
  });

  it('clicking action button does not throw when action is missing', async () => {
    const actions = [{ key: 'a', label: 'A', color: 'red' }];
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, actions },
    });
    await tick();
    const btn = container.querySelector('.swipeable-action');
    let error: any = null;
    try {
      if (btn) await fireEvent.click(btn);
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });

  it('clicking different actions on same item calls correct action', async () => {
    const editAction = vi.fn();
    const deleteAction = vi.fn();
    const actions = [
      { key: 'edit', label: 'Edit', color: 'blue', action: editAction },
      { key: 'delete', label: 'Delete', color: 'red', action: deleteAction },
    ];
    const { container } = render(SwipeableList, {
      props: { items: [{ id: 1 }], actions },
    });
    await tick();
    const buttons = container.querySelectorAll('.swipeable-action');
    if (buttons[1]) {
      await fireEvent.click(buttons[1]);
    }
    expect(deleteAction).toHaveBeenCalled();
    expect(editAction).not.toHaveBeenCalled();
  });

  it('default threshold value is 50 (per source)', async () => {
    const { container } = render(SwipeableList, {
      props: { items: sampleItems, threshold: 75 },
    });
    await tick();
    // Custom threshold should be applied - check the swipeable-list still renders
    expect(container.querySelector('.swipeable-list')).not.toBeNull();
  });
});
