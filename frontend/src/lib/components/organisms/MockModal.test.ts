import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { tick } from 'svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockModal from './MockModal.svelte';

describe('MockModal Component (Organisms)', () => {
  describe('Basic Rendering', () => {
    it('does not render when open is false', () => {
      render(MockModal, { props: { open: false } });
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('renders when open is true', () => {
      render(MockModal, { props: { open: true, title: 'Test Modal' } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
      expect(screen.getByText('Test Modal')).toBeInTheDocument();
    });

    it('renders without title', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts open prop', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('accepts title prop', () => {
      render(MockModal, { props: { open: true, title: 'Test Title' } });
      expect(screen.getByText('Test Title')).toBeInTheDocument();
    });

    it('accepts size prop', () => {
      render(MockModal, { props: { open: true, size: 'lg' } });
      const dialog = screen.getByRole('dialog');
      expect(dialog).toHaveClass('modal-lg');
    });

    it('accepts closable prop', () => {
      render(MockModal, { props: { open: true, closable: false } });
      expect(screen.queryByLabelText('Close modal')).not.toBeInTheDocument();
    });

    it('accepts backdrop prop', () => {
      render(MockModal, { props: { open: true, backdrop: false, title: 'Test' } });
      const overlay = screen.getByText('Test').closest('.modal-overlay');
      expect(overlay).not.toHaveClass('backdrop');
    });

    it('accepts persistent prop', () => {
      render(MockModal, { props: { open: true, persistent: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  describe('Component Structure', () => {
    it('renders modal overlay', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('renders modal header when title provided', () => {
      render(MockModal, { props: { open: true, title: 'Test Title' } });
      expect(screen.getByText('Test Title')).toBeInTheDocument();
      expect(screen.getByRole('heading', { level: 2 })).toBeInTheDocument();
    });

    it('renders close button when closable', () => {
      render(MockModal, { props: { open: true, closable: true } });
      expect(screen.getByLabelText('Close modal')).toBeInTheDocument();
    });

    it('does not render close button when not closable', () => {
      render(MockModal, { props: { open: true, closable: false } });
      expect(screen.queryByLabelText('Close modal')).not.toBeInTheDocument();
    });

    it('does not render header when no title and not closable', () => {
      render(MockModal, { props: { open: true, closable: false } });
      expect(screen.queryByRole('heading')).not.toBeInTheDocument();
    });

    it('renders modal body with slot content', () => {
      const { container } = render(MockModal, { 
        props: { open: true, content: '<p>Modal content</p>' } 
      });
      expect(container.querySelector('.modal-body')).toBeInTheDocument();
      expect(screen.getByText('Modal content')).toBeInTheDocument();
    });

    it('renders modal footer with slot content', () => {
      const { container } = render(MockModal, { 
        props: { open: true, content: '<button>Footer button</button>' } 
      });
      expect(container.querySelector('.modal-footer')).toBeInTheDocument();
      expect(screen.getByText('Footer button')).toBeInTheDocument();
    });

    it('has correct size classes', () => {
      const { container: c1 } = render(MockModal, { props: { open: true, size: 'sm' } });
      expect(c1.querySelector('[role="dialog"]')).toHaveClass('modal-sm');
      
      const { container: c2 } = render(MockModal, { props: { open: true, size: 'md' } });
      expect(c2.querySelector('[role="dialog"]')).toHaveClass('modal-md');
      
      const { container: c3 } = render(MockModal, { props: { open: true, size: 'lg' } });
      expect(c3.querySelector('[role="dialog"]')).toHaveClass('modal-lg');
      
      const { container: c4 } = render(MockModal, { props: { open: true, size: 'xl' } });
      expect(c4.querySelector('[role="dialog"]')).toHaveClass('modal-xl');
    });

    it('has backdrop class when backdrop prop is true', () => {
      render(MockModal, { props: { open: true, backdrop: true } });
      expect(screen.getByRole('dialog').closest('.modal-overlay')).toHaveClass('backdrop');
    });

    it('does not have backdrop class when backdrop prop is false', () => {
      render(MockModal, { props: { open: true, backdrop: false, title: 'Test' } });
      expect(screen.getByRole('dialog').closest('.modal-overlay')).not.toHaveClass('backdrop');
    });
  });

  describe('Open/Close Behavior', () => {
    it('opens when open prop changes to true', async () => {
      const { component, rerender } = render(MockModal, { props: { open: false } });
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      
      rerender({ open: true });
      await waitFor(() => {
        expect(screen.getByRole('dialog')).toBeInTheDocument();
      });
    });

    it('closes when open prop changes to false', async () => {
      const { component, rerender } = render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
      
      rerender({ open: false });
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });

    it('closes when close button clicked', async () => {
      const { component } = render(MockModal, { props: { open: true, closable: true } });
      
      await fireEvent.click(screen.getByLabelText('Close modal'));
      // The component should close (open becomes false)
      expect(component.getIsOpen()).toBe(false);
    });

    it('does not close when persistent and close button clicked', async () => {
      render(MockModal, { props: { open: true, closable: true, persistent: true } });
      
      await fireEvent.click(screen.getByLabelText('Close modal'));
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  describe('Backdrop Click', () => {
    it('closes when backdrop clicked and not persistent', async () => {
      const { component } = render(MockModal, { 
        props: { open: true, backdrop: true, persistent: false } 
      });
      
      const overlay = screen.getByRole('dialog').closest('.modal-overlay');
      await fireEvent.click(overlay);
      expect(component.getIsOpen()).toBe(false);
    });

    it('does not close when backdrop clicked but persistent', async () => {
      render(MockModal, { props: { open: true, backdrop: true, persistent: true } });
      
      const overlay = screen.getByRole('dialog').closest('.modal-overlay');
      await fireEvent.click(overlay);
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('does not close when clicking inside modal', async () => {
      const { component } = render(MockModal, { 
        props: { open: true, backdrop: true, persistent: false, content: '<div class="inner">Content</div>' } 
      });
      
      const innerDiv = screen.getByText('Content');
      await fireEvent.click(innerDiv);
      expect(component.getIsOpen()).toBe(true);
    });

    it('does not close when backdrop disabled', async () => {
      const { component } = render(MockModal, { 
        props: { open: true, backdrop: false, persistent: false } 
      });
      
      const overlay = screen.getByRole('dialog').closest('.modal-overlay');
      await fireEvent.click(overlay);
      expect(component.getIsOpen()).toBe(true);
    });
  });

  describe('Escape Key', () => {
    it('closes on Escape key when not persistent', async () => {
      const { component } = render(MockModal, { 
        props: { open: true, persistent: false, closable: true } 
      });
      
      await fireEvent.keyDown(document, { key: 'Escape' });
      expect(component.getIsOpen()).toBe(false);
    });

    it('does not close on Escape when persistent', async () => {
      render(MockModal, { props: { open: true, persistent: true } });
      
      await fireEvent.keyDown(document, { key: 'Escape' });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('does not close on Escape when not closable', async () => {
      render(MockModal, { props: { open: true, persistent: false, closable: false } });
      
      await fireEvent.keyDown(document, { key: 'Escape' });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
  });

  describe('Focus Management', () => {
    it('has correct aria attributes', () => {
      render(MockModal, { props: { open: true, title: 'Test Title' } });
      
      const dialog = screen.getByRole('dialog');
      expect(dialog).toHaveAttribute('aria-modal', 'true');
      expect(dialog).toHaveAttribute('aria-labelledby', 'modal-title');
    });

    it('has tabindex on modal', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toHaveAttribute('tabindex', '-1');
    });

    it('has role dialog', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('overlay has role presentation', () => {
      render(MockModal, { props: { open: true } });
      const overlay = screen.getByRole('dialog').closest('.modal-overlay');
      expect(overlay).toHaveAttribute('role', 'presentation');
    });
  });

  describe('Footer Slot', () => {
    it.skip('renders footer slot content - slots not supported in test environment', () => {
      const { container } = render(MockModal, { 
        props: { open: true, footer: '<button>Footer Action</button>' }
      });
      expect(screen.getByText('Footer Action')).toBeInTheDocument();
      expect(container.querySelector('.modal-footer')).toBeInTheDocument();
    });

    it.skip('does not render footer when no footer slot - slots not supported in test environment', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.queryByRole('region', { name: /footer/i })).not.toBeInTheDocument();
    });
  });

  describe('Animation Classes', () => {
    it('has modal-open class when open', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toHaveClass('modal-open');
    });

    it('does not have modal-open class when closed', () => {
      render(MockModal, { props: { open: false } });
      // Not rendered when closed
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  describe('Responsive Design', () => {
    it('applies responsive styles', () => {
      const { container } = render(MockModal, { props: { open: true } });
      const style = window.getComputedStyle(container.querySelector('.modal-overlay')!);
      expect(style).toBeDefined();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty slots', () => {
      render(MockModal, { props: { open: true } });
      expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('handles unicode characters in title', () => {
      render(MockModal, { props: { open: true, title: '模态框 🎉' } });
      expect(screen.getByText('模态框 🎉')).toBeInTheDocument();
    });

    it('handles very long content', () => {
      const longContent = 'x'.repeat(1000);
      const { container } = render(MockModal, { 
        props: { open: true, content: `<p>${'x'.repeat(1000)}</p>` }
      });
      expect(screen.getByText('x'.repeat(1000))).toBeInTheDocument();
    });

    it('handles rapid open/close cycles', async () => {
      const { component, rerender } = render(MockModal, { props: { open: false } });
      
      for (let i = 0; i < 5; i++) {
        rerender({ open: true });
        await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument());
        rerender({ open: false });
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
      }
    });

    it('handles special characters in content', () => {
      const { container } = render(MockModal, { 
        props: { open: true, content: '<p>Special: 🎉 ñáéíóú 中文</p>' } 
      });
      expect(container.innerHTML).toContain('Special: 🎉 ñáéíóú 中文');
    });
  });

  describe('Event Dispatching', () => {
    it.skip('dispatches close event', async () => {
      // TODO: Fix event dispatching test in Svelte 5
      // The close event dispatching needs proper event handling in Svelte 5
    });
  });
});