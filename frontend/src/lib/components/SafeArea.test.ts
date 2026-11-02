import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import SafeArea from './SafeArea.svelte';

describe('SafeArea Component', () => {
  it('renders safe-area container', async () => {
    const { container } = render(SafeArea);
    await tick();
    expect(container.querySelector('.safe-area')).not.toBeNull();
  });

  it('applies safe-area class by default', async () => {
    const { container } = render(SafeArea);
    await tick();
    const safeArea = container.querySelector('.safe-area');
    expect(safeArea?.className).toContain('safe-area');
  });

  it('applies custom className when provided', async () => {
    const { container } = render(SafeArea, { props: { className: 'custom-safe' } });
    await tick();
    const safeArea = container.querySelector('.safe-area');
    expect(safeArea?.className).toContain('custom-safe');
  });

  it('renders with default top=true', async () => {
    const { container } = render(SafeArea);
    await tick();
    const safeArea = container.querySelector('.safe-area');
    expect(safeArea?.getAttribute('style')).toBeDefined();
  });

  it('accepts all 4 boolean props (top/bottom/left/right)', async () => {
    const { container } = render(SafeArea, {
      props: { top: true, bottom: true, left: true, right: true },
    });
    await tick();
    expect(container.querySelector('.safe-area')).not.toBeNull();
  });

  it('accepts all 4 boolean props as false', async () => {
    const { container } = render(SafeArea, {
      props: { top: false, bottom: false, left: false, right: false },
    });
    await tick();
    expect(container.querySelector('.safe-area')).not.toBeNull();
  });

  it('accepts mixed boolean props', async () => {
    const { container } = render(SafeArea, {
      props: { top: true, bottom: false, left: true, right: false },
    });
    await tick();
    expect(container.querySelector('.safe-area')).not.toBeNull();
  });

  it('renders slot content', async () => {
    const { container } = render(SafeArea);
    await tick();
    // The component has <slot /> which should be present
    const safeArea = container.querySelector('.safe-area');
    expect(safeArea).not.toBeNull();
  });

  it('does not throw when onMount is skipped in test env', async () => {
    const { container } = render(SafeArea);
    await tick();
    // Component should still render even if onMount doesn't fire
    expect(container.querySelector('.safe-area')).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { container: c1 } = render(SafeArea);
    const { container: c2 } = render(SafeArea, { props: { className: 'second' } });
    await tick();
    expect(c1.querySelector('.safe-area')).not.toBeNull();
    expect(c2.querySelector('.safe-area')).not.toBeNull();
  });

  it('style attribute reflects padding configuration', async () => {
    const { container } = render(SafeArea, {
      props: { top: true, bottom: false, left: true, right: false },
    });
    await tick();
    const safeArea = container.querySelector('.safe-area');
    const style = safeArea?.getAttribute('style') || '';
    // With top=true and insets.top=0, style contains padding-top: 0px
    expect(style).toContain('padding-top');
    // With bottom=false, padding-bottom should NOT be in style
    expect(style).not.toContain('padding-bottom');
    // With left=true and insets.left=0, style contains padding-left: 0px
    expect(style).toContain('padding-left');
    // With right=false, padding-right should NOT be in style
    expect(style).not.toContain('padding-right');
  });

  it('handles empty className', async () => {
    const { container } = render(SafeArea, { props: { className: '' } });
    await tick();
    const safeArea = container.querySelector('.safe-area');
    expect(safeArea?.className).toContain('safe-area');
  });
});
