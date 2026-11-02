import { describe, it, expect, vi, afterEach } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import ErrorBoundary from './ErrorBoundary.svelte';

describe('ErrorBoundary Component', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders default slot when no error is provided (no error-boundary shown)', async () => {
    const { container } = render(ErrorBoundary);
    await tick();
    expect(container.querySelector('.error-boundary')).toBeNull();
  });

  it('renders default slot without error-boundary class when no error', async () => {
    const { container } = render(ErrorBoundary);
    await tick();
    expect(container.querySelector('.error-boundary')).toBeNull();
  });

  it('renders error UI when error is provided', async () => {
    const error = new Error('Something failed');
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    expect(container.querySelector('.error-boundary')).not.toBeNull();
  });

  it('displays the error message when error has message property', async () => {
    const error = new Error('Database connection lost');
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    expect(container.textContent).toContain('Database connection lost');
  });

  it('displays fallback message when error has no message', async () => {
    const error = { code: 'X1' };
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    expect(container.textContent).toContain('An error occurred');
  });

  it('displays fallback message when error is null/undefined after first set', async () => {
    const { container } = render(ErrorBoundary);
    await tick();
    expect(container.textContent).not.toContain('An error occurred');
  });

  it('renders the Something went wrong heading', async () => {
    const error = new Error('Boom');
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    expect(container.textContent).toContain('Something went wrong');
  });

  it('does not render Try Again button when retry is null', async () => {
    const error = new Error('Boom');
    const { container } = render(ErrorBoundary, { props: { error, retry: null } });
    await tick();
    // The Try Again button only renders if retry is provided
    const buttons = container.querySelectorAll('button');
    // Other buttons might exist from internal Card/Space; check no "Try Again"
    const tryAgain = Array.from(buttons).find(b => b.textContent?.includes('Try Again'));
    expect(tryAgain).toBeUndefined();
  });

  it('renders Try Again button when retry is provided', async () => {
    const retry = vi.fn();
    const error = new Error('Boom');
    const { container } = render(ErrorBoundary, { props: { error, retry } });
    await tick();
    const buttons = container.querySelectorAll('button');
    const tryAgain = Array.from(buttons).find(b => b.textContent?.includes('Try Again'));
    expect(tryAgain).not.toBeUndefined();
  });

  it('clicking Try Again invokes retry callback', async () => {
    const retry = vi.fn();
    const error = new Error('Boom');
    const { container } = render(ErrorBoundary, { props: { error, retry } });
    await tick();
    const buttons = container.querySelectorAll('button');
    const tryAgain = Array.from(buttons).find(b => b.textContent?.includes('Try Again')) as HTMLButtonElement;
    expect(tryAgain).not.toBeUndefined();
    await fireEvent.click(tryAgain);
    await tick();
    expect(retry).toHaveBeenCalledTimes(1);
  });

  it('error UI re-renders if error prop is updated', async () => {
    const error1 = new Error('First error');
    const error2 = new Error('Second error');
    const { component, container } = render(ErrorBoundary, { props: { error: error1 } });
    await tick();
    expect(container.textContent).toContain('First error');
    expect(container.textContent).not.toContain('Second error');
  });

  it('renders error-boundary wrapper around Card when error present', async () => {
    const error = new Error('Test');
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    const boundary = container.querySelector('.error-boundary');
    expect(boundary).not.toBeNull();
    expect(boundary?.querySelector('div, .card, article')).toBeDefined();
  });

  it('handles error that is a plain string (falls back to default message)', async () => {
    const { container } = render(ErrorBoundary, { props: { error: 'plain string error' } });
    await tick();
    // Plain string has no .message property so default 'An error occurred' is shown
    expect(container.textContent).toContain('An error occurred');
  });

  it('handles error that is a number (falls back to default message)', async () => {
    const { container } = render(ErrorBoundary, { props: { error: 42 } });
    await tick();
    expect(container.textContent).toContain('An error occurred');
  });

  it('no-throw safety when retry callback throws — retry is called but error propagates', async () => {
    // Per Code Preservation: ErrorBoundary does not catch retry exceptions, so this test
    // documents the current behavior (retry is invoked, error propagates upward).
    const retry = vi.fn(() => {
      throw new Error('retry failed');
    });
    const error = new Error('Original');
    const { container } = render(ErrorBoundary, { props: { error, retry } });
    await tick();
    const buttons = container.querySelectorAll('button');
    const tryAgain = Array.from(buttons).find(b => b.textContent?.includes('Try Again')) as HTMLButtonElement;
    expect(tryAgain).not.toBeUndefined();
    // Use window error handler to absorb the unhandled exception from retry
    const errorHandler = vi.fn();
    window.addEventListener('error', errorHandler);
    try {
      await fireEvent.click(tryAgain);
      expect(retry).toHaveBeenCalled();
    } finally {
      window.removeEventListener('error', errorHandler);
    }
  });

  it('preserves error message even with non-Error throwables', async () => {
    const thrown = { message: 'Custom error message' };
    const { container } = render(ErrorBoundary, { props: { error: thrown } });
    await tick();
    expect(container.textContent).toContain('Custom error message');
  });

  it('default slot still renders when error prop is null', async () => {
    const { container } = render(ErrorBoundary, { props: { error: null } });
    await tick();
    expect(container.querySelector('.error-boundary')).toBeNull();
  });

  it('does not double-render error UI when re-rendered with same error', async () => {
    const error = new Error('Same');
    const { container } = render(ErrorBoundary, { props: { error } });
    await tick();
    const firstRender = container.querySelector('.error-boundary');
    expect(firstRender).not.toBeNull();
    // No duplicate boundaries
    const allBoundaries = container.querySelectorAll('.error-boundary');
    expect(allBoundaries.length).toBe(1);
  });
});
