import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Badge from './Badge.svelte';

describe('Badge Component', () => {
  describe('rendering with count', () => {
    it('renders nothing when count is 0 and showZero false', () => {
      const { container } = render(Badge);
      const badge = container.querySelector('.badge');
      expect(badge).toBeNull();
    });

    it('renders badge with count when count > 0', () => {
      render(Badge, { props: { count: 5 } });
      expect(screen.getByText('5')).toBeTruthy();
    });

    it('renders badge when showZero true and count 0', () => {
      render(Badge, { props: { count: 0, showZero: true } });
      expect(screen.getByText('0')).toBeTruthy();
    });

    it('displays maxCount+ when count exceeds maxCount', () => {
      render(Badge, { props: { count: 150, maxCount: 99 } });
      expect(screen.getByText('99+')).toBeTruthy();
    });

    it('does not truncate when count equals maxCount', () => {
      render(Badge, { props: { count: 99, maxCount: 99 } });
      expect(screen.getByText('99')).toBeTruthy();
    });
  });

  describe('dot variant', () => {
    it('renders dot badge when dot prop true and count > 0', () => {
      const { container } = render(Badge, { props: { dot: true, count: 1 } });
      const badge = container.querySelector('.badge-dot');
      expect(badge).toBeTruthy();
    });

    it('dot badge does not show count text', () => {
      render(Badge, { props: { dot: true, count: 5 } });
      expect(screen.queryByText('5')).toBeNull();
    });

    it('does not render dot when count is 0', () => {
      const { container } = render(Badge, { props: { dot: true, count: 0 } });
      const badge = container.querySelector('.badge-dot');
      expect(badge).toBeNull();
    });
  });

  describe('status colors', () => {
    const statuses = ['success', 'error', 'warning', 'info'] as const;
    statuses.forEach((status) => {
      it(`applies ${status} status class`, () => {
        const { container } = render(Badge, { props: { status, count: 1 } });
        const badge = container.querySelector('.badge');
        expect(badge).toHaveClass(`badge-${status}`);
      });
    });

    it('does not apply status class when status null', () => {
      const { container } = render(Badge, { props: { count: 1 } });
      const badge = container.querySelector('.badge');
      expect(badge).not.toHaveClass('badge-success');
      expect(badge).not.toHaveClass('badge-error');
      expect(badge).not.toHaveClass('badge-warning');
      expect(badge).not.toHaveClass('badge-info');
    });
  });

  describe('size variants', () => {
    const sizes: Array<'sm' | 'md' | 'lg'> = ['sm', 'md', 'lg'];
    sizes.forEach((size) => {
      it(`applies ${size} size class`, () => {
        const { container } = render(Badge, { props: { count: 1, size } });
        const badge = container.querySelector('.badge');
        expect(badge).toHaveClass(`badge-${size}`);
      });
    });
  });

  describe('variant styles', () => {
    const variants: Array<'default' | 'outlined' | 'filled'> = ['default', 'outlined', 'filled'];
    variants.forEach((variant) => {
      it(`applies ${variant} variant class`, () => {
        const { container } = render(Badge, { props: { count: 1, variant } });
        const badge = container.querySelector('.badge');
        expect(badge).toHaveClass(`badge-${variant}`);
      });
    });
  });

  describe('positioning', () => {
    const positions: Array<'top-right' | 'top-left' | 'bottom-right' | 'bottom-left'> = ['top-right', 'top-left', 'bottom-right', 'bottom-left'];
    positions.forEach((position) => {
      it(`applies ${position} position class`, () => {
        const { container } = render(Badge, { props: { count: 1, position } });
        const badge = container.querySelector('.badge');
        expect(badge).toHaveClass(`badge-${position}`);
      });
    });
  });

  describe('wrapper classes', () => {
    it('wrapper has badge-has-content when badge should show', () => {
      const { container } = render(Badge, { props: { count: 3 } });
      const wrapper = container.querySelector('.badge-wrapper');
      expect(wrapper).toHaveClass('badge-has-content');
    });

    it('wrapper does not have badge-has-content when badge hidden', () => {
      const { container } = render(Badge);
      const wrapper = container.querySelector('.badge-wrapper');
      expect(wrapper).not.toHaveClass('badge-has-content');
    });
  });

  describe('aria-label', () => {
    it('badge has aria-label with count items when text not provided', () => {
      render(Badge, { props: { count: 5 } });
      const badge = document.querySelector('.badge');
      expect(badge).toHaveAttribute('aria-label', '5 items');
    });

    it('badge uses custom text for aria-label when text provided', () => {
      render(Badge, { props: { count: 5, text: 'Notifications' } });
      const badge = document.querySelector('.badge');
      expect(badge).toHaveAttribute('aria-label', 'Notifications');
    });
  });
});
