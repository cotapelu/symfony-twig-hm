import { describe, it, expect, vi } from 'vitest';
import { render } from '@testing-library/svelte';
import FloatButton from './FloatButton.svelte';

describe('FloatButton', () => {
  it('renders wrapper with default position (top)', () => {
    const { container } = render(FloatButton);
    const wrapper = container.querySelector('.float-button-wrapper');
    expect(wrapper).not.toBeNull();
    expect(wrapper?.className).toContain('float-button-wrapper');
    expect(wrapper?.className).toContain('top');
  });

  it('renders button with icon', () => {
    const { container } = render(FloatButton);
    const button = container.querySelector('.float-button');
    expect(button).not.toBeNull();
    const icon = container.querySelector('svg');
    expect(icon).not.toBeNull();
  });

  it('renders custom icon when icon prop provided', () => {
    const { container } = render(FloatButton, { props: { icon: 'edit' } });
    const icon = container.querySelector('svg');
    expect(icon).not.toBeNull();
    // Icon component renders with the name prop
  });

  it('applies custom position class', () => {
    const positions = ['top', 'bottom', 'left', 'right'] as const;
    positions.forEach((position) => {
      const { container, unmount } = render(FloatButton, { props: { position } });
      const wrapper = container.querySelector('.float-button-wrapper');
      expect(wrapper?.className).toContain(position);
      unmount();
    });
  });

  it('applies custom className to wrapper', () => {
    const { container } = render(FloatButton, { props: { className: 'my-float-btn' } });
    const wrapper = container.querySelector('.float-button-wrapper');
    expect(wrapper?.className).toContain('my-float-btn');
  });

  it('disables button when disabled prop is true', () => {
    const { container } = render(FloatButton, { props: { disabled: true } });
    const button = container.querySelector('button');
    expect(button?.disabled).toBe(true);
  });

  it('does not disable button when disabled is false', () => {
    const { container } = render(FloatButton, { props: { disabled: false } });
    const button = container.querySelector('button');
    expect(button?.disabled).toBe(false);
  });

  it('calls clickCallback on button click', async () => {
    const clickCallback = vi.fn();
    const { container } = render(FloatButton, { props: { clickCallback } });
    const button = container.querySelector('button');
    await button?.click();
    expect(clickCallback).toHaveBeenCalledTimes(1);
  });

  it('does not call clickCallback when disabled', async () => {
    const clickCallback = vi.fn();
    const { container } = render(FloatButton, { props: { clickCallback, disabled: true } });
    const button = container.querySelector('button');
    await button?.click();
    expect(clickCallback).not.toHaveBeenCalled();
  });

  it('applies tooltip as title attribute', () => {
    const { container } = render(FloatButton, { props: { tooltip: 'Add new item' } });
    const wrapper = container.querySelector('.float-button-wrapper');
    expect(wrapper?.getAttribute('title')).toBe('Add new item');
  });

  it('renders with plus icon by default', () => {
    const { container } = render(FloatButton);
    // Icon component should render with 'plus' name by default
    const icon = container.querySelector('.float-button svg');
    expect(icon).not.toBeNull();
  });
});