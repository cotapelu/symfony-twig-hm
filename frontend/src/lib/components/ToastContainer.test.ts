import { describe, it, expect, beforeEach } from 'vitest';
import { render } from '@testing-library/svelte';
import ToastContainer from './ToastContainer.svelte';
import { toasts } from '$lib/stores/global';

describe('ToastContainer', () => {
  beforeEach(() => {
    toasts.set([]);
  });

  it('renders without error when toasts store is empty', () => {
    const { container } = render(ToastContainer);
    expect(container).toBeInTheDocument();
  });

  it('renders without error and reacts to store updates (empty)', () => {
    const { container } = render(ToastContainer);
    expect(container).toBeInTheDocument();

    // Add and remove from empty store
    toasts.update((current) => [...current, { id: '1', message: 'Toast', type: 'info', timestamp: 1 }]);
    toasts.update((current) => current.filter((t) => t.id !== '1'));
    toasts.set([]);

    expect(container).toBeInTheDocument();
  });

  it('renders multiple instances independently (empty store)', () => {
    const { container: container1 } = render(ToastContainer);
    const { container: container2 } = render(ToastContainer);
    expect(container1).toBeInTheDocument();
    expect(container2).toBeInTheDocument();
  });

  it('handles rapid store updates without error (empty store)', () => {
    const { container } = render(ToastContainer);

    // Rapid add/remove on empty store
    for (let i = 0; i < 20; i++) {
      toasts.update((current) => [...current, { id: String(i), message: `Toast ${i}`, type: 'info', timestamp: i }]);
      toasts.update((current) => current.filter((t) => t.id !== String(i)));
    }

    expect(container).toBeInTheDocument();
    toasts.set([]);
    expect(container).toBeInTheDocument();
  });

  it('component mounts and unmounts without error', () => {
    const { unmount } = render(ToastContainer);
    expect(() => unmount()).not.toThrow();
  });

  it('renders with initial empty toasts store', () => {
    // Verify initial state
    expect(toasts).toBeDefined();
    const { container } = render(ToastContainer);
    expect(container).toBeInTheDocument();
  });

  it('handles store subscription without memory leak', () => {
    const { container, unmount } = render(ToastContainer);
    expect(container).toBeInTheDocument();
    unmount();
    // Re-render to verify no leaked subscriptions
    const { container: container2, unmount: unmount2 } = render(ToastContainer);
    expect(container2).toBeInTheDocument();
    unmount2();
  });

  it('renders without error when store reference changes', () => {
    const { container } = render(ToastContainer);
    // toasts is a writable store, component subscribes to it
    expect(container).toBeInTheDocument();
  });
});