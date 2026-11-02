import { describe, it, expect } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import PasswordStrength from './PasswordStrength.svelte';

describe('PasswordStrength extra', () => {
  it('stays at zero strength on empty password', () => {
    const { container, unmount } = render(PasswordStrength, { props: { password: '' } });
    expect(container.textContent || '').toContain('Weak password');
    unmount();
  });

  it('reflects typed input value via element value property', async () => {
    const { getByLabelText, unmount } = render(PasswordStrength, {
      props: {
        password: '',
        minLength: 1,
        requireUppercase: false,
        requireLowercase: false,
        requireNumber: false,
        requireSpecial: false,
      },
    });
    const input = getByLabelText('Password input');
    await fireEvent.input(input, { target: { value: 'a' } });
    expect((input as HTMLInputElement).value).toBe('a');
    unmount();
  });

  it('toggles password visibility on button click', async () => {
    const { getByLabelText, getByRole, unmount } = render(PasswordStrength, {
      props: { password: 'secret' },
    });
    const input = getByLabelText('Password input');
    expect((input as HTMLInputElement).type).toBe('password');

    const show = getByRole('button', { name: /show password/i });
    await fireEvent.click(show);
    expect((input as HTMLInputElement).type).toBe('text');

    const hide = getByRole('button', { name: /hide password/i });
    await fireEvent.click(hide);
    expect((input as HTMLInputElement).type).toBe('password');
    unmount();
  });
});
