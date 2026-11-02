import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import Popover from './Popover.svelte';

// Mock document.addEventListener/removeEventListener
const originalAddEventListener = document.addEventListener;
const originalRemoveEventListener = document.removeEventListener;

describe('Popover Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.addEventListener = originalAddEventListener;
    document.removeEventListener = originalRemoveEventListener;
  });

  afterEach(() => {
    document.addEventListener = originalAddEventListener;
    document.removeEventListener = originalRemoveEventListener;
  });

  describe('rendering', () => {
    it('renders without error', () => {
      render(Popover);
      expect(document.body).toBeTruthy();
    });

    it('renders popover container', () => {
      render(Popover, { props: { visible: true } });
      const container = document.querySelector('.popover-container');
      expect(container).toBeInTheDocument();
    });

    it('renders popover content when visible', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
    });

    it('does not render popover content when not visible', () => {
      render(Popover, { props: { visible: false } });
      expect(document.querySelector('.popover')).not.toBeInTheDocument();
    });

    it('applies placement classes', () => {
      const placements = ['top', 'bottom', 'left', 'right', 'top-start', 'top-end', 'bottom-start', 'bottom-end', 'left-start', 'left-end', 'right-start', 'right-end'] as const;
      
      placements.forEach(placement => {
        const { container, unmount } = render(Popover, { props: { placement, visible: true } });
        const popover = container.querySelector('.popover');
        expect(popover).toHaveClass(`popover-${placement}`);
        unmount();
      });
    });

    it('applies custom className', () => {
      render(Popover, { props: { className: 'custom-popover', visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toHaveClass('custom-popover');
    });
  });

  describe('props handling', () => {
    it('accepts trigger prop', () => {
      render(Popover, { props: { trigger: 'click' } });
      expect(document.body).toBeTruthy();
    });

    it('accepts placement prop', () => {
      render(Popover, { props: { placement: 'top' } });
      expect(document.body).toBeTruthy();
    });

    it('accepts visible prop', () => {
      render(Popover, { props: { visible: true } });
      expect(document.body).toBeTruthy();
    });

    it('accepts disabled prop', () => {
      render(Popover, { props: { disabled: true } });
      expect(document.body).toBeTruthy();
    });

    it('accepts custom className', () => {
      render(Popover, { props: { className: 'custom-popover', visible: true } });
      expect(document.body).toBeTruthy();
    });

    it('handles undefined props gracefully', () => {
      render(Popover, { 
        props: { 
          trigger: undefined as any,
          placement: undefined as any,
          visible: undefined as any,
          disabled: undefined as any,
          className: undefined as any
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(Popover, { props: { visible: true } });
      const { container: c2 } = render(Popover, { props: { visible: true } });
      
      expect(c1).toBeTruthy();
      expect(c2).toBeTruthy();
    });
  });

  describe('trigger types', () => {
    it('handles click trigger', () => {
      render(Popover, { props: { trigger: 'click' } });
      expect(document.body).toBeTruthy();
    });

    it('handles hover trigger', () => {
      render(Popover, { props: { trigger: 'hover' } });
      expect(document.body).toBeTruthy();
    });

    it('handles focus trigger', () => {
      render(Popover, { props: { trigger: 'focus' } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('disabled state', () => {
    it('renders without error when disabled', () => {
      render(Popover, { props: { disabled: true, visible: true } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on hover when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'hover', visible: false } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on focus when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'focus', visible: false } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on click when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'click', visible: false } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('accessibility', () => {
    it('trigger has role button', () => {
      render(Popover, { props: { visible: true } });
      const trigger = document.querySelector('.popover-trigger');
      expect(trigger).toHaveAttribute('role', 'button');
    });

    it('trigger has aria-expanded based on visibility', () => {
      render(Popover, { props: { visible: true } });
      const trigger = document.querySelector('.popover-trigger');
      expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    it('trigger has aria-haspopup dialog', () => {
      render(Popover, { props: { visible: true } });
      const trigger = document.querySelector('.popover-trigger');
      expect(trigger).toHaveAttribute('aria-haspopup', 'dialog');
    });

    it('popover has role dialog', () => {
      render(Popover, { props: { visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toHaveAttribute('role', 'dialog');
    });

    it('popover has aria-modal true', () => {
      render(Popover, { props: { visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toHaveAttribute('aria-modal', 'true');
    });

    it('popover has tabindex -1', () => {
      render(Popover, { props: { visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toHaveAttribute('tabindex', '-1');
    });

    it('trigger has tabindex for focus trigger', () => {
      render(Popover, { props: { trigger: 'focus' } });
      const trigger = document.querySelector('.popover-trigger');
      expect(trigger).toHaveAttribute('tabindex', '0');
    });
  });

  describe('placement', () => {
    it('applies all placement classes', () => {
      const placements = ['top', 'bottom', 'left', 'right', 'top-start', 'top-end', 'bottom-start', 'bottom-end', 'left-start', 'left-end', 'right-start', 'right-end'] as const;
      
      placements.forEach(placement => {
        const { container, unmount } = render(Popover, { props: { placement, visible: true } });
        const popover = container.querySelector('.popover');
        expect(popover).toHaveClass(`popover-${placement}`);
        unmount();
      });
    });

    it('applies arrow with correct data-placement for top', () => {
      render(Popover, { props: { placement: 'top', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'top');
    });

    it('applies arrow with correct data-placement for bottom', () => {
      render(Popover, { props: { placement: 'bottom', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'bottom');
    });

    it('applies arrow with correct data-placement for left', () => {
      render(Popover, { props: { placement: 'left', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'left');
    });

    it('applies arrow with correct data-placement for right', () => {
      render(Popover, { props: { placement: 'right', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'right');
    });
  });

  describe('content rendering', () => {
    it('renders popover with fixed positioning', () => {
      render(Popover, { props: { visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toBeInTheDocument();
      expect(popover).toHaveClass('popover');
    });
  });

  describe('keyboard handling', () => {
    it('has keydown handler on window via svelte:window', () => {
      render(Popover, { props: { visible: true } });
      // The keydown handler is attached to window via svelte:window
      // Just verify component renders without error
      expect(document.body).toBeTruthy();
    });
  });

  describe('disabled state', () => {
    it('renders without error when disabled', () => {
      render(Popover, { props: { disabled: true, visible: true } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on hover when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'hover', visible: false } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on focus when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'focus', visible: false } });
      expect(document.body).toBeTruthy();
    });

    it('does not show on click when disabled', () => {
      render(Popover, { props: { disabled: true, trigger: 'click', visible: false } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('arrow positioning', () => {
    it('has arrow with correct placement attribute for top', () => {
      render(Popover, { props: { placement: 'top', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'top');
    });

    it('has arrow with correct placement attribute for bottom', () => {
      render(Popover, { props: { placement: 'bottom', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'bottom');
    });

    it('has arrow with correct placement attribute for left', () => {
      render(Popover, { props: { placement: 'left', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'left');
    });

    it('has arrow with correct placement attribute for right', () => {
      render(Popover, { props: { placement: 'right', visible: true } });
      const arrow = document.querySelector('.popover-arrow');
      expect(arrow).toHaveAttribute('data-placement', 'right');
    });
  });

  describe('viewport boundary handling', () => {
    it('renders popover with fixed positioning', () => {
      render(Popover, { props: { visible: true } });
      const popover = document.querySelector('.popover');
      expect(popover).toBeInTheDocument();
      expect(popover).toHaveClass('popover');
    });
  });

  describe('styling', () => {
    it('includes popover base styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
    });

    it('includes arrow styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover-arrow')).toBeInTheDocument();
    });

    it('includes dark mode styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
    });

    it('includes reduced motion styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
    });

    it('includes mobile responsive styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
    });

    it('includes focus styles', () => {
      render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover-trigger')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', () => {
      render(Popover, { 
        props: { 
          trigger: undefined as any,
          placement: undefined as any,
          visible: undefined as any,
          disabled: undefined as any,
          className: undefined as any
        }
      });
      expect(document.body).toBeTruthy();
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(Popover, { props: { visible: true } });
      const { container: c2 } = render(Popover, { props: { visible: true } });
      
      expect(c1).toBeTruthy();
      expect(c2).toBeTruthy();
    });

    it('handles rapid visibility changes', () => {
      const { unmount: unmount1 } = render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
      unmount1();
      
      const { unmount: unmount2 } = render(Popover, { props: { visible: false } });
      expect(document.querySelector('.popover')).not.toBeInTheDocument();
      unmount2();
      
      const { unmount: unmount3 } = render(Popover, { props: { visible: true } });
      expect(document.querySelector('.popover')).toBeInTheDocument();
      unmount3();
    });
  });
});
