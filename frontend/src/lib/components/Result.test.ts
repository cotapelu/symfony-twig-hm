import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import Result from './Result.svelte';

describe('Result Component', () => {
  const defaultProps = {
    status: 'info' as const,
    title: 'Result Title',
    description: 'Result description text',
    showAction: false,
    buttonText: '',
    actionCallback: undefined as (() => void) | undefined,
    className: '',
    icon: ''
  };

  describe('rendering', () => {
    it('renders with title and description', () => {
      render(Result, { props: defaultProps });
      expect(screen.getByText('Result Title')).toBeTruthy();
      expect(screen.getByText('Result description text')).toBeTruthy();
    });

    it('does not show button when showAction false', () => {
      render(Result, { props: defaultProps });
      expect(screen.queryByRole('button')).toBeNull();
    });

    it('shows button when showAction true and buttonText provided', () => {
      render(Result, { props: { ...defaultProps, showAction: true, buttonText: 'Proceed' } });
      expect(screen.getByRole('button', { name: 'Proceed' })).toBeTruthy();
    });

    it('does not show button when buttonText empty', () => {
      render(Result, { props: { ...defaultProps, showAction: true, buttonText: '' } });
      expect(screen.queryByRole('button')).toBeNull();
    });
  });

  describe('status variants', () => {
    const statuses = ['info', 'success', 'warning', 'error'] as const;
    statuses.forEach((status) => {
      it(`applies result-${status} class`, () => {
        render(Result, { props: { ...defaultProps, status } });
        const result = document.querySelector('.result');
        expect(result).toHaveClass(`result-${status}`);
      });
    });
  });

  describe('icon rendering', () => {
    it('renders an icon element', () => {
      render(Result, { props: defaultProps });
      const icon = document.querySelector('.result .icon');
      expect(icon).toBeTruthy();
    });

    it('icon exists for all statuses', () => {
      const statuses = ['info', 'success', 'warning', 'error'] as const;
      statuses.forEach((status) => {
        const { unmount } = render(Result, { props: { ...defaultProps, status } });
        const icon = document.querySelector('.result .icon');
        expect(icon).toBeTruthy();
        unmount();
      });
    });

    it('uses custom icon when provided', () => {
      render(Result, { props: { ...defaultProps, icon: 'home' } });
      const icon = document.querySelector('.result .icon');
      expect(icon).toBeTruthy();
      // The Icon component should render an SVG path; we can check that there is a path element.
      const path = icon?.querySelector('path');
      expect(path).toBeTruthy();
    });
  });

  describe('action callback', () => {
    it('calls actionCallback when button clicked', async () => {
      const callback = vi.fn();
      render(Result, { props: { ...defaultProps, showAction: true, buttonText: 'Action', actionCallback: callback } });
      const btn = screen.getByRole('button', { name: 'Action' });
      btn.click();
      await tick();
      expect(callback).toHaveBeenCalledTimes(1);
    });
  });

  describe('className', () => {
    it('applies custom className to result container', () => {
      render(Result, { props: { ...defaultProps, className: 'my-result' } });
      const result = document.querySelector('.result');
      expect(result).toHaveClass('my-result');
    });
  });

  describe('accessibility', () => {
    it('has role="alert"', () => {
      render(Result, { props: defaultProps });
      const result = document.querySelector('.result');
      expect(result).toHaveAttribute('role', 'alert');
    });
  });
});
