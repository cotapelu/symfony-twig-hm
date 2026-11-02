import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import Space from './Space.svelte';

describe('Space Component', () => {
  it('renders a wrapper element', () => {
    const { container } = render(Space);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toBeInTheDocument();
    expect(wrapper.tagName).toBe('DIV');
  });

  it('applies horizontal direction by default', () => {
    const { container } = render(Space);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space-horizontal');
  });

  it('applies vertical direction when specified', () => {
    const { container } = render(Space, { props: { direction: 'vertical' } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space-vertical');
  });

  it('applies align class', () => {
    const { container } = render(Space, { props: { align: 'end' } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space-align-end');
  });

  it('applies justify class', () => {
    const { container } = render(Space, { props: { justify: 'between' } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space-justify-between');
  });

  it('applies wrap class when wrap is true', () => {
    const { container } = render(Space, { props: { wrap: true } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space-wrap');
  });

  it('does not apply wrap class when wrap is false', () => {
    const { container } = render(Space, { props: { wrap: false } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).not.toHaveClass('space-wrap');
  });

  it('sets custom size style when size is a number', () => {
    const { container } = render(Space, { props: { size: 20 } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper.getAttribute('style')).toContain('--space-size: 20px');
  });

  it('does not set style when size is a string', () => {
    const { container } = render(Space, { props: { size: 'lg' } });
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper.getAttribute('style')).toBe('');
  });

  it('applies base space class', () => {
    const { container } = render(Space);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper).toHaveClass('space');
  });
});
