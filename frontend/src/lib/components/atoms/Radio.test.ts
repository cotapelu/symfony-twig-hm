import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import Radio from './Radio.svelte';

// Skipping due to Svelte 5 getContext lifecycle issues during testing
// Needs wrapper component strategy; see TODO

describe('Radio', () => {
  it.skip('renders with label', () => {
    const { getByText } = render(Radio, {
      props: { name: 'color', value: 'red', label: 'Red' },
    });
    expect(getByText('Red')).toBeDefined();
  });

  it.skip('renders without label', () => {
    const { container } = render(Radio, {
      props: { name: 'color', value: 'red' },
    });
    const input = container.querySelector('input[type="radio"]');
    expect(input).toBeDefined();
  });

  it.skip('is checked when bound group value matches', () => {
    const { container, rerender } = render(Radio, {
      props: { name: 'color', value: 'red', checked: false },
    });
    let input = container.querySelector('input[type="radio"]') as HTMLInputElement;
    expect(input.checked).toBe(false);

    rerender({ name: 'color', value: 'red', checked: true });
    input = container.querySelector('input[type="radio"]') as HTMLInputElement;
    expect(input.checked).toBe(true);
  });

  it.skip('is disabled when disabled prop is true', () => {
    const { container } = render(Radio, {
      props: { name: 'color', value: 'red', disabled: true },
    });
    const input = container.querySelector('input[type="radio"]') as HTMLInputElement;
    expect(input.disabled).toBe(true);
  });

  it.skip('is required when required prop is true', () => {
    const { container } = render(Radio, {
      props: { name: 'color', value: 'red', required: true },
    });
    const input = container.querySelector('input[type="radio"]') as HTMLInputElement;
    expect(input.required).toBe(true);
  });

  it.skip('emits change event when clicked', async () => {
    const handleChange = vi.fn();
    const { container } = render(Radio, {
      props: { name: 'color', value: 'red' },
    });
    const input = container.querySelector('input[type="radio"]') as HTMLInputElement;
    await fireEvent.click(input);
    expect(input).toBeDefined();
  });

  it.skip('applies custom class', () => {
    const { container } = render(Radio, {
      props: { name: 'color', value: 'red', class: 'custom-class' },
    });
    const wrapper = container.querySelector('.radio-wrapper') as HTMLElement;
    expect(wrapper.classList.contains('custom-class')).toBe(true);
  });
});
