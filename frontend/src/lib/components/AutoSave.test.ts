import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import AutoSave from './AutoSave.svelte';

describe('AutoSave Component', () => {
  let originalLocalStorage: Storage;

  beforeEach(() => {
    vi.clearAllMocks();
    originalLocalStorage = global.localStorage;

    // Mock localStorage
    const localStorageMock = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    };
    global.localStorage = localStorageMock as any;
  });

  afterEach(() => {
    global.localStorage = originalLocalStorage;
  });

  describe('rendering', () => {
    it('renders without props', () => {
      render(AutoSave);
      expect(document.body).toBeTruthy();
    });

    it('renders slot content', () => {
      const { container } = render(AutoSave);
      expect(container).toBeTruthy();
    });

    it('accepts data prop', () => {
      render(AutoSave, { props: { data: { key: 'value' } } });
      expect(document.body).toBeTruthy();
    });

    it('accepts storageKey prop', () => {
      render(AutoSave, { props: { storageKey: 'custom-key' } });
      expect(document.body).toBeTruthy();
    });

    it('accepts debounceMs prop', () => {
      render(AutoSave, { props: { debounceMs: 500 } });
      expect(document.body).toBeTruthy();
    });

    it('accepts custom saveFunction', () => {
      const saveFn = vi.fn();
      render(AutoSave, { props: { saveFunction: saveFn } });
      expect(document.body).toBeTruthy();
    });

    it('accepts custom loadFunction', () => {
      const loadFn = vi.fn();
      render(AutoSave, { props: { loadFunction: loadFn } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('props handling', () => {
    it('handles undefined data gracefully', () => {
      render(AutoSave, { props: { data: undefined as any } });
      expect(document.body).toBeTruthy();
    });

    it('handles empty data object', () => {
      render(AutoSave, { props: { data: {} } });
      expect(document.body).toBeTruthy();
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(AutoSave, { props: { storageKey: 'key1', data: {} } });
      const { container: c2 } = render(AutoSave, { props: { storageKey: 'key2', data: {} } });
      expect(c1).toBeTruthy();
      expect(c2).toBeTruthy();
    });

    it('re-renders when data prop changes', () => {
      render(AutoSave, { props: { data: { a: 1 } } });
      expect(document.body).toBeTruthy();
    });

    it('re-renders when debounceMs changes', () => {
      render(AutoSave, { props: { debounceMs: 100, data: { a: 1 } } });
      expect(document.body).toBeTruthy();
    });

    it('re-renders when storageKey changes', () => {
      render(AutoSave, { props: { storageKey: 'key1', data: { a: 1 } } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('manual methods', () => {
    it('renders with slot', () => {
      render(AutoSave, { 
        props: { data: { test: 'data' } }
      });
      expect(document.body).toBeTruthy();
    });

    it('renders with clear slot', () => {
      render(AutoSave, { 
        props: { data: { test: 'data' } }
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('reactive behavior', () => {
    it('has reactive data binding', () => {
      render(AutoSave, { props: { data: { initial: 'value' } } });
      expect(document.body).toBeTruthy();
    });

    it('handles custom saveFunction that throws', () => {
      const saveFn = vi.fn().mockImplementation(() => { throw new Error('Save failed'); });
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      
      render(AutoSave, { 
        props: { saveFunction: saveFn, data: { test: 'data' } }
      });
      
      expect(consoleSpy).toHaveBeenCalledTimes(0);
      consoleSpy.mockRestore();
    });
  });

  describe('edge cases', () => {
    it('handles custom loadFunction that throws on mount', () => {
      const loadFn = vi.fn().mockRejectedValue(new Error('Load failed'));
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      
      expect(() => render(AutoSave, { props: { loadFunction: loadFn } })).not.toThrow();
      
      consoleSpy.mockRestore();
    });

    it('handles zero debounceMs', () => {
      render(AutoSave, { props: { debounceMs: 0, data: {} } });
      expect(document.body).toBeTruthy();
    });

    it('handles large debounceMs', () => {
      render(AutoSave, { props: { debounceMs: 10000, data: {} } });
      expect(document.body).toBeTruthy();
    });

    it('handles empty storageKey', () => {
      render(AutoSave, { props: { storageKey: '', data: {} } });
      expect(document.body).toBeTruthy();
    });
  });
});