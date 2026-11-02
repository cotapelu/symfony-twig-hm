import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import Anchor from './Anchor.svelte';

describe('Anchor Component (Extra)', () => {
  it('renders with target _parent', () => {
    const { container } = render(Anchor, { props: { href: '#parent', target: '_parent' } });
    const anchor = container.querySelector('a');
    expect(anchor).toHaveAttribute('target', '_parent');
  });

  it('renders with target _top', () => {
    const { container } = render(Anchor, { props: { href: '#top', target: '_top' } });
    const anchor = container.querySelector('a');
    expect(anchor).toHaveAttribute('target', '_top');
  });

  it('does not override title when provided', () => {
    const { container } = render(Anchor, { props: { href: 'https://example.com', title: 'Custom title' } });
    const anchor = container.querySelector('a');
    expect(anchor?.getAttribute('title')).toBe('Custom title');
  });

  it('dispatches click event', async () => {
    const handleClick = vi.fn();
    const { container } = render(Anchor, { props: { href: '#' } });
    const anchor = container.querySelector('a');
    anchor?.addEventListener('click', handleClick);
    await fireEvent.click(anchor);
    expect(handleClick).toHaveBeenCalled();
  });
});
