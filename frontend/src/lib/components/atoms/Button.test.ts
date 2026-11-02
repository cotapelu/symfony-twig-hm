import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import userEvent from '@testing-library/user-event';
import Button from '$lib/components/atoms/Button.svelte';

describe('Button Component', () => {
  it('renders a button element by default', () => {
    const { container } = render(Button, { props: { text: 'Click me' } });
    expect(container.querySelector('button')).toBeTruthy();
    expect(container.textContent).toContain('Click me');
  });

  it('renders an anchor element when href is provided', () => {
    const { container } = render(Button, { props: { href: 'https://example.com', text: 'Link' } });
    const anchor = container.querySelector('a');
    expect(anchor).toBeTruthy();
    expect(anchor?.getAttribute('href')).toBe('https://example.com');
  });

  it('applies variant classes correctly', () => {
    const { container: primary } = render(Button, { props: { variant: 'primary', text: 'Primary' } });
    expect(primary.querySelector('.btn-primary')).toBeTruthy();

    const { container: secondary } = render(Button, { props: { variant: 'secondary', text: 'Secondary' } });
    expect(secondary.querySelector('.btn-secondary')).toBeTruthy();

    const { container: danger } = render(Button, { props: { variant: 'danger', text: 'Danger' } });
    expect(danger.querySelector('.btn-danger')).toBeTruthy();

    const { container: ghost } = render(Button, { props: { variant: 'ghost', text: 'Ghost' } });
    expect(ghost.querySelector('.btn-ghost')).toBeTruthy();
  });

  it('applies size classes correctly', () => {
    const { container: sm } = render(Button, { props: { size: 'sm', text: 'Small' } });
    expect(sm.querySelector('.btn-sm')).toBeTruthy();

    const { container: md } = render(Button, { props: { size: 'md', text: 'Medium' } });
    expect(md.querySelector('.btn-md')).toBeTruthy();

    const { container: lg } = render(Button, { props: { size: 'lg', text: 'Large' } });
    expect(lg.querySelector('.btn-lg')).toBeTruthy();
  });

  it('applies disabled state correctly', () => {
    const { container } = render(Button, { props: { disabled: true, text: 'Disabled' } });
    const button = container.querySelector('button');
    expect(button?.disabled).toBe(true);
    expect(container.querySelector('.btn-disabled')).toBeTruthy();
  });

  it('shows loading state correctly', () => {
    const { container } = render(Button, { props: { loading: true, text: 'Loading' } });
    expect(container.querySelector('.btn-loading')).toBeTruthy();
    expect(container.querySelector('.btn-spinner')).toBeTruthy();
  });

  it('handles click events', async () => {
    const handleClick = vi.fn();
    const { container } = render(Button, { props: { text: 'Click me' } });
    const button = container.querySelector('button');
    button?.addEventListener('click', handleClick);
    await fireEvent.click(button!);
    expect(handleClick).toHaveBeenCalled();
  });

  it('does not handle click when disabled', async () => {
    const handleClick = vi.fn();
    render(Button, { props: { disabled: true, text: 'Click me' } });
    const button = screen.getByRole('button');
    button?.addEventListener('click', handleClick);
    await userEvent.click(button);
    expect(handleClick).not.toHaveBeenCalled();
  });

  it('renders with custom className', () => {
    const { container } = render(Button, { props: { className: 'custom-class', text: 'Custom' } });
    expect(container.querySelector('.custom-class')).toBeTruthy();
  });
});
