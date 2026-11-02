import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Icon from './Icon.svelte';

describe('Icon', () => {
  it('renders with default props', () => {
    render(Icon, { props: { name: 'home' } });
    const svg = document.querySelector('.icon');
    expect(svg).toBeInTheDocument();
    expect(svg).toHaveClass('icon-md');
  });

  it('renders unknown icon as question mark', () => {
    render(Icon, { props: { name: 'unknown-icon-name' } });
    const span = document.querySelector('.icon');
    expect(span).toBeInTheDocument();
    expect(span).toHaveTextContent('?');
  });

  it('applies size variants', () => {
    const sizes = ['xs', 'sm', 'md', 'lg', 'xl'] as const;
    sizes.forEach(size => {
      const { unmount } = render(Icon, { props: { name: 'home', size } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass(`icon-${size}`);
      unmount();
    });
  });

  it('applies custom color (rgb format)', () => {
    render(Icon, { props: { name: 'home', color: '#ff0000' } });
    const icon = document.querySelector('.icon');
    expect(icon).toHaveAttribute('style', expect.stringContaining('rgb(255, 0, 0)'));
  });

  it('applies spin class', () => {
    render(Icon, { props: { name: 'home', spin: true } });
    const icon = document.querySelector('.icon');
    expect(icon).toHaveClass('icon-spin');
  });

  it('applies rotate transform', () => {
    render(Icon, { props: { name: 'home', rotate: 90 } });
    const icon = document.querySelector('.icon');
    expect(icon).toHaveAttribute('style', expect.stringContaining('rotate(90deg)'));
  });

  it('applies flip variants', () => {
    const flips = ['horizontal', 'vertical', 'both'] as const;
    flips.forEach(flip => {
      const { unmount } = render(Icon, { props: { name: 'home', flip } });
      const icon = document.querySelector('.icon');
      expect(icon).toHaveClass(`icon-flip-${flip}`);
      unmount();
    });
  });

  it('combines multiple props', () => {
    render(Icon, { props: { name: 'home', size: 'lg', color: '#00ff00', spin: true, rotate: 45 } });
    const icon = document.querySelector('.icon');
    expect(icon).toHaveClass('icon-lg');
    expect(icon).toHaveClass('icon-spin');
    expect(icon).toHaveAttribute('style', expect.stringContaining('rgb(0, 255, 0)'));
    expect(icon).toHaveAttribute('style', expect.stringContaining('rotate(45deg)'));
  });



  it('renders SVG with correct attributes', () => {
    render(Icon, { props: { name: 'home' } });
    const svg = document.querySelector('.icon');
    expect(svg).toHaveAttribute('viewBox', '0 0 24 24');
    expect(svg).toHaveAttribute('stroke', 'currentColor');
    expect(svg).toHaveAttribute('stroke-width', '2');
    expect(svg).toHaveAttribute('stroke-linecap', 'round');
    expect(svg).toHaveAttribute('stroke-linejoin', 'round');
  });

  it('renders path for known icon', () => {
    render(Icon, { props: { name: 'home' } });
    const path = document.querySelector('.icon path');
    expect(path).toBeInTheDocument();
    expect(path).toHaveAttribute('d');
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(Icon, { props: { name: 'home', size: 'sm' } });
    const { container: container2 } = render(Icon, { props: { name: 'search', size: 'lg', color: 'red' } });
    const icon1 = container1.querySelector('.icon');
    const icon2 = container2.querySelector('.icon');
    expect(icon1).toHaveClass('icon-sm');
    expect(icon2).toHaveClass('icon-lg');
    expect(icon2).toHaveAttribute('style', expect.stringContaining('color: red'));
  });

  it('handles empty string name', () => {
    render(Icon, { props: { name: '' } });
    const span = document.querySelector('.icon');
    expect(span).toHaveTextContent('?');
  });

  it('does not add spin class when false', () => {
    render(Icon, { props: { name: 'home', spin: false } });
    const icon = document.querySelector('.icon');
    expect(icon).not.toHaveClass('icon-spin');
  });

  it('does not add flip class when null', () => {
    render(Icon, { props: { name: 'home', flip: null } });
    const icon = document.querySelector('.icon');
    expect(icon).not.toHaveClass('icon-flip-horizontal');
    expect(icon).not.toHaveClass('icon-flip-vertical');
    expect(icon).not.toHaveClass('icon-flip-both');
  });
});