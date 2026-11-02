import { describe, it, expect, vi, afterEach } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import { get } from 'svelte/store';
import ToastManager from './ToastManager.svelte';

describe('ToastManager Component', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders no container when no toasts are present', async () => {
    const { container } = render(ToastManager);
    await tick();
    expect(container.querySelector('.toast-container')).toBeNull();
  });

  it('exposes a writable toasts store', async () => {
    const { component } = render(ToastManager);
    await tick();
    // The store is exported as a named const from the module; verify its initial state
    const store = component.toasts;
    expect(get(store)).toEqual([]);
  });

  it('showToast appends a toast item with default options', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id = component.showToast('info', 'Heads up', 'Information message');
    await tick();
    const store = component.toasts;
    const items = get(store);
    expect(items).toHaveLength(1);
    expect(items[0]).toMatchObject({
      id,
      type: 'info',
      title: 'Heads up',
      message: 'Information message',
      duration: 5000,
      persistent: false,
      showCloseButton: true
    });
  });

  it('showToast honors options (duration, persistent, showCloseButton)', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id = component.showToast('success', 'Saved', 'Saved ok', {
      duration: 1234,
      persistent: true,
      showCloseButton: false
    });
    await tick();
    const items = get(component.toasts);
    expect(items[0]).toMatchObject({
      id,
      type: 'success',
      title: 'Saved',
      message: 'Saved ok',
      duration: 1234,
      persistent: true,
      showCloseButton: false
    });
  });

  it('showSuccessToast wraps showToast with type=success', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showSuccessToast('OK', 'It worked');
    await tick();
    const items = get(component.toasts);
    expect(items[0].type).toBe('success');
    expect(items[0].title).toBe('OK');
    expect(items[0].message).toBe('It worked');
  });

  it('showErrorToast wraps showToast with type=error', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showErrorToast('Oops', 'Something failed');
    await tick();
    const items = get(component.toasts);
    expect(items[0].type).toBe('error');
    expect(items[0].title).toBe('Oops');
    expect(items[0].message).toBe('Something failed');
  });

  it('showWarningToast wraps showToast with type=warning', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showWarningToast('Warn', 'Be careful');
    await tick();
    const items = get(component.toasts);
    expect(items[0].type).toBe('warning');
    expect(items[0].title).toBe('Warn');
    expect(items[0].message).toBe('Be careful');
  });

  it('showInfoToast wraps showToast with type=info', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showInfoToast('FYI', 'Just so you know');
    await tick();
    const items = get(component.toasts);
    expect(items[0].type).toBe('info');
    expect(items[0].title).toBe('FYI');
    expect(items[0].message).toBe('Just so you know');
  });

  it('increments internal toast id so each id is unique', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id1 = component.showToast('info', 'a', 'a');
    const id2 = component.showToast('info', 'b', 'b');
    const id3 = component.showToast('info', 'c', 'c');
    await tick();
    expect(id1).not.toBe(id2);
    expect(id2).not.toBe(id3);
    expect(id1).not.toBe(id3);
    const items = get(component.toasts);
    expect(items.map(i => i.id)).toEqual([id1, id2, id3]);
  });

  it('dismissToast removes the toast by id', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id1 = component.showToast('info', 'one', 'm1');
    const id2 = component.showToast('info', 'two', 'm2');
    await tick();
    expect(get(component.toasts)).toHaveLength(2);
    component.dismissToast(id1);
    await tick();
    const items = get(component.toasts);
    expect(items).toHaveLength(1);
    expect(items[0].id).toBe(id2);
  });

  it('dismissToast is a no-op for unknown ids', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id = component.showToast('info', 'one', 'm1');
    await tick();
    component.dismissToast('does-not-exist');
    await tick();
    expect(get(component.toasts)).toHaveLength(1);
    expect(get(component.toasts)[0].id).toBe(id);
  });

  it('clearAllToasts empties the list', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'a', 'a');
    component.showToast('error', 'b', 'b');
    component.showToast('warning', 'c', 'c');
    await tick();
    expect(get(component.toasts)).toHaveLength(3);
    component.clearAllToasts();
    await tick();
    expect(get(component.toasts)).toEqual([]);
  });

  it('store reflects toast additions (Toast DOM gated by onMount so not asserted here)', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('success', 'Saved', 'Saved ok');
    await tick();
    const items = get(component.toasts);
    expect(items).toHaveLength(1);
    expect(items[0].title).toBe('Saved');
    expect(items[0].message).toBe('Saved ok');
    expect(items[0].type).toBe('success');
  });

  it('multiple toasts preserved in store order', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'First', 'msg1');
    component.showToast('warning', 'Second', 'msg2');
    component.showToast('error', 'Third', 'msg3');
    await tick();
    const items = get(component.toasts);
    expect(items.map(i => i.title)).toEqual(['First', 'Second', 'Third']);
    expect(items.map(i => i.type)).toEqual(['info', 'warning', 'error']);
  });

  it('clearAllToasts empties store (DOM not asserted due to Toast mounted-gate)', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'x', 'y');
    await tick();
    expect(get(component.toasts)).toHaveLength(1);
    component.clearAllToasts();
    await tick();
    expect(get(component.toasts)).toEqual([]);
  });

  it('dismissToast removes the targeted toast from store', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id = component.showToast('info', 'hello', 'world', { persistent: true });
    await tick();
    expect(get(component.toasts)).toHaveLength(1);
    component.dismissToast(id);
    await tick();
    expect(get(component.toasts)).toHaveLength(0);
  });

  it('showCloseButton=false is honored (default true when omitted)', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'a', 'a'); // default
    await tick();
    expect(get(component.toasts)[0].showCloseButton).toBe(true);

    component.showToast('info', 'b', 'b', { showCloseButton: false });
    await tick();
    expect(get(component.toasts)[1].showCloseButton).toBe(false);

    component.showToast('info', 'c', 'c', { showCloseButton: true });
    await tick();
    expect(get(component.toasts)[2].showCloseButton).toBe(true);
  });

  it('persistent option defaults to false and can be toggled', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'a', 'a');
    await tick();
    expect(get(component.toasts)[0].persistent).toBe(false);

    component.showToast('info', 'b', 'b', { persistent: true });
    await tick();
    expect(get(component.toasts)[1].persistent).toBe(true);
  });

  it('duration defaults to 5000 when not specified', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('info', 'a', 'a');
    await tick();
    expect(get(component.toasts)[0].duration).toBe(5000);
  });

  it('multiple toasts show independent titles and messages', async () => {
    const { component } = render(ToastManager);
    await tick();
    component.showToast('success', 'A', 'msg-A');
    component.showToast('error', 'B', 'msg-B');
    await tick();
    const items = get(component.toasts);
    expect(items[0].title).toBe('A');
    expect(items[0].message).toBe('msg-A');
    expect(items[1].title).toBe('B');
    expect(items[1].message).toBe('msg-B');
  });

  it('returns a string id from showToast', async () => {
    const { component } = render(ToastManager);
    await tick();
    const id = component.showToast('info', 'x', 'y');
    expect(typeof id).toBe('string');
    expect(id.length).toBeGreaterThan(0);
  });

  it('no-throw safety on dispatch interactions', async () => {
    const { component } = render(ToastManager);
    await tick();
    expect(() => {
      component.showToast('info', 'x', 'y');
      component.dismissToast('non-existent');
      component.clearAllToasts();
    }).not.toThrow();
  });
});
