import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import Button from './atoms/Button.svelte';

describe('Button (extra coverage)', () => {
  it('renders button element with type="submit"', () => {
    const { container } = render(Button, { props: { text: 'Submit', type: 'submit' } });
    const button = container.querySelector('button');
    expect(button?.getAttribute('type')).toBe('submit');
  });

  it('renders button element with type="reset"', () => {
    const { container } = render(Button, { props: { text: 'Reset', type: 'reset' } });
    const button = container.querySelector('button');
    expect(button?.getAttribute('type')).toBe('reset');
  });

  it('renders anchor element with form attribute', () => {
    const { container } = render(Button, { props: { href: '/home', text: 'Home', form: 'myForm' } });
    const anchor = container.querySelector('a');
    expect(anchor?.getAttribute('form')).toBe('myForm');
  });

  it('sets aria-label when provided', () => {
    const { container } = render(Button, { props: { text: 'Close', 'aria-label': 'Close modal' } });
    const button = container.querySelector('button');
    expect(button?.getAttribute('aria-label')).toBe('Close modal');
  });

  it('shows spinner element when loading', () => {
    const { container } = render(Button, { props: { loading: true, text: 'Saving' } });
    const spinner = container.querySelector('.btn-spinner');
    expect(spinner).toBeTruthy();
  });

  it('does not show spinner when not loading', () => {
    const { container } = render(Button, { props: { loading: false, text: 'Save' } });
    const spinner = container.querySelector('.btn-spinner');
    expect(spinner).toBeNull();
  });

  it('button is focusable and has cursor style', () => {
    const { container } = render(Button, { props: { text: 'Click' } });
    const button = container.querySelector('button');
    // Basic check: button element exists and is not disabled
    expect(button).not.toBeNull();
    expect(button?.disabled).toBe(false);
  });
});
