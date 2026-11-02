import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import TimePicker from './TimePicker.svelte';

describe('TimePicker Component', () => {
  it('renders input with placeholder', () => {
    render(TimePicker, { props: { placeholder: 'Choose time' } });
    expect(screen.getByPlaceholderText('Choose time')).toBeInTheDocument();
  });

  it('shows default placeholder when none provided', () => {
    render(TimePicker);
    expect(screen.getByPlaceholderText('Select time')).toBeInTheDocument();
  });

  it('displays label when provided', () => {
    render(TimePicker, { props: { label: 'Time' } });
    expect(screen.getByText('Time')).toBeInTheDocument();
  });

  it('reflects disabled state', () => {
    render(TimePicker, { props: { disabled: true } });
    const input = screen.getByRole('textbox');
    expect(input).toBeDisabled();
  });

  it('shows error message when error prop provided', () => {
    render(TimePicker, { props: { error: 'Invalid time' } });
    expect(screen.getByText('Invalid time')).toBeInTheDocument();
  });
});
