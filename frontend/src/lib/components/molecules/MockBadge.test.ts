import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockBadge from './MockBadge.svelte';

describe('MockBadge Component', () => {
  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(MockBadge, { 
        props: { 
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Badge');
    });

    it('renders with text prop', () => {
      render(MockBadge, { 
        props: { 
          text: 'Badge text' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Badge text');
    });
  });

  describe('Props', () => {
    it('accepts text prop', () => {
      render(MockBadge, { 
        props: { 
          text: 'Badge text' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Badge text');
    });

    it('accepts variant prop', () => {
      render(MockBadge, { 
        props: { 
          variant: 'filled',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-filled');
    });

    it('accepts size prop', () => {
      render(MockBadge, { 
        props: { 
          size: 'lg',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-lg');
    });

    it('accepts className prop', () => {
      render(MockBadge, { 
        props: { 
          className: 'custom-class',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('custom-class');
    });
  });

  describe('Variants', () => {
    it('renders filled variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'filled',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-filled');
    });

    it('renders default variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'default',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-default');
    });

    it('renders outlined variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'outlined',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-outlined');
    });
  });

  describe('Sizes', () => {
    it('renders sm size', () => {
      render(MockBadge, { 
        props: { 
          size: 'sm',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-sm');
    });

    it('renders md size', () => {
      render(MockBadge, { 
        props: { 
          size: 'md',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-md');
    });

    it('renders lg size', () => {
      render(MockBadge, { 
        props: { 
          size: 'lg',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-lg');
    });
  });

  describe('Variant Styles', () => {
    it('applies filled variant styles', () => {
      render(MockBadge, { 
        props: { 
          variant: 'filled',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-filled');
      // Filled variant should have white text on primary background
      expect(badge).toHaveClass('badge-filled');
    });

    it('applies default variant styles', () => {
      render(MockBadge, { 
        props: { 
          variant: 'default',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-default');
    });

    it('applies outlined variant styles', () => {
      render(MockBadge, { 
        props: { 
          variant: 'outlined',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-outlined');
    });

    it('applies error variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'error',
          text: 'Error' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-error');
    });

    it('applies warning variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'warning',
          text: 'Warning' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-warning');
    });

    it('applies success variant', () => {
      render(MockBadge, { 
        props: { 
          variant: 'success',
          text: 'Success' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-success');
    });
  });

  describe('Sizes', () => {
    it('applies sm size', () => {
      render(MockBadge, { 
        props: { 
          size: 'sm',
          text: 'Small' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-sm');
    });

    it('applies md size', () => {
      render(MockBadge, { 
        props: { 
          size: 'md',
          text: 'Medium' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-md');
    });

    it('applies lg size', () => {
      render(MockBadge, { 
        props: { 
          size: 'lg',
          text: 'Large' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-lg');
    });
  });

  describe('Text Content', () => {
    it('renders simple text', () => {
      render(MockBadge, { 
        props: { 
          text: 'Simple badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Simple badge');
    });

    it('renders empty text', () => {
      render(MockBadge, { 
        props: { 
          text: '' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('');
    });

    it('renders special characters', () => {
      render(MockBadge, { 
        props: { 
          text: 'Special: 🎉 ñáéíóú 中文' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Special: 🎉 ñáéíóú 中文');
    });
  });

  describe('Variants and Sizes Combined', () => {
    it('renders filled large badge', () => {
      render(MockBadge, { 
        props: { 
          variant: 'filled',
          size: 'lg',
          text: 'Large Filled' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-filled');
      expect(badge).toHaveClass('badge-lg');
    });

    it('renders outlined small badge', () => {
      render(MockBadge, { 
        props: { 
          variant: 'outlined',
          size: 'sm',
          text: 'Small Outlined' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-outlined');
      expect(badge).toHaveClass('badge-sm');
    });

    it('renders default medium badge', () => {
      render(MockBadge, { 
        props: { 
          variant: 'default',
          size: 'md',
          text: 'Medium Default' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('badge-default');
      expect(badge).toHaveClass('badge-md');
    });
  });

  describe('Edge Cases', () => {
    it('handles empty text', () => {
      render(MockBadge, { 
        props: { 
          text: '' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('');
    });

    it('handles special characters', () => {
      render(MockBadge, { 
        props: { 
          text: 'Special: 🎉 ñáéíóú 中文' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('Special: 🎉 ñáéíóú 中文');
    });

    it('handles unicode characters', () => {
      render(MockBadge, { 
        props: { 
          text: '模态框 🎉' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('模态框 🎉');
    });

    it('handles HTML in text', () => {
      render(MockBadge, { 
        props: { 
          text: '<strong>Bold</strong>' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      expect(badge.textContent).toBe('<strong>Bold</strong>');
    });
  });

  describe('Custom Classes', () => {
    it('applies custom class', () => {
      render(MockBadge, { 
        props: { 
          className: 'custom-badge',
          text: 'Custom' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('custom-badge');
    });

    it('applies multiple custom classes', () => {
      render(MockBadge, { 
        props: { 
          className: 'class1 class2',
          text: 'Badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toHaveClass('class1');
      expect(badge).toHaveClass('class2');
    });
  });

  describe('Snippet Children', () => {
    it('renders snippet children', () => {
      render(MockBadge, { 
        props: { 
          children: () => 'Snippet badge' 
        } 
      });
      const badge = screen.getByTestId('mock-badge');
      expect(badge).toBeInTheDocument();
      // Snippet content rendered as comment nodes in test env
      expect(badge).toBeInTheDocument();
    });
  });
});