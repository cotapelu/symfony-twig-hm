import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen, waitFor } from '@testing-library/svelte';
import Select from './Select.svelte';

describe('Select', () => {
  const options = [
    { value: 'a', label: 'Option A' },
    { value: 'b', label: 'Option B' },
    { value: 'c', label: 'Option C' }
  ];

  const getMainButton = (container: HTMLElement) => container.querySelector('button.select') as HTMLButtonElement;

  it('renders placeholder when no value selected', () => {
    const { container } = render(Select, { props: { options } });
    expect(screen.getByText('Select...')).toBeInTheDocument();
    expect(getMainButton(container)).toHaveTextContent('Select...');
  });

  it('displays selected option label', () => {
    const { container } = render(Select, { props: { options, value: 'b' } });
    expect(getMainButton(container)).toHaveTextContent('Option B');
  });

  it('opens dropdown on click', async () => {
    const { container } = render(Select, { props: { options } });
    const btn = getMainButton(container);
    await fireEvent.click(btn);
    expect(screen.getByRole('listbox')).toBeInTheDocument();
    expect(screen.getByText('Option A')).toBeInTheDocument();
    expect(screen.getByText('Option B')).toBeInTheDocument();
    expect(screen.getByText('Option C')).toBeInTheDocument();
  });

  it('closes dropdown after selecting an option', async () => {
    const { container } = render(Select, { props: { options } });
    const btn = getMainButton(container);
    await fireEvent.click(btn);
    const option = screen.getByText('Option B');
    await fireEvent.click(option);
    expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
    expect(getMainButton(container)).toHaveTextContent('Option B');
  });

  it('supports multiple selection', async () => {
    const { container } = render(Select, { props: { options, multiple: true, value: [] } });
    const btn = getMainButton(container);
    await fireEvent.click(btn);
    // Select first option
    await fireEvent.click(screen.getByText('Option A'));
    await waitFor(() => {
      expect(getMainButton(container)).toHaveTextContent('1 selected');
    });
    // Dropdown remains open in multiple mode; select second option directly
    await fireEvent.click(screen.getByText('Option B'));
    await waitFor(() => {
      expect(getMainButton(container)).toHaveTextContent('2 selected');
    });
  });

  it('is disabled when disabled prop true', () => {
    const { container } = render(Select, { props: { options, disabled: true } });
    expect(getMainButton(container)).toHaveAttribute('disabled');
  });

  it('does not open dropdown when disabled', async () => {
    const { container } = render(Select, { props: { options, disabled: true } });
    const btn = getMainButton(container);
    await fireEvent.click(btn);
    expect(screen.queryByRole('listbox')).not.toBeInTheDocument();
  });

  it('shows loading spinner and loading class when loading', () => {
    const { container } = render(Select, { props: { options, loading: true } });
    expect(screen.getByText('⟳')).toBeInTheDocument();
    expect(getMainButton(container)).toHaveClass('select-loading');
  });

  it('displays error message when error prop set', () => {
    render(Select, { props: { options, error: 'Invalid selection' } });
    const errorEl = screen.getByText('Invalid selection');
    expect(errorEl).toBeInTheDocument();
    expect(errorEl).toHaveAttribute('role', 'alert');
  });

  it('applies size class', () => {
    const sizes = ['sm', 'md', 'lg'] as const;
    sizes.forEach((size) => {
      const { container } = render(Select, { props: { options, size } });
      const selectWrapper = container.querySelector('.select');
      expect(selectWrapper).toHaveClass(`select-${size}`);
    });
  });

  it('applies variant class', () => {
    const variants = ['default', 'filled', 'outlined'] as const;
    variants.forEach((variant) => {
      const { container } = render(Select, { props: { options, variant } });
      const selectWrapper = container.querySelector('.select');
      expect(selectWrapper).toHaveClass(`select-${variant}`);
    });
  });

  it('filters options in searchable mode', async () => {
    const { container } = render(Select, { props: { options, searchable: true } });
    const btn = getMainButton(container);
    await fireEvent.click(btn);
    const searchInput = screen.getByPlaceholderText('Search...');
    await fireEvent.input(searchInput, { target: { value: 'A' } });
    expect(screen.getByText('Option A')).toBeInTheDocument();
    expect(screen.queryByText('Option B')).not.toBeInTheDocument();
    expect(screen.queryByText('Option C')).not.toBeInTheDocument();
  });

  it('shows required indicator when label and required provided', () => {
    const { container } = render(Select, { props: { options, label: 'Country', required: true } });
    expect(screen.getByText('Country')).toBeInTheDocument();
    expect(screen.getByText('*')).toBeInTheDocument();
    expect(screen.getByText('*')).toHaveAttribute('aria-label', 'required');
  });

  it('shows description when provided', () => {
    render(Select, { props: { options, description: 'Pick an option from the list' } });
    expect(screen.getByText('Pick an option from the list')).toBeInTheDocument();
  });
});
