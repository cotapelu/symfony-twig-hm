import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Checkbox from './Checkbox.svelte';

describe('Checkbox', () => {
  it('renders with default unchecked', () => {
    render(Checkbox, { props: { label: 'Accept' } });
    const checkbox = screen.getByRole('checkbox');
    expect(checkbox).not.toBeChecked();
  });

  it('renders checked when checked prop true', () => {
    render(Checkbox, { props: { label: 'Accept', checked: true } });
    const checkbox = screen.getByRole('checkbox');
    expect(checkbox).toBeChecked();
  });

  it('shows indeterminate state when indeterminate prop true', () => {
    const { container } = render(Checkbox, { props: { label: 'Accept', indeterminate: true } });
    const checkboxLabel = container.querySelector('.checkbox');
    expect(checkboxLabel).toHaveClass('checkbox-indeterminate');
  });

  it('is disabled when disabled prop true', () => {
    render(Checkbox, { props: { label: 'Accept', disabled: true } });
    const checkbox = screen.getByRole('checkbox');
    expect(checkbox).toBeDisabled();
  });

  it('is required when required prop true', () => {
    render(Checkbox, { props: { label: 'Accept', required: true } });
    const checkbox = screen.getByRole('checkbox');
    expect(checkbox).toHaveAttribute('required');
  });

  it('applies size class correctly', () => {
    const sizes = ['sm', 'md', 'lg'] as const;
    sizes.forEach((size) => {
      const { container } = render(Checkbox, { props: { label: 'Accept', size } });
      const checkboxEl = container.querySelector('.checkbox');
      expect(checkboxEl).toHaveClass(`checkbox-${size}`);
    });
  });

  it('displays label, description, and error', () => {
    render(Checkbox, {
      props: { label: 'Accept', description: 'You agree to terms', error: 'Required' }
    });
    expect(screen.getByText('Accept')).toBeInTheDocument();
    expect(screen.getByText('You agree to terms')).toBeInTheDocument();
    expect(screen.getByText('Required')).toBeInTheDocument();
  });

  it('dispatches change event when clicked', async () => {
    const handleChange = vi.fn();
    render(Checkbox, { props: { label: 'Accept' } });
    const checkbox = screen.getByRole('checkbox');
    checkbox.addEventListener('change', handleChange);
    await fireEvent.click(checkbox);
    expect(handleChange).toHaveBeenCalled();
  });

  it('clicking label toggles checkbox', async () => {
    render(Checkbox, { props: { label: 'Accept' } });
    const checkbox = screen.getByRole('checkbox');
    const label = screen.getByText('Accept');
    expect(checkbox).not.toBeChecked();
    await fireEvent.click(label);
    expect(checkbox).toBeChecked();
  });

  it('applies error class and aria-invalid when error prop set', () => {
    const { container } = render(Checkbox, { props: { label: 'Accept', error: 'Invalid' } });
    const wrapper = container.querySelector('.checkbox-wrapper-error');
    expect(wrapper).toBeTruthy();
    const input = screen.getByRole('checkbox');
    expect(input).toHaveAttribute('aria-invalid', 'true');
  });
});
