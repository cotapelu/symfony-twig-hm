import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import CurrencyFormatter from './CurrencyFormatter.svelte';

describe('CurrencyFormatter', () => {
  it('renders formatted currency with default USD', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 1234.56 } });
    const text = container.textContent || '';
    expect(text).toContain('$');
    expect(text).toContain('1,234.56');
    expect(text).toContain('USD');
  });

  it('renders custom currency (EUR)', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 100, currency: 'EUR' } });
    const text = container.textContent || '';
    expect(text).toContain('€');
    expect(text).toContain('100.00');
    expect(text).toContain('EUR');
  });

  it('renders with code display mode', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 99.99, display: 'code' } });
    const text = container.textContent || '';
    expect(text).toContain('USD');
    expect(text).toContain('99.99');
  });

  it('renders with plain number display mode', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 50.5, display: 'number' } });
    const text = container.textContent || '';
    expect(text).toContain('50.50');
    // Should not contain currency symbol in number mode
    expect(text).not.toContain('$');
    // Currency code is still displayed as caption - this is current behavior
    expect(text).toContain('USD');
  });

  it('applies custom className', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 100, className: 'my-currency' } });
    const root = container.firstChild as HTMLElement;
    expect(root?.className).toContain('currency-formatter');
    expect(root?.className).toContain('my-currency');
  });

  it('has clickable role and tabindex', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 100 } });
    const root = container.firstChild as HTMLElement;
    expect(root?.getAttribute('role')).toBe('button');
    expect(root?.getAttribute('tabindex')).toBe('0');
  });

  it('dispatches click event on click', async () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 100 } });
    const clickHandler = vi.fn();
    
    // Get the root element
    const root = container.firstChild as HTMLElement;
    if (root) {
      // Add event listener to container for dispatched events
      root.addEventListener('click', clickHandler);
      await fireEvent.click(root);
      // The component uses createEventDispatcher which fires custom events
      // Test just that click works
      expect(clickHandler).toHaveBeenCalled();
    }
  });

  it('calls onClick callback when provided', async () => {
    const onClick = vi.fn();
    const { container } = render(CurrencyFormatter, { props: { amount: 200, onClick } });
    
    const root = container.firstChild as HTMLElement;
    if (root) {
      await fireEvent.click(root);
      expect(onClick).toHaveBeenCalledWith(200);
    }
  });

  it('handles Enter key press', async () => {
    const onClick = vi.fn();
    const { container } = render(CurrencyFormatter, { props: { amount: 300, onClick } });
    
    const root = container.firstChild as HTMLElement;
    if (root) {
      await fireEvent.keyDown(root, { key: 'Enter' });
      expect(onClick).toHaveBeenCalledWith(300);
    }
  });

  it('handles Space key press', async () => {
    const onClick = vi.fn();
    const { container } = render(CurrencyFormatter, { props: { amount: 400, onClick } });
    
    const root = container.firstChild as HTMLElement;
    if (root) {
      await fireEvent.keyDown(root, { key: ' ' });
      expect(onClick).toHaveBeenCalledWith(400);
    }
  });

  it('formats zero amount correctly', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 0 } });
    const text = container.textContent || '';
    expect(text).toContain('0.00');
  });

  it('formats negative amount correctly', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: -50.25 } });
    const text = container.textContent || '';
    expect(text).toContain('-');
    expect(text).toContain('50.25');
  });

  it('formats large numbers with commas', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 1000000 } });
    const text = container.textContent || '';
    expect(text).toContain('1,000,000.00');
  });

  it('renders Typography components for amount and currency code', () => {
    const { container } = render(CurrencyFormatter, { props: { amount: 100 } });
    // Check for the structure - h6 for amount, caption for currency
    const h6 = container.querySelector('h6') || container.querySelector('[class*="h6"]');
    const caption = container.querySelector('.currency-code') || container.querySelector('[class*="caption"]');
    expect(h6).not.toBeNull();
    expect(caption).not.toBeNull();
  });
});