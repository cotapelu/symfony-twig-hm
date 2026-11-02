import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Icon from './atoms/Icon.svelte';

describe('Icon Component', () => {
  describe('rendering', () => {
    it('renders fallback "?" when no name provided', () => {
      render(Icon);
      const fallback = screen.getByText('?');
      expect(fallback).toBeTruthy();
      expect(fallback).toHaveClass('icon');
    });

    it('renders with default size md', () => {
      render(Icon);
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-md');
    });

    it('renders SVG when valid icon name provided', () => {
      render(Icon, { props: { name: 'home' } });
      const svg = document.querySelector('svg');
      expect(svg).toBeTruthy();
      expect(svg).toHaveAttribute('viewBox', '0 0 24 24');
      const path = svg?.querySelector('path');
      expect(path).toBeTruthy();
      expect(path?.getAttribute('d')).toBeTruthy();
    });

    it('renders different size classes correctly', () => {
      const sizes = ['xs', 'sm', 'md', 'lg', 'xl'] as const;
      sizes.forEach((size) => {
        const { unmount } = render(Icon, { props: { size } });
        const icon = document.querySelector('.icon');
        expect(icon).toHaveClass(`icon-${size}`);
        unmount();
      });
    });

    it('applies custom color via style', () => {
      render(Icon, { props: { color: 'red' } });
      const icon = document.querySelector('.icon');
      expect(icon?.getAttribute('style')).toContain('color: red');
    });

    it('applies rotation transform based on rotate prop', () => {
      render(Icon, { props: { rotate: 45 } });
      const icon = document.querySelector('.icon');
      expect(icon?.getAttribute('style')).toContain('transform: rotate(45deg)');
    });

    it('applies spin class when spin prop is true', () => {
      render(Icon, { props: { spin: true } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-spin');
    });

    it('does not apply spin class when spin prop is false', () => {
      render(Icon, { props: { spin: false } });
      const icon = document.querySelector('.icon');
      expect(icon).not.toHaveClass('icon-flip-horizontal');
      expect(icon).not.toHaveClass('icon-spin');
    });

    it('applies flip-horizontal class when flip is "horizontal"', () => {
      render(Icon, { props: { flip: 'horizontal' } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-flip-horizontal');
    });

    it('applies flip-vertical class when flip is "vertical"', () => {
      render(Icon, { props: { flip: 'vertical' } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-flip-vertical');
    });

    it('applies flip-both class when flip is "both"', () => {
      render(Icon, { props: { flip: 'both' } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-flip-both');
    });

    it('does not apply flip class when flip is null', () => {
      render(Icon, { props: { flip: null } });
      const icon = document.querySelector('.icon');
      expect(icon).not.toHaveClass('icon-flip-horizontal');
      expect(icon).not.toHaveClass('icon-flip-vertical');
      expect(icon).not.toHaveClass('icon-flip-both');
    });

    it('combines multiple classes correctly', () => {
      render(Icon, { props: { name: 'search', size: 'lg', spin: true, color: 'blue' } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass('icon-lg', 'icon-spin');
      expect(icon?.getAttribute('style')).toContain('color: blue');
    });
  });

  describe('accessibility', () => {
    it('svg has role="img"', () => {
      render(Icon, { props: { name: 'home' } });
      const svg = document.querySelector('svg');
      expect(svg).toHaveAttribute('role', 'img');
    });

    it('svg has aria-hidden="true"', () => {
      render(Icon, { props: { name: 'home' } });
      const svg = document.querySelector('svg');
      expect(svg).toHaveAttribute('aria-hidden', 'true');
    });

    it('fallback span has aria-hidden="true"', () => {
      render(Icon);
      const span = document.querySelector('span');
      expect(span).toHaveAttribute('aria-hidden', 'true');
    });
  });

  describe('icon paths', () => {
    it('renders correct path for "home" icon', () => {
      render(Icon, { props: { name: 'home' } });
      const path = document.querySelector('svg path');
      expect(path?.getAttribute('d')).toBe('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6');
    });

    it('renders correct path for "search" icon', () => {
      render(Icon, { props: { name: 'search' } });
      const path = document.querySelector('svg path');
      expect(path?.getAttribute('d')).toBe('M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z');
    });

    it('renders unknown icon as fallback', () => {
      render(Icon, { props: { name: 'unknown-icon' } });
      const fallback = document.querySelector('span');
      expect(fallback).toBeTruthy();
      expect(fallback?.textContent).toBe('?');
    });
  });
});
