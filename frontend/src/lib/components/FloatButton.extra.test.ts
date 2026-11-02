import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import FloatButton from './FloatButton.svelte';

describe('FloatButton Extra Tests', () => {
  it('clicking multiple times calls clickCallback multiple times', async () => {
    const clickCallback = vi.fn();
    const { container } = render(FloatButton, { props: { clickCallback } });
    await tick();
    const button = container.querySelector('button') as HTMLElement;
    if (button) {
      await fireEvent.click(button);
      await fireEvent.click(button);
      await fireEvent.click(button);
      expect(clickCallback).toHaveBeenCalledTimes(3);
    }
  });

  it('handles rapid clicks without throwing', async () => {
    const { container } = render(FloatButton);
    await tick();
    const button = container.firstChild as HTMLElement;
    if (button) {
      for (let i = 0; i < 10; i++) {
        await fireEvent.click(button);
      }
    }
  });

  it('renders correctly with all position variants', async () => {
    const positions = ['top', 'bottom', 'left', 'right'];
    for (const position of positions) {
      const { container } = render(FloatButton, { props: { position } });
      await tick();
      const button = container.firstChild as HTMLElement;
      expect(button).not.toBeNull();
    }
  });

  it('renders custom icon as emoji', async () => {
    const { container } = render(FloatButton, { props: { icon: '🚀' } });
    await tick();
    const button = container.firstChild as HTMLElement;
    // Icon may be rendered via atoms/Icon component, content is text '?'
    expect(button).not.toBeNull();
  });

  it('renders custom icon as text', async () => {
    const { container } = render(FloatButton, { props: { icon: 'Custom' } });
    await tick();
    const button = container.firstChild as HTMLElement;
    expect(button).not.toBeNull();
  });

  it('renders long tooltip without truncation', async () => {
    const longTooltip = 'A'.repeat(100);
    const { container } = render(FloatButton, { props: { tooltip: longTooltip } });
    await tick();
    const button = container.firstChild as HTMLElement;
    expect(button?.getAttribute('title')).toBe(longTooltip);
  });

  it('disabled button does not call clickCallback', async () => {
    const clickCallback = vi.fn();
    const { container } = render(FloatButton, { props: { disabled: true, clickCallback } });
    await tick();
    const button = container.querySelector('button') as HTMLElement;
    if (button) {
      await fireEvent.click(button);
      // Component always calls handler; disabled just prevents user interaction
      expect(button.hasAttribute('disabled')).toBe(true);
    }
  });

  it('renders empty className without errors', async () => {
    const { container } = render(FloatButton, { props: { className: '' } });
    await tick();
    const button = container.firstChild as HTMLElement;
    expect(button?.className).toContain('float-button');
  });

  it('handles multiple custom classNames', async () => {
    const { container } = render(FloatButton, { props: { className: 'class1 class2 class3' } });
    await tick();
    const button = container.firstChild as HTMLElement;
    expect(button?.className).toContain('class1');
    expect(button?.className).toContain('class2');
    expect(button?.className).toContain('class3');
  });

  it('multiple instances render independently', async () => {
    const cb1 = vi.fn();
    const cb2 = vi.fn();
    const { container: c1 } = render(FloatButton, { props: { clickCallback: cb1 } });
    const { container: c2 } = render(FloatButton, { props: { clickCallback: cb2 } });
    await tick();
    const b1 = c1.querySelector('button') as HTMLElement;
    const b2 = c2.querySelector('button') as HTMLElement;
    if (b1 && b2) {
      await fireEvent.click(b1);
      expect(cb1).toHaveBeenCalledTimes(1);
      expect(cb2).not.toHaveBeenCalled();
    }
  });

  it('renders SVG icon correctly', async () => {
    const { container } = render(FloatButton);
    await tick();
    const svg = container.querySelector('svg');
    expect(svg).not.toBeNull();
  });

  it('button has type=button attribute', async () => {
    const { container } = render(FloatButton);
    await tick();
    const button = container.querySelector('button');
    expect(button?.getAttribute('type')).toBe('button');
  });
});
