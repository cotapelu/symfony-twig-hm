import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Breadcrumb from './Breadcrumb.svelte';

describe('Breadcrumb Component', () => {
  it('renders all items when under maxItems', () => {
    render(Breadcrumb, { props: { items: [
      { label: 'Home', href: '/' },
      { label: 'Library' },
      { label: 'Data' }
    ] } });
    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('Library')).toBeInTheDocument();
    expect(screen.getByText('Data')).toBeInTheDocument();
  });

  it('collapses excess items when exceeding maxItems', () => {
    render(Breadcrumb, { props: { items: [
      { label: 'Home', href: '/' },
      { label: 'Level 1', href: '/l1' },
      { label: 'Level 2', href: '/l2' },
      { label: 'Level 3', href: '/l3' },
      { label: 'Level 4', href: '/l4' }
    ], maxItems: 4 } });
    // Should show: Home, +2, Level 3, Level 4 (total 4 visible)
    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('+2')).toBeInTheDocument();
    expect(screen.getByText('Level 3')).toBeInTheDocument();
    expect(screen.getByText('Level 4')).toBeInTheDocument();
    expect(screen.queryByText('Level 1')).not.toBeInTheDocument();
    expect(screen.queryByText('Level 2')).not.toBeInTheDocument();
  });

  it('uses custom separator', () => {
    render(Breadcrumb, { props: { items: [{ label: 'A' }, { label: 'B' }], separator: '>' } });
    expect(screen.getByText('>')).toBeInTheDocument();
  });

  it('marks last item as current when it has href', () => {
    render(Breadcrumb, { props: { items: [
      { label: 'A', href: '/a' },
      { label: 'B', href: '/b' }
    ] } });
    const lastLink = screen.getByText('B').closest('a');
    expect(lastLink).toHaveAttribute('aria-current', 'page');
  });

  // Event dispatch test can be added later if needed.
});
