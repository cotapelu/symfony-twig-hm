import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ClickOutside } from './click-outside';

describe('ClickOutside', () => {
  let element: HTMLElement;
  let clickOutside: ClickOutside;

  beforeEach(() => {
    vi.useFakeTimers();
    element = document.createElement('div');
    document.body.appendChild(element);
  });

  afterEach(() => {
    clickOutside?.destroy();
    if (element.parentNode) {
      document.body.removeChild(element);
    }
    vi.useRealTimers();
  });

  it('adds event listener after setTimeout', () => {
    const addSpy = vi.spyOn(document, 'addEventListener');
    clickOutside = new ClickOutside(element, () => {});
    // setTimeout hasn't run yet
    expect(addSpy).not.toHaveBeenCalled();
    // Fast-forward time
    vi.runAllTimers();
    expect(addSpy).toHaveBeenCalledWith('click', expect.any(Function), true);
  });

  it('removes event listener on destroy', () => {
    const removeSpy = vi.spyOn(document, 'removeEventListener');
    clickOutside = new ClickOutside(element, () => {});
    vi.runAllTimers(); // ensure listener added
    clickOutside.destroy();
    expect(removeSpy).toHaveBeenCalledWith('click', expect.any(Function), true);
  });
});
