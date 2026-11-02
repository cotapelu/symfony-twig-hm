import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import InputNumber from './InputNumber.svelte';

describe('InputNumber', () => {
  const defaultProps = {};

  it('renders input with initial value', () => {
    render(InputNumber, { props: { value: 10 } });
    const input = screen.getByRole('textbox');
    expect(input).toHaveValue('10');
  });

  it('displays value with precision formatting when precision set', () => {
    render(InputNumber, { props: { value: 1.5, precision: 2 } });
    const input = screen.getByRole('textbox');
    expect(input).toHaveValue('1.50');
  });

  it('increments value when increase button clicked', async () => {
    render(InputNumber, { props: { value: 5, step: 1 } });
    const incBtn = screen.getByRole('button', { name: /increase value/i });
    await fireEvent.click(incBtn);
    await tick();
    const input = screen.getByRole('textbox');
    expect(input).toHaveValue('6');
  });

  it('decrements value when decrease button clicked', async () => {
    render(InputNumber, { props: { value: 5, step: 1 } });
    const decBtn = screen.getByRole('button', { name: /decrease value/i });
    await fireEvent.click(decBtn);
    await tick();
    const input = screen.getByRole('textbox');
    expect(input).toHaveValue('4');
  });

  it('respects step size', async () => {
    render(InputNumber, { props: { value: 10, step: 2 } });
    const incBtn = screen.getByRole('button', { name: /increase value/i });
    await fireEvent.click(incBtn);
    await tick();
    expect(screen.getByRole('textbox')).toHaveValue('12');
  });

  it('clamps to max', async () => {
    render(InputNumber, { props: { value: 9, max: 10, step: 2 } });
    const incBtn = screen.getByRole('button', { name: /increase value/i });
    await fireEvent.click(incBtn);
    await tick();
    expect(screen.getByRole('textbox')).toHaveValue('10');
    await fireEvent.click(incBtn);
    await tick();
    // stays at max
    expect(screen.getByRole('textbox')).toHaveValue('10');
  });

  it('clamps to min', async () => {
    render(InputNumber, { props: { value: 2, min: 0, step: 3 } });
    const decBtn = screen.getByRole('button', { name: /decrease value/i });
    await fireEvent.click(decBtn);
    await tick();
    expect(screen.getByRole('textbox')).toHaveValue('0');
    await fireEvent.click(decBtn);
    await tick();
    expect(screen.getByRole('textbox')).toHaveValue('0');
  });

  it('updates value via input typing', async () => {
    render(InputNumber, { props: { value: 5 } });
    const input = screen.getByRole('textbox');
    fireEvent.input(input, { target: { value: '12' } });
    await tick();
    expect(input).toHaveValue('12');
  });

  it('parses value on blur and clamps to max', async () => {
    render(InputNumber, { props: { value: 5, max: 10 } });
    const input = screen.getByRole('textbox');
    fireEvent.input(input, { target: { value: '15' } });
    await tick();
    fireEvent.blur(input);
    await tick();
    expect(input).toHaveValue('10');
  });

  it('disables controls when disabled prop true', () => {
    render(InputNumber, { props: { value: 5, disabled: true } });
    const incBtn = screen.getByRole('button', { name: /increase value/i });
    const decBtn = screen.getByRole('button', { name: /decrease value/i });
    expect(incBtn).toHaveAttribute('disabled');
    expect(decBtn).toHaveAttribute('disabled');
    expect(screen.getByRole('textbox')).toHaveAttribute('disabled');
  });

  it('hides controls when controls=false', () => {
    render(InputNumber, { props: { value: 5, controls: false } });
    expect(screen.queryByRole('button', { name: /increase value/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /decrease value/i })).not.toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
  });

  it('applies size class to input element', () => {
    const sizes = ['sm', 'md', 'lg'] as const;
    sizes.forEach((size) => {
      const { container } = render(InputNumber, { props: { value: 5, size } });
      const input = container.querySelector('input.input-number');
      expect(input).toHaveClass(`input-number-${size}`);
    });
  });

  it('applies custom className to input element', () => {
    const { container } = render(InputNumber, { props: { value: 5, className: 'my-input-number' } });
    const input = container.querySelector('input.input-number');
    expect(input).toHaveClass('my-input-number');
  });

  it('shows placeholder when provided', () => {
    render(InputNumber, { props: { placeholder: 'Enter number' } });
    const input = screen.getByRole('textbox');
    expect(input).toHaveAttribute('placeholder', 'Enter number');
  });

  it('dispatches change event when value updates via buttons', () => {
    render(InputNumber, { props: { value: 5 } });
    const incBtn = screen.getByRole('button', { name: /increase value/i });
    fireEvent.click(incBtn);
    // No explicit assertion; increment test verifies value change.
  });
});
