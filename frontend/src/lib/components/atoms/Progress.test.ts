import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Progress from './Progress.svelte';

describe('Progress', () => {
  it('renders with default percent 0', () => {
    render(Progress);
    expect(screen.getByText('0%')).toBeInTheDocument();
  });

  it('displays percent when showInfo true', () => {
    render(Progress, { props: { percent: 45 } });
    expect(screen.getByText('45%')).toBeInTheDocument();
  });

  it('hides percent when showInfo false', () => {
    render(Progress, { props: { percent: 45, showInfo: false } });
    expect(screen.queryByText('45%')).not.toBeInTheDocument();
  });

  it('shows infoText when provided', () => {
    render(Progress, { props: { infoText: 'Loading...' } });
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('does not show infoText when not provided', () => {
    render(Progress);
    expect(screen.queryByText(/Loading/)).not.toBeInTheDocument();
  });

  it('applies status class', () => {
    const statuses = ['active', 'success', 'exception'] as const;
    statuses.forEach((status) => {
      const { container } = render(Progress, { props: { status } });
      const progress = container.querySelector('.progress');
      expect(progress).toHaveClass(status);
    });
  });

  it('applies size class', () => {
    const sizes = ['sm', 'md', 'lg'] as const;
    sizes.forEach((size) => {
      const { container } = render(Progress, { props: { size } });
      const progress = container.querySelector('.progress');
      expect(progress).toHaveClass(size);
    });
  });

  it('has correct ARIA attributes', () => {
    render(Progress, { props: { percent: 75 } });
    const el = document.querySelector('.progress') as HTMLElement;
    expect(el).toHaveAttribute('aria-valuenow', '75');
    expect(el).toHaveAttribute('aria-valuemin', '0');
    expect(el).toHaveAttribute('aria-valuemax', '100');
  });

  it('applies custom className', () => {
    const { container } = render(Progress, { props: { className: 'my-progress' } });
    const progress = container.querySelector('.progress');
    expect(progress).toHaveClass('my-progress');
  });

  it('renders without errors for 100 percent', () => {
    render(Progress, { props: { percent: 100 } });
    expect(screen.getByText('100%')).toBeInTheDocument();
  });
});
