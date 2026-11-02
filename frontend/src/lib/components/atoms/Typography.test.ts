import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Typography from './Typography.svelte';

describe('Typography', () => {
  const renderTypography = (props = {}) => render(Typography, { props });

  it('renders a <p> tag by default (variant body)', () => {
    const { container } = renderTypography();
    expect(container.querySelector('p')).toBeTruthy();
  });

  it('renders <h1> for display variant', () => {
    const { container } = renderTypography({ variant: 'display' });
    expect(container.querySelector('h1')).toBeTruthy();
  });

  it('renders <h1> for h1 variant', () => {
    const { container } = renderTypography({ variant: 'h1' });
    expect(container.querySelector('h1')).toBeTruthy();
  });

  it('renders <h2> for h2 variant', () => {
    const { container } = renderTypography({ variant: 'h2' });
    expect(container.querySelector('h2')).toBeTruthy();
  });

  it('renders <h3> for h3 variant', () => {
    const { container } = renderTypography({ variant: 'h3' });
    expect(container.querySelector('h3')).toBeTruthy();
  });

  it('renders <h4> for h4 variant', () => {
    const { container } = renderTypography({ variant: 'h4' });
    expect(container.querySelector('h4')).toBeTruthy();
  });

  it('renders <h5> for h5 variant', () => {
    const { container } = renderTypography({ variant: 'h5' });
    expect(container.querySelector('h5')).toBeTruthy();
  });

  it('renders <h6> for h6 variant', () => {
    const { container } = renderTypography({ variant: 'h6' });
    expect(container.querySelector('h6')).toBeTruthy();
  });

  it('renders <p> for body variant', () => {
    const { container } = renderTypography({ variant: 'body' });
    expect(container.querySelector('p')).toBeTruthy();
  });

  it('renders <p> for caption variant', () => {
    const { container } = renderTypography({ variant: 'caption' });
    expect(container.querySelector('p')).toBeTruthy();
  });

  it('renders <p> for overline variant', () => {
    const { container } = renderTypography({ variant: 'overline' });
    expect(container.querySelector('p')).toBeTruthy();
  });

  it('applies base typography class', () => {
    const { container } = renderTypography();
    const el = container.querySelector('.typography');
    expect(el).toBeTruthy();
  });

  it('applies variant class', () => {
    const variants: Array<'display' | 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'body' | 'caption' | 'overline'> = ['display', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'caption', 'overline'];
    variants.forEach((variant) => {
      const { container } = renderTypography({ variant });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-${variant}`);
    });
  });

  it('applies size class when size prop provided', () => {
    const sizes = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl'] as const;
    sizes.forEach((size) => {
      const { container } = renderTypography({ size });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-size-${size}`);
    });
  });

  it('does not apply size class when size is null', () => {
    const { container } = renderTypography({ size: null });
    const el = container.querySelector('.typography');
    expect(el).not.toHaveClass(/\typography-size-/);
  });

  it('applies weight class when weight prop provided', () => {
    const weights = [100, 200, 300, 400, 500, 600, 700, 800, 900];
    weights.forEach((weight) => {
      const { container } = renderTypography({ weight });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-weight-${weight}`);
    });
  });

  it('does not apply weight class when weight is null', () => {
    const { container } = renderTypography({ weight: null });
    const el = container.querySelector('.typography');
    expect(el).not.toHaveClass(/\typography-weight-/);
  });

  it('applies align class', () => {
    const aligns = ['left', 'center', 'right', 'justify'] as const;
    aligns.forEach((align) => {
      const { container } = renderTypography({ align });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-align-${align}`);
    });
  });

  it('applies color class', () => {
    const colors = ['inherit', 'primary', 'secondary', 'error', 'warning', 'success', 'info'] as const;
    colors.forEach((color) => {
      const { container } = renderTypography({ color });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-color-${color}`);
    });
  });

  it('applies truncate class when truncate true', () => {
    const { container } = renderTypography({ truncate: true });
    const el = container.querySelector('.typography');
    expect(el).toHaveClass('typography-truncate');
  });

  it('does not apply truncate class when truncate false', () => {
    const { container } = renderTypography({ truncate: false });
    const el = container.querySelector('.typography');
    expect(el).not.toHaveClass('typography-truncate');
  });

  it('applies clamp class when lines prop is set', () => {
    const { container } = renderTypography({ lines: 2 });
    const el = container.querySelector('.typography');
    expect(el).toHaveClass('typography-clamp');
  });

  it('sets CSS custom property for lines', () => {
    const { container } = renderTypography({ lines: 3 });
    const el = container.querySelector('.typography');
    expect(el?.style.getPropertyValue('--lines')).toBe('3');
  });

  it('does not set style when lines is null', () => {
    const { container } = renderTypography({ lines: null });
    const el = container.querySelector('.typography');
    const style = el?.getAttribute('style') || '';
    expect(style).not.toMatch(/--lines/);
  });

  it('applies transform class when transform prop set', () => {
    const transforms = ['uppercase', 'lowercase', 'capitalize'] as const;
    transforms.forEach((transform) => {
      const { container } = renderTypography({ transform });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-transform-${transform}`);
    });
  });

  it('applies decoration class when decoration prop set', () => {
    const decorations = ['underline', 'line-through', 'overline'] as const;
    decorations.forEach((decoration) => {
      const { container } = renderTypography({ decoration });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-decoration-${decoration}`);
    });
  });

  it('applies spacing class when spacing prop set', () => {
    const spacings = ['tight', 'normal', 'loose'] as const;
    spacings.forEach((spacing) => {
      const { container } = renderTypography({ spacing });
      const el = container.querySelector('.typography');
      expect(el).toHaveClass(`typography-spacing-${spacing}`);
    });
  });

  it('applies custom className', () => {
    const { container } = renderTypography({ className: 'my-text' });
    const el = container.querySelector('.typography');
    expect(el).toHaveClass('my-text');
  });

  it('combines multiple classes correctly', () => {
    const { container } = renderTypography({
      variant: 'h2',
      size: 'lg',
      weight: 600,
      align: 'center',
      color: 'primary',
      truncate: true,
      lines: 2,
      transform: 'uppercase',
      decoration: 'underline',
      spacing: 'tight',
      className: 'custom-typography'
    });
    const el = container.querySelector('.typography');
    expect(el).toHaveClass('typography');
    expect(el).toHaveClass('typography-h2');
    expect(el).toHaveClass('typography-size-lg');
    expect(el).toHaveClass('typography-weight-600');
    expect(el).toHaveClass('typography-align-center');
    expect(el).toHaveClass('typography-color-primary');
    expect(el).toHaveClass('typography-truncate');
    expect(el).toHaveClass('typography-clamp');
    expect(el).toHaveClass('typography-transform-uppercase');
    expect(el).toHaveClass('typography-decoration-underline');
    expect(el).toHaveClass('typography-spacing-tight');
    expect(el).toHaveClass('custom-typography');
    expect(el?.style.getPropertyValue('--lines')).toBe('2');
  });
});
