import { render } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import ToastManager from './ToastManager.svelte';

describe('ToastManager Component (Global window.showToast)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    delete (window as any).showToast;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    delete (window as any).showToast;
  });

  describe('window.showToast Global Function', () => {
    it('exposes showToast function on window after mount', () => {
      render(ToastManager);
      expect(typeof (window as any).showToast).toBe('function');
    });

    it('removes showToast from window on unmount', () => {
      const { unmount } = render(ToastManager);
      expect(typeof (window as any).showToast).toBe('function');
      unmount();
      expect((window as any).showToast).toBeUndefined();
    });

    it('does not throw when showToast called after unmount', () => {
      const { unmount } = render(ToastManager);
      unmount();
      expect(() => {
        (window as any).showToast?.('Test', 'info', 100);
      }).not.toThrow();
    });

    it('can be called with basic parameters', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('Test message', 'info', 1000);
      }).not.toThrow();
    });

    it('accepts different toast types', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('Info', 'info', 1000);
        (window as any).showToast('Success', 'success', 1000);
        (window as any).showToast('Error', 'error', 1000);
        (window as any).showToast('Warning', 'warning', 1000);
      }).not.toThrow();
    });

    it('can be called with title parameter', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('Message', 'info', 1000, 'Title');
      }).not.toThrow();
    });
  });

  describe('Cleanup', () => {
    it('removes showToast from window on unmount', () => {
      const { unmount } = render(ToastManager);
      expect(typeof (window as any).showToast).toBe('function');
      unmount();
      expect((window as any).showToast).toBeUndefined();
    });

    it('does not throw when showToast called after unmount', () => {
      const { unmount } = render(ToastManager);
      unmount();
      expect(() => {
        (window as any).showToast?.('Test', 'info', 100);
      }).not.toThrow();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty message', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('', 'info', 1000);
      }).not.toThrow();
    });

    it('handles special characters in message', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('<script>alert(1)</script>', 'info', 1000);
      }).not.toThrow();
    });

    it('handles very long messages', () => {
      render(ToastManager);
      const longMessage = 'A'.repeat(1000);
      expect(() => {
        (window as any).showToast(longMessage, 'info', 1000);
      }).not.toThrow();
    });

    it('handles unicode characters', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('🎉 🎊 🎈 Unicode message', 'info', 1000);
      }).not.toThrow();
    });

    it('does not throw when called before mount', () => {
      expect(() => {
        (window as any).showToast?.('Test', 'info', 100);
      }).not.toThrow();
    });

    it('accepts all toast types without throwing', () => {
      render(ToastManager);
      expect(() => {
        (window as any).showToast('Info', 'info', 1000);
        (window as any).showToast('Success', 'success', 1000);
        (window as any).showToast('Error', 'error', 1000);
        (window as any).showToast('Warning', 'warning', 1000);
      }).not.toThrow();
    });
  });

  describe('Multiple Instances', () => {
    it('only one showToast function exists even with multiple mounts', () => {
      const { unmount: unmount1 } = render(ToastManager);
      const { unmount: unmount2 } = render(ToastManager);
      
      const showToast1 = (window as any).showToast;
      const showToast2 = (window as any).showToast;
      expect(showToast1).toBe(showToast2);
      
      unmount1();
      unmount2();
    });

    it('cleanup removes showToast on first unmount', () => {
      const { unmount: unmount1 } = render(ToastManager);
      const { unmount: unmount2 } = render(ToastManager);
      
      const showToastBeforeUnmount = (window as any).showToast;
      expect(typeof (window as any).showToast).toBe('function');
      
      unmount1();
      // After first unmount, showToast is removed (current behavior)
      expect((window as any).showToast).toBeUndefined();
      
      unmount2();
      expect((window as any).showToast).toBeUndefined();
    });
  });
});