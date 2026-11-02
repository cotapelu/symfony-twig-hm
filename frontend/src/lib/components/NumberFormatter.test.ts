import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import NumberFormatter from './NumberFormatter.svelte';

describe('NumberFormatter Component', () => {
  describe('formatting', () => {
    it('formats number with no decimals and commas by default', () => {
      render(NumberFormatter, { props: { number: 1234567 } });
      expect(screen.getByText('1,234,567')).toBeTruthy();
    });

    it('formats integer without commas when showCommas false', () => {
      render(NumberFormatter, { props: { number: 1234567, showCommas: false } });
      expect(screen.getByText('1234567')).toBeTruthy();
    });

    it('formats number with specified decimals', () => {
      render(NumberFormatter, { props: { number: 1234.5678, decimals: 2 } });
      expect(screen.getByText('1,234.57')).toBeTruthy();
    });

    it('rounds decimals appropriately', () => {
      render(NumberFormatter, { props: { number: 999.999, decimals: 2 } });
      expect(screen.getByText('1,000.00')).toBeTruthy();
    });

    it('handles zero', () => {
      render(NumberFormatter, { props: { number: 0 } });
      expect(screen.getByText('0')).toBeTruthy();
    });

    it('handles negative numbers', () => {
      render(NumberFormatter, { props: { number: -1234 } });
      expect(screen.getByText('-1,234')).toBeTruthy();
    });

    it('formats large numbers without commas when showCommas false', () => {
      render(NumberFormatter, { props: { number: 1234567890, showCommas: false } });
      expect(screen.getByText('1234567890')).toBeTruthy();
    });
  });

  describe('color based on magnitude', () => {
    it('applies danger color for numbers >= 1,000,000', () => {
      render(NumberFormatter, { props: { number: 1000000 } });
      const span = document.querySelector('.number-formatter > span');
      expect(span?.style.color).toBe('var(--color-danger, #ef4444)');
    });

    it('applies warning color for numbers >= 1,000 and < 1,000,000', () => {
      render(NumberFormatter, { props: { number: 50000 } });
      const span = document.querySelector('.number-formatter > span');
      expect(span?.style.color).toBe('var(--color-warning, #f59e0b)');
    });

    it('applies success color for numbers >= 0', () => {
      render(NumberFormatter, { props: { number: 500 } });
      const span = document.querySelector('.number-formatter > span');
      expect(span?.style.color).toBe('var(--color-success, #10b981)');
    });

    it('applies success color for zero', () => {
      render(NumberFormatter, { props: { number: 0 } });
      const span = document.querySelector('.number-formatter > span');
      expect(span?.style.color).toBe('var(--color-success, #10b981)');
    });

    it('applies success color for negative numbers (abs >= 0)', () => {
      render(NumberFormatter, { props: { number: -500 } });
      const span = document.querySelector('.number-formatter > span');
      expect(span?.style.color).toBe('var(--color-success, #10b981)');
    });
  });

  describe('class name', () => {
    it('applies custom className', () => {
      render(NumberFormatter, { props: { number: 123, className: 'my-number' } });
      const container = document.querySelector('.number-formatter');
      expect(container).toHaveClass('my-number');
    });
  });

  describe('accessibility', () => {
    it('has role="text" and appropriate aria-label', () => {
      render(NumberFormatter, { props: { number: 1234 } });
      const container = document.querySelector('.number-formatter');
      expect(container).toHaveAttribute('role', 'text');
      expect(container).toHaveAttribute('aria-label', 'Formatted number');
    });
  });
});
