import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import Input from '$lib/components/atoms/Input.svelte';

describe('Input Component', () => {
  it('renders an input element', () => {
    const { container } = render(Input);
    const input = container.querySelector('input');
    expect(input).toBeTruthy();
  });

  it('renders with placeholder', () => {
    const { container } = render(Input, { props: { placeholder: 'Enter text' } });
    const input = container.querySelector('input');
    expect(input?.getAttribute('placeholder')).toBe('Enter text');
  });

  it('renders with label', () => {
    const { container } = render(Input, { props: { label: 'Username' } });
    const label = container.querySelector('.input-label');
    expect(label?.textContent).toContain('Username');
  });

  it('shows required indicator', () => {
    const { container } = render(Input, { props: { label: 'Email', required: true } });
    const required = container.querySelector('.required');
    expect(required).toBeTruthy();
  });

  it('displays error message', () => {
    const { container } = render(Input, { props: { error: 'This field is required' } });
    const errorMsg = container.querySelector('.input-error-message');
    expect(errorMsg?.textContent).toBe('This field is required');
  });

  it('applies error class when error is present', () => {
    const { container } = render(Input, { props: { error: 'Error' } });
    const input = container.querySelector('.input-error');
    expect(input).toBeTruthy();
  });

  it('applies disabled state', () => {
    const { container } = render(Input, { props: { disabled: true } });
    const input = container.querySelector('.input-disabled');
    expect(input).toBeTruthy();
  });

  it('applies readonly state', () => {
    const { container } = render(Input, { props: { readonly: true } });
    const input = container.querySelector('.input-readonly');
    expect(input).toBeTruthy();
  });

  it('applies size classes correctly', () => {
    const { container: sm } = render(Input, { props: { size: 'sm' } });
    expect(sm.querySelector('.input-sm')).toBeTruthy();

    const { container: md } = render(Input, { props: { size: 'md' } });
    expect(md.querySelector('.input-md')).toBeTruthy();

    const { container: lg } = render(Input, { props: { size: 'lg' } });
    expect(lg.querySelector('.input-lg')).toBeTruthy();
  });

  it('applies variant classes correctly', () => {
    const { container: filled } = render(Input, { props: { variant: 'filled' } });
    expect(filled.querySelector('.input-filled')).toBeTruthy();

    const { container: outlined } = render(Input, { props: { variant: 'outlined' } });
    expect(outlined.querySelector('.input-outlined')).toBeTruthy();
  });

  it('handles input changes', async () => {
    let value = '';
    const { container } = render(Input, { props: { value } });
    
    const input = container.querySelector('input');
    await fireEvent.input(input!, { target: { value: 'new value' } });
    
    expect(input?.value).toBe('new value');
  });

  it('sets aria-invalid when error exists', () => {
    const { container } = render(Input, { props: { error: 'Error' } });
    const input = container.querySelector('input');
    expect(input?.getAttribute('aria-invalid')).toBe('true');
  });
});
