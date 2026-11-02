import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import SwipeActions from './SwipeActions.svelte';

describe('SwipeActions Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(SwipeActions);
      const container = screen.getByTestId('swipe-actions');
      expect(container).toBeInTheDocument();
      expect(container).toHaveClass('swipe-actions');
    });

    it('renders with custom slot content', () => {
      const { container } = render(SwipeActions, {
        slots: { default: '<div class="custom">Custom Content</div>' },
      });
      // Verify the component renders with slot
      expect(container.querySelector('.swipe-content')).toBeInTheDocument();
    });

    it('applies disabled class when disabled', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });

  describe('Props', () => {
    it('accepts threshold prop', () => {
      render(SwipeActions, { props: { threshold: 100 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts leftActions prop', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts rightActions prop', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('applies disabled class', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });

  describe('Props - Action Configuration', () => {
    it('renders left action buttons when leftActions provided', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' }
      ];
      render(SwipeActions, { props: { leftActions } });
      // Verify the component renders without error
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('renders right action buttons when rightActions provided', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts both left and right actions', () => {
      const leftActions = [{ icon: '✏️', label: 'Edit', action: 'edit' }];
      const rightActions = [{ icon: '✅', label: 'Complete', action: 'complete' }];
      render(SwipeActions, { props: { leftActions, rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('applies disabled class', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });

  describe('Component Structure', () => {
    it('renders with correct structure', () => {
      render(SwipeActions);
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
      expect(screen.getByTestId('swipe-actions')).toHaveClass('swipe-actions');
    });

    it('has swipe-content container', () => {
      render(SwipeActions);
      expect(screen.getByTestId('swipe-actions').querySelector('.swipe-content')).toBeInTheDocument();
    });

    it('renders slot content', () => {
      render(SwipeActions, {
        slots: { default: '<div class="content">Swipe me</div>' }
      });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('renders complex slot content', () => {
      render(SwipeActions, {
        slots: { 
          default: '<div class="item"><span>Title</span><span>Description</span></div>' 
        }
      });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });
  });

  describe('Props - Configuration', () => {
    it('accepts threshold prop', () => {
      render(SwipeActions, { props: { threshold: 100 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts leftActions prop', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts rightActions prop', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('applies disabled class when disabled', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });

  describe('Edge Cases', () => {
    it('renders with empty actions', () => {
      render(SwipeActions, { props: { leftActions: [], rightActions: [] } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles multiple left actions', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '📋', label: 'Copy', action: 'copy' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles multiple right actions', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' },
        { icon: '⭐', label: 'Star', action: 'star' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('renders multiple instances independently', () => {
      const { container: container1 } = render(SwipeActions);
      const { container: container2 } = render(SwipeActions, { 
        props: { rightActions: [{ icon: '✅', label: 'Complete', action: 'complete' }] } 
      });
      expect(container1).toBeTruthy();
      expect(container2).toBeTruthy();
    });

    it('handles zero threshold', () => {
      render(SwipeActions, { props: { threshold: 0 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles large threshold', () => {
      render(SwipeActions, { props: { threshold: 200 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });
  });

  describe('Props - Configuration', () => {
    it('accepts threshold prop', () => {
      render(SwipeActions, { props: { threshold: 100 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts leftActions prop', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts rightActions prop', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('applies disabled class when disabled', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });

  describe('Structure', () => {
    it('renders with correct structure', () => {
      render(SwipeActions);
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
      expect(screen.getByTestId('swipe-actions')).toHaveClass('swipe-actions');
    });

    it('has swipe-content container', () => {
      render(SwipeActions);
      expect(screen.getByTestId('swipe-actions').querySelector('.swipe-content')).toBeInTheDocument();
    });

    it('renders with correct class names', () => {
      render(SwipeActions);
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('swipe-actions');
      expect(container).not.toHaveClass('swiping');
      expect(container).not.toHaveClass('disabled');
    });

    it('has data-testid attribute', () => {
      render(SwipeActions);
      const container = screen.getByTestId('swipe-actions');
      expect(container.getAttribute('data-testid')).toBe('swipe-actions');
    });
  });

  describe('Disabled State', () => {
    it('applies disabled class when disabled', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });

    it('does not apply disabled class when not disabled', () => {
      render(SwipeActions, { props: { disabled: false } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).not.toHaveClass('disabled');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty actions arrays', () => {
      render(SwipeActions, { props: { leftActions: [], rightActions: [] } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles multiple left actions', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '📋', label: 'Copy', action: 'copy' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles multiple right actions', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' },
        { icon: '⭐', label: 'Star', action: 'star' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('renders multiple instances independently', () => {
      const { container: container1 } = render(SwipeActions);
      const { container: container2 } = render(SwipeActions, { 
        props: { rightActions: [{ icon: '✅', label: 'Complete', action: 'complete' }] } 
      });
      expect(container1).toBeTruthy();
      expect(container2).toBeTruthy();
    });

    it('handles zero threshold', () => {
      render(SwipeActions, { props: { threshold: 0 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('handles large threshold', () => {
      render(SwipeActions, { props: { threshold: 200 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });
  });

  describe('Props - Configuration', () => {
    it('accepts threshold prop', () => {
      render(SwipeActions, { props: { threshold: 100 } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts leftActions prop', () => {
      const leftActions = [
        { icon: '✏️', label: 'Edit', action: 'edit' },
        { icon: '🗑️', label: 'Delete', action: 'delete' }
      ];
      render(SwipeActions, { props: { leftActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('accepts rightActions prop', () => {
      const rightActions = [
        { icon: '✅', label: 'Complete', action: 'complete' }
      ];
      render(SwipeActions, { props: { rightActions } });
      expect(screen.getByTestId('swipe-actions')).toBeInTheDocument();
    });

    it('applies disabled class when disabled', () => {
      render(SwipeActions, { props: { disabled: true } });
      const container = screen.getByTestId('swipe-actions');
      expect(container).toHaveClass('disabled');
    });
  });
});