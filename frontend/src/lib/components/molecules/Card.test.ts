import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Card from './Card.svelte';

describe('Card Component', () => {
  describe('Rendering', () => {
    it('renders card element', () => {
      const { container } = render(Card);
      expect(container.querySelector('.card')).toBeInTheDocument();
    });

    it('applies size classes correctly', () => {
      const { container: sm } = render(Card, { props: { size: 'sm' } });
      expect(sm.querySelector('.card')).toHaveClass('card-sm');

      const { container: md } = render(Card, { props: { size: 'md' } });
      expect(md.querySelector('.card')).toHaveClass('card-md');

      const { container: lg } = render(Card, { props: { size: 'lg' } });
      expect(lg.querySelector('.card')).toHaveClass('card-lg');
    });

    it('applies variant classes', () => {
      const variants = ['default', 'outlined', 'filled', 'elevated'] as const;
      variants.forEach((variant) => {
        const { container } = render(Card, { props: { variant } });
        expect(container.querySelector('.card')).toHaveClass(`card-${variant}`);
      });
    });

    it('adds hoverable class', () => {
      const { container } = render(Card, { props: { hoverable: true } });
      expect(container.querySelector('.card')).toHaveClass('card-hoverable');
    });

    it('adds clickable attributes', () => {
      const { container } = render(Card, { props: { clickable: true } });
      const card = container.querySelector('.card');
      expect(card).toHaveClass('card-clickable');
      expect(card).toHaveAttribute('role', 'button');
      expect(card).toHaveAttribute('tabindex', '0');
    });

    it('removes clickable attributes when false', () => {
      const { container } = render(Card, { props: { clickable: false } });
      const card = container.querySelector('.card');
      expect(card).not.toHaveAttribute('role');
      expect(card).not.toHaveAttribute('tabindex');
    });

    it('renders cover image', () => {
      const { container } = render(Card, { props: { cover: '/cover.jpg' } });
      const img = container.querySelector('.card-cover img');
      expect(img).toBeInTheDocument();
      expect(img?.getAttribute('src')).toBe('/cover.jpg');
    });

    it('does not render cover when null', () => {
      const { container } = render(Card, { props: { cover: null } });
      expect(container.querySelector('.card-cover')).not.toBeInTheDocument();
    });
  });

  describe('Actions', () => {
    it('renders action buttons', () => {
      const actions = [{ label: 'Edit' }, { label: 'Delete' }];
      render(Card, { props: { actions } });
      expect(screen.getByText('Edit')).toBeInTheDocument();
      expect(screen.getByText('Delete')).toBeInTheDocument();
    });

    it('renders icon-only action', () => {
      const actions = [{ icon: '❤️' }];
      render(Card, { props: { actions } });
      expect(screen.getByText('❤️')).toBeInTheDocument();
    });
  });

  describe('Loading', () => {
    it('shows loading overlay when loading', () => {
      const { container } = render(Card, { props: { loading: true } });
      expect(container.querySelector('.card-loading-overlay')).toBeInTheDocument();
    });

    it('hides loading overlay when not loading', () => {
      const { container } = render(Card, { props: { loading: false } });
      expect(container.querySelector('.card-loading-overlay')).not.toBeInTheDocument();
    });

    it('loading overlay is aria-hidden', () => {
      const { container } = render(Card, { props: { loading: true } });
      expect(container.querySelector('.card-loading-overlay')).toHaveAttribute('aria-hidden', 'true');
    });
  });
});
