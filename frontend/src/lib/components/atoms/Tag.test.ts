import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Tag from './Tag.svelte';

describe('Tag', () => {
  it('renders when visible is true', () => {
    render(Tag, { props: { visible: true } });
    const tag = document.querySelector('.tag');
    expect(tag).toBeTruthy();
  });

  it('does not render when visible false', () => {
    render(Tag, { props: { visible: false } });
    const tag = document.querySelector('.tag');
    expect(tag).toBeNull();
  });

  it('shows close button when closable', () => {
    render(Tag, { props: { closable: true, visible: true } });
    expect(screen.getByText('×')).toBeInTheDocument();
  });

  it('hides close button when not closable', () => {
    render(Tag, { props: { closable: false, visible: true } });
    expect(screen.queryByText('×')).not.toBeInTheDocument();
  });

  it('hides tag when close button is clicked', async () => {
    render(Tag, { props: { closable: true, visible: true } });
    const closeBtn = screen.getByText('×');
    await fireEvent.click(closeBtn);
    expect(document.querySelector('.tag')).toBeNull();
  });

  it('toggles checked class on click when checkable', async () => {
    render(Tag, { props: { checkable: true, checked: false, visible: true } });
    const tag = document.querySelector('.tag') as HTMLElement;
    expect(tag).toHaveClass('tag-unchecked');
    await fireEvent.click(tag);
    expect(tag).not.toHaveClass('tag-unchecked');
  });

  it('applies size class', () => {
    const sizes = ['default', 'small'] as const;
    sizes.forEach((size) => {
      const { container } = render(Tag, { props: { size, visible: true } });
      const tag = container.querySelector('.tag');
      expect(tag).toHaveClass(`tag-${size}`);
    });
  });

  it('applies color class when color prop set', () => {
    const { container } = render(Tag, { props: { color: 'primary', visible: true } });
    const tag = container.querySelector('.tag');
    expect(tag).toHaveClass('tag-primary');
  });

  it('does not close when clicking content (not close button)', async () => {
    render(Tag, { props: { closable: true, visible: true } });
    const tag = document.querySelector('.tag') as HTMLElement;
    expect(screen.getByText('×')).toBeInTheDocument();
    await fireEvent.click(tag!);
    expect(document.querySelector('.tag')).toBeTruthy();
  });
});
