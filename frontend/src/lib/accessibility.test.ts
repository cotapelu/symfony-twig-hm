import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { a11y } from './accessibility';

describe('Accessibility Utilities (a11y)', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    (a11y as any).liveRegions.clear();
  });

  describe('generateId', () => {
    it('generates IDs starting with a11y-', () => {
      const id = a11y.generateId();
      expect(id).toMatch(/^a11y-\d+-[a-z0-9]+$/);
    });

    it('generates unique IDs each call', () => {
      const id1 = a11y.generateId();
      const id2 = a11y.generateId();
      expect(id1).not.toBe(id2);
    });
  });

  describe('announce', () => {
    beforeEach(() => {
      vi.useFakeTimers();
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('creates an aria-live element with polite priority by default', () => {
      a11y.announce('Test message');
      const el = document.body.querySelector('[aria-live]');
      expect(el).not.toBeNull();
      expect(el?.getAttribute('aria-live')).toBe('polite');
      expect(el?.getAttribute('aria-atomic')).toBe('true');
      expect(el?.textContent).toBe('Test message');
      expect(el?.className).toContain('sr-only');
    });

    it('uses assertive priority when specified', () => {
      a11y.announce('Urgent', 'assertive');
      const el = document.body.querySelector('[aria-live]');
      expect(el?.getAttribute('aria-live')).toBe('assertive');
    });

    it('removes the element after ~1000ms', async () => {
      a11y.announce('Temporary');
      const initial = document.body.querySelectorAll('[aria-live]');
      expect(initial.length).toBeGreaterThan(0);
      // Fast-forward beyond the 1000ms removal
      await vi.runAllTimersAsync();
      expect(document.body.querySelectorAll('[aria-live]').length).toBe(0);
    });
  });

  describe('trapFocus', () => {
    it('returns an object with cleanup, focusFirst, focusLast', () => {
      const container = document.createElement('div');
      const button1 = document.createElement('button');
      button1.textContent = 'One';
      const button2 = document.createElement('button');
      button2.textContent = 'Two';
      container.append(button1, button2);
      document.body.append(container);

      const { cleanup, focusFirst, focusLast } = a11y.trapFocus(container);
      expect(typeof cleanup).toBe('function');
      expect(typeof focusFirst).toBe('function');
      expect(typeof focusLast).toBe('function');

      cleanup();
    });

    it('focuses first element on initialization', () => {
      const container = document.createElement('div');
      const button1 = document.createElement('button');
      button1.textContent = 'One';
      const button2 = document.createElement('button');
      button2.textContent = 'Two';
      container.append(button1, button2);
      document.body.append(container);

      const focusSpy = vi.spyOn(button1, 'focus');
      a11y.trapFocus(container);
      expect(focusSpy).toHaveBeenCalled();
    });

    it('focusLast moves focus to last focusable element', () => {
      const container = document.createElement('div');
      const button1 = document.createElement('button');
      button1.textContent = 'One';
      const button2 = document.createElement('button');
      button2.textContent = 'Two';
      container.append(button1, button2);
      document.body.append(container);

      const focusSpy2 = vi.spyOn(button2, 'focus');
      const { focusLast } = a11y.trapFocus(container);
      // Initially first has focus (maybe), but we call focusLast explicitly
      focusLast();
      expect(focusSpy2).toHaveBeenCalled();
    });

    it('traps Tab between first and last elements', () => {
      const container = document.createElement('div');
      const button1 = document.createElement('button');
      button1.textContent = 'One';
      const button2 = document.createElement('button');
      button2.textContent = 'Two';
      container.append(button1, button2);
      document.body.append(container);

      const { cleanup } = a11y.trapFocus(container);

      // Simulate Tab at last element
      const tabEvent = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
      button2.focus();
      button2.dispatchEvent(tabEvent);
      expect(document.activeElement).toBe(button1); // wrapped

      // Simulate Shift+Tab at first element
      const shiftTabEvent = new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true });
      button1.focus();
      button1.dispatchEvent(shiftTabEvent);
      expect(document.activeElement).toBe(button2);

      cleanup();
    });

    it('calls onEscape when Escape pressed', () => {
      const container = document.createElement('div');
      const onEscape = vi.fn();
      a11y.trapFocus(container, onEscape);

      const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true });
      container.dispatchEvent(escapeEvent);
      expect(onEscape).toHaveBeenCalled();
    });
  });

  describe('createSkipLink', () => {
    it('creates an anchor with correct href and default text', () => {
      const skipLink = a11y.createSkipLink({ targetId: 'main' });
      expect(skipLink.tagName).toBe('A');
      expect(skipLink.getAttribute('href')).toBe('#main');
      expect(skipLink.textContent).toBe('Skip to main content');
      expect(skipLink.className).toBe('skip-link');
    });

    it('allows custom text and className', () => {
      const skipLink = a11y.createSkipLink({ targetId: 'main', text: 'Jump', className: 'custom' });
      expect(skipLink.textContent).toBe('Jump');
      expect(skipLink.className).toBe('custom');
    });

    it('toggles top style on focus/blur', () => {
      const skipLink = a11y.createSkipLink({ targetId: 'main' });
      const focusEvent = new Event('focus');
      const blurEvent = new Event('blur');

      skipLink.dispatchEvent(focusEvent);
      expect(skipLink.style.top).toBe('6px');

      skipLink.dispatchEvent(blurEvent);
      expect(skipLink.style.top).toBe('-40px');
    });
  });

  describe('getFocusableElements', () => {
    it('returns focusable elements within container', () => {
      const container = document.createElement('div');
      const button = document.createElement('button');
      button.textContent = 'Btn';
      const input = document.createElement('input');
      input.disabled = false;
      const disabledBtn = document.createElement('button');
      disabledBtn.disabled = true;
      container.append(button, input, disabledBtn);
      document.body.append(container);

      const focusable = a11y.getFocusableElements(container);
      expect(focusable).toContain(button);
      expect(focusable).toContain(input);
      expect(focusable).not.toContain(disabledBtn);
    });
  });

  describe('isFocusable', () => {
    it('returns true for focusable elements', () => {
      const button = document.createElement('button');
      expect(a11y.isFocusable(button)).toBe(true);
    });

    it('returns false for non-focusable elements', () => {
      const div = document.createElement('div');
      expect(a11y.isFocusable(div)).toBe(false);
    });

    it('returns false for disabled button', () => {
      const button = document.createElement('button');
      button.disabled = true;
      expect(a11y.isFocusable(button)).toBe(false);
    });
  });

  describe('setFocus', () => {
    it('focuses element when passed HTMLElement', () => {
      const input = document.createElement('input');
      document.body.append(input);
      const focusSpy = vi.spyOn(input, 'focus');
      a11y.setFocus(input);
      expect(focusSpy).toHaveBeenCalled();
    });

    it('finds element by ID string and focuses it', () => {
      const input = document.createElement('input');
      input.id = 'my-input';
      document.body.append(input);
      const focusSpy = vi.spyOn(input, 'focus');
      a11y.setFocus('my-input');
      expect(focusSpy).toHaveBeenCalled();
    });

    it('returns false and warns if element not found', () => {
      const consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      const result = a11y.setFocus('#nonexistent');
      expect(result).toBe(false);
      expect(consoleWarnSpy).toHaveBeenCalledWith('Element not found for focus: #nonexistent');
      consoleWarnSpy.mockRestore();
    });
  });

  describe('prefersReducedMotion', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockReturnValue({ matches: false }),
      });
    });

    it('returns true when matchMedia reports reduced motion', () => {
      window.matchMedia = vi.fn().mockReturnValue({ matches: true });
      expect(a11y.prefersReducedMotion()).toBe(true);
    });

    it('returns false when not reduced motion', () => {
      window.matchMedia = vi.fn().mockReturnValue({ matches: false });
      expect(a11y.prefersReducedMotion()).toBe(false);
    });
  });

  describe('prefersHighContrast', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockReturnValue({ matches: false }),
      });
    });

    it('reflects high contrast preference', () => {
      window.matchMedia = vi.fn().mockReturnValue({ matches: true });
      expect(a11y.prefersHighContrast()).toBe(true);
    });
  });

  describe('prefersDarkMode', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockReturnValue({ matches: false }),
      });
    });

    it('reflects dark mode preference', () => {
      window.matchMedia = vi.fn().mockReturnValue({ matches: true });
      expect(a11y.prefersDarkMode()).toBe(true);
    });
  });

  describe('prefersReducedData', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockReturnValue({ matches: false }),
      });
    });

    it('reflects reduced data preference', () => {
      window.matchMedia = vi.fn().mockReturnValue({ matches: true });
      expect(a11y.prefersReducedData()).toBe(true);
    });
  });

  describe('createLiveRegion', () => {
    it('creates a new live region with specified id', () => {
      const region = a11y.createLiveRegion('test', 'polite');
      expect(region.id).toBe('live-region-test');
      expect(region.getAttribute('role')).toBe('status');
      expect(region.getAttribute('aria-live')).toBe('polite');
      expect(region.className).toContain('sr-only');
    });

    it('returns existing region if already created', () => {
      const region1 = a11y.createLiveRegion('test');
      const region2 = a11y.createLiveRegion('test');
      expect(region1).toBe(region2);
    });

    it('appends region to body', () => {
      a11y.createLiveRegion('test');
      const regions = document.body.querySelectorAll('[role="status"]');
      expect(regions.length).toBeGreaterThan(0);
    });
  });

  describe('announceToRegion', () => {
    beforeEach(() => {
      vi.useFakeTimers();
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('announces message to existing or new region', async () => {
      a11y.announceToRegion('myregion', 'Hello');
      const region = document.body.querySelector('#live-region-myregion');
      expect(region).not.toBeNull();
      // Immediate check: text not yet set (setTimeout 100ms)
      expect(region?.textContent).toBe('');
      await vi.runAllTimersAsync();
      expect(region?.textContent).toBe('Hello');
    });
  });

  describe('removeLiveRegion', () => {
    it('removes region from DOM and map', () => {
      const region = a11y.createLiveRegion('toremove');
      expect(document.body.contains(region)).toBe(true);
      a11y.removeLiveRegion('toremove');
      expect(document.body.contains(region)).toBe(false);
    });
  });

  describe('generateAriaIds', () => {
    it('generates unique label and description IDs with prefix', () => {
      const ids = a11y.generateAriaIds('custom');
      expect(ids.labelId).toMatch(/^custom-label-[a-z0-9]+$/);
      expect(ids.descId).toMatch(/^custom-desc-[a-z0-9]+$/);
    });

    it('default prefix is a11y', () => {
      const ids = a11y.generateAriaIds();
      expect(ids.labelId).toMatch(/^a11y-label-/);
      expect(ids.descId).toMatch(/^a11y-desc-/);
    });
  });

  describe('setAriaDescribedBy', () => {
    it('adds description IDs to aria-describedby', () => {
      const el = document.createElement('input');
      a11y.setAriaDescribedBy(el, 'desc1', 'desc2');
      expect(el.getAttribute('aria-describedby')).toBe('desc1 desc2');
    });

    it('does not duplicate existing IDs', () => {
      const el = document.createElement('input');
      el.setAttribute('aria-describedby', 'desc1');
      a11y.setAriaDescribedBy(el, 'desc1', 'desc2');
      expect(el.getAttribute('aria-describedby')).toBe('desc1 desc2');
    });
  });

  describe('removeAriaDescribedBy', () => {
    it('removes specified description IDs', () => {
      const el = document.createElement('input');
      el.setAttribute('aria-describedby', 'desc1 desc2 desc3');
      a11y.removeAriaDescribedBy(el, 'desc2');
      expect(el.getAttribute('aria-describedby')).toBe('desc1 desc3');
    });
  });
});
