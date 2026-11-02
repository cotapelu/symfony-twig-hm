import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Switch from './Switch.svelte';

describe('Switch', () => {
  it('renders unchecked by default', () => {
    render(Switch);
    const btn = screen.getByRole('switch');
    expect(btn).toHaveAttribute('aria-checked', 'false');
  });

  it('renders checked when checked prop true', () => {
    render(Switch, { props: { checked: true } });
    const btn = screen.getByRole('switch');
    expect(btn).toHaveAttribute('aria-checked', 'true');
  });

  it('shows checkedText when checked', () => {
    render(Switch, { props: { checked: true, checkedText: 'On', uncheckedText: 'Off' } });
    expect(screen.getByText('On')).toBeInTheDocument();
  });

  it('shows uncheckedText when not checked', () => {
    render(Switch, { props: { checked: false, checkedText: 'On', uncheckedText: 'Off' } });
    expect(screen.getByText('Off')).toBeInTheDocument();
  });

  it('is disabled when disabled prop true', () => {
    render(Switch, { props: { disabled: true } });
    const btn = screen.getByRole('switch');
    expect(btn).toHaveAttribute('disabled');
  });

  it('is loading when loading prop true', () => {
    render(Switch, { props: { loading: true } });
    const btn = screen.getByRole('switch');
    expect(btn).toHaveAttribute('disabled');
    expect(screen.getByText('⟳')).toBeInTheDocument();
  });

  it('toggles checked on click', async () => {
    render(Switch, { props: { checked: false } });
    const btn = screen.getByRole('switch');
    await fireEvent.click(btn);
    expect(btn).toHaveAttribute('aria-checked', 'true');
  });

  it('does not toggle when disabled', async () => {
    render(Switch, { props: { disabled: true, checked: false } });
    const btn = screen.getByRole('switch');
    await fireEvent.click(btn);
    expect(btn).toHaveAttribute('aria-checked', 'false');
  });

  it('does not toggle when loading', async () => {
    render(Switch, { props: { loading: true, checked: false } });
    const btn = screen.getByRole('switch');
    await fireEvent.click(btn);
    expect(btn).toHaveAttribute('aria-checked', 'false');
  });

  it('applies size class', () => {
    const sizes = ['small', 'default'] as const;
    sizes.forEach((size) => {
      const { container } = render(Switch, { props: { size } });
      const switchEl = container.querySelector('.switch');
      expect(switchEl).toHaveClass(`switch-${size}`);
    });
  });

  it('applies custom className', () => {
    const { container } = render(Switch, { props: { className: 'my-switch' } });
    const switchEl = container.querySelector('.switch');
    expect(switchEl).toHaveClass('my-switch');
  });
});
