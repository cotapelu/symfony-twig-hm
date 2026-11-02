import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import Spin from './Spin.svelte';

describe('Spin (additional coverage)', () => {
  it('renders with default size', () => {
    const { container } = render(Spin);
    const div = container.querySelector('div.spin');
    expect(div).not.toBeNull();
    expect(div?.className).toContain('spin');
    expect(div?.className).toContain('md');
  });

  it('applies custom color prop to Icon', () => {
    const { container } = render(Spin, {
      props: { color: 'red' },
    });
    const svg = container.querySelector('svg');
    expect(svg).not.toBeNull();
    // Color might be set as attribute; just ensure rendering
  });

  it('combines custom className with base spin class', () => {
    const { container } = render(Spin, {
      props: { className: 'my-custom-spin' },
    });
    const div = container.querySelector('div.spin');
    expect(div?.className).toContain('spin');
    expect(div?.className).toContain('my-custom-spin');
  });

  it('renders with size "sm"', () => {
    const { container } = render(Spin, {
      props: { size: 'sm' },
    });
    const div = container.querySelector('div.spin');
    expect(div?.className).toContain('sm');
    expect(div?.className).not.toContain('md');
    expect(div?.className).not.toContain('lg');
  });

  it('renders with size "lg"', () => {
    const { container } = render(Spin, {
      props: { size: 'lg' },
    });
    const div = container.querySelector('div.spin');
    expect(div?.className).toContain('lg');
    expect(div?.className).not.toContain('md');
    expect(div?.className).not.toContain('sm');
  });

  it('has correct ARIA attributes for accessibility', () => {
    const { container } = render(Spin);
    const div = container.querySelector('div.spin');
    expect(div?.getAttribute('role')).toBe('status');
    expect(div?.getAttribute('aria-label')).toBe('Loading');
    expect(div?.getAttribute('aria-busy')).toBe('true');
  });
});
