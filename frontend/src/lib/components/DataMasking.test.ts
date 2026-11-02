import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import userEvent from '@testing-library/user-event';
import DataMasking from './DataMasking.svelte';

describe('DataMasking', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Mock navigator.clipboard using vi.stubGlobal
    vi.stubGlobal('navigator', {
      clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined)
      }
    });
  });

  it('renders with default props', () => {
    render(DataMasking);
    expect(screen.getByPlaceholderText('user@example.com')).toBeInTheDocument();
  });

  it('renders with email mask type (default)', () => {
    render(DataMasking, { props: { value: 'user@example.com' } });
    const input = screen.getByPlaceholderText('user@example.com');
    // Component masks all but first 2 chars of username: 'user' (4 chars) -> 'us**'
    expect(input).toHaveValue('us**@example.com');
  });

  it('renders with phone mask type', () => {
    render(DataMasking, { props: { value: '123-456-7890', maskType: 'phone' } });
    const input = screen.getByPlaceholderText('123-456-7890');
    expect(input).toHaveValue('***-***-7890');
  });

  it('renders with creditCard mask type', () => {
    render(DataMasking, { props: { value: '1234-5678-1234-5678', maskType: 'creditCard' } });
    const input = screen.getByPlaceholderText('1234-5678-1234-5678');
    expect(input).toHaveValue('****-****-****-5678');
  });

  it('renders with ssn mask type', () => {
    render(DataMasking, { props: { value: '123-45-6789', maskType: 'ssn' } });
    const input = screen.getByPlaceholderText('123-45-6789');
    expect(input).toHaveValue('***-**-6789');
  });

  it('renders with custom mask type and pattern', () => {
    render(DataMasking, { props: { value: '1234-5678', maskType: 'custom', customMaskPattern: '\\d{4}' } });
    const input = screen.getByPlaceholderText('Enter value');
    expect(input).toHaveValue('****-5678');
  });

  it('shows unmasked value when visible is true', () => {
    render(DataMasking, { props: { value: 'user@example.com', showByDefault: true } });
    const input = screen.getByPlaceholderText('user@example.com');
    expect(input).toHaveValue('user@example.com');
    expect(input).not.toHaveAttribute('type', 'password');
  });

  it('toggles visibility when eye button clicked', async () => {
    const user = userEvent.setup();
    render(DataMasking, { props: { value: 'user@example.com' } });
    const toggleButton = screen.getByLabelText('Show value');
    await user.click(toggleButton);
    const input = screen.getByPlaceholderText('user@example.com');
    expect(input).toHaveValue('user@example.com');
    expect(input).not.toHaveAttribute('type', 'password');
  });

  it('shows copy button when copyable is true', () => {
    render(DataMasking, { props: { value: 'user@example.com', copyable: true } });
    expect(screen.getByLabelText('Copy')).toBeInTheDocument();
  });

  it('hides copy button when copyable is false', () => {
    render(DataMasking, { props: { value: 'user@example.com', copyable: false } });
    expect(screen.queryByLabelText('Copy')).not.toBeInTheDocument();
  });

  it('renders custom mask pattern input when maskType is custom', () => {
    render(DataMasking, { props: { value: 'test', maskType: 'custom', customMaskPattern: '\\d{4}' } });
    // Component renders the pattern with escaped backslashes
    expect(screen.getByPlaceholderText('e.g., \\\\d4-\\\\d4')).toBeInTheDocument();
  });

  it('handles short email gracefully (2+ char username)', () => {
    render(DataMasking, { props: { value: 'ab@b.com', maskType: 'email' } });
    const input = screen.getByPlaceholderText('user@example.com');
    // 'ab' (2 chars) -> first 2 chars = 'ab', rest masked (0 chars) = 'ab'
    expect(input).toHaveValue('ab@b.com');
  });

  it('handles short phone gracefully', () => {
    render(DataMasking, { props: { value: '123', maskType: 'phone' } });
    const input = screen.getByPlaceholderText('123-456-7890');
    expect(input).toHaveValue('123');
  });

  it('handles short credit card gracefully', () => {
    render(DataMasking, { props: { value: '1234', maskType: 'creditCard' } });
    const input = screen.getByPlaceholderText('1234-5678-1234-5678');
    expect(input).toHaveValue('1234');
  });

  it('handles short ssn gracefully', () => {
    render(DataMasking, { props: { value: '123', maskType: 'ssn' } });
    const input = screen.getByPlaceholderText('123-45-6789');
    expect(input).toHaveValue('123');
  });

  it('renders with custom placeholder based on mask type', () => {
    render(DataMasking, { props: { maskType: 'email' } });
    expect(screen.getByPlaceholderText('user@example.com')).toBeInTheDocument();
  });

  it('renders with phone placeholder', () => {
    render(DataMasking, { props: { maskType: 'phone' } });
    expect(screen.getByPlaceholderText('123-456-7890')).toBeInTheDocument();
  });

  it('renders with creditCard placeholder', () => {
    render(DataMasking, { props: { maskType: 'creditCard' } });
    expect(screen.getByPlaceholderText('1234-5678-1234-5678')).toBeInTheDocument();
  });

  it('renders with ssn placeholder', () => {
    render(DataMasking, { props: { maskType: 'ssn' } });
    expect(screen.getByPlaceholderText('123-45-6789')).toBeInTheDocument();
  });

  it('renders multiple instances independently', () => {
    const { container: c1 } = render(DataMasking, { props: { value: 'user1@example.com' } });
    const { container: c2 } = render(DataMasking, { props: { value: 'user2@example.com' } });
    // 'user1' (5 chars) -> 'us***', 'user2' (5 chars) -> 'us***'
    expect(c1.querySelector('input')).toHaveValue('us***@example.com');
    expect(c2.querySelector('input')).toHaveValue('us***@example.com');
  });

  it('shows toggle button with correct label based on visibility', () => {
    render(DataMasking, { props: { value: 'test', showByDefault: false } });
    expect(screen.getByLabelText('Show value')).toBeInTheDocument();
  });

  it('shows toggle button with hide label when visible', () => {
    render(DataMasking, { props: { value: 'test', showByDefault: true } });
    expect(screen.getByLabelText('Hide value')).toBeInTheDocument();
  });

  it('handles empty value', () => {
    render(DataMasking, { props: { value: '' } });
    const input = screen.getByPlaceholderText('user@example.com');
    expect(input).toHaveValue('');
  });

  it('readOnly attribute is set when not visible', () => {
    render(DataMasking, { props: { value: 'test@example.com', showByDefault: false } });
    const input = screen.getByPlaceholderText('user@example.com');
    expect(input).toHaveAttribute('readonly');
  });

  it('shows copy button with correct aria-label', () => {
    render(DataMasking, { props: { value: 'test', copyable: true } });
    const copyButton = screen.getByLabelText('Copy');
    expect(copyButton).toHaveAttribute('aria-label', 'Copy');
  });
});