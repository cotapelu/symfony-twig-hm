import { describe, it, expect, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import SearchFilters from './SearchFilters.svelte';

/**
 * Presentational filter panel. Props: `filters` + optional `onChange` callback.
 * (searchStore is imported by the component but unused here — logical render is
 * driven purely by props.) Covers selection styling, onChange payloads, the
 * active-filters summary, the Clear-all action, and the optional-callback guard.
 */
afterEach(() => cleanup());

describe('SearchFilters', () => {
  describe('rendering', () => {
    it('renders the three section headings', () => {
      render(SearchFilters, { props: { filters: {} } });
      expect(screen.getByText('Categories')).toBeInTheDocument();
      expect(screen.getByText('Date Range')).toBeInTheDocument();
      expect(screen.getByText('Sort By')).toBeInTheDocument();
    });

    it('renders all 8 category buttons (icon+label)', () => {
      render(SearchFilters, { props: { filters: {} } });
      for (const label of ['All', 'People', 'Messages', 'Files', 'Products', 'Posts', 'Groups', 'Events']) {
        expect(screen.getByRole('button', { name: new RegExp(label) })).toBeInTheDocument();
      }
    });

    it('renders all date-range and sort options', () => {
      render(SearchFilters, { props: { filters: {} } });
      for (const label of ['Any time', 'Today', 'This week', 'This month', 'This year']) {
        expect(screen.getByRole('button', { name: label })).toBeInTheDocument();
      }
      for (const label of ['Relevance', 'Date', 'Popularity']) {
        expect(screen.getByRole('button', { name: label })).toBeInTheDocument();
      }
    });
  });

  describe('selected-state styling', () => {
    it("highlights 'All' category by default (no category filter)", () => {
      render(SearchFilters, { props: { filters: {} } });
      expect(screen.getByRole('button', { name: /All/ })).toHaveClass('bg-blue-600');
    });

    it('highlights the selected category', () => {
      render(SearchFilters, { props: { filters: { category: 'files' } } });
      expect(screen.getByRole('button', { name: /Files/ })).toHaveClass('bg-blue-600');
    });

    it('highlights the selected date range', () => {
      render(SearchFilters, { props: { filters: { dateRange: 'week' } } });
      expect(screen.getByRole('button', { name: 'This week' })).toHaveClass('bg-blue-100', 'text-blue-800');
    });

    it('highlights relevance sort by default and the selected sort when provided', () => {
      const { unmount } = render(SearchFilters, { props: { filters: {} } });
      expect(screen.getByRole('button', { name: 'Relevance' })).toHaveClass('bg-blue-600');
      unmount();
      render(SearchFilters, { props: { filters: { sortBy: 'date' } } });
      expect(screen.getByRole('button', { name: 'Date' })).toHaveClass('bg-blue-600');
    });
  });

  describe('onChange interactions', () => {
    it('calls onChange with the chosen category', () => {
      const onChange = vi.fn();
      render(SearchFilters, { props: { filters: {}, onChange } });
      fireEvent.click(screen.getByRole('button', { name: /Messages/ }));
      expect(onChange).toHaveBeenCalledTimes(1);
      expect(onChange.mock.calls[0][0]).toEqual({ category: 'messages' });
    });

    it("maps the 'All' category to undefined (clears that filter)", () => {
      const onChange = vi.fn();
      render(SearchFilters, { props: { filters: { category: 'files' }, onChange } });
      fireEvent.click(screen.getByRole('button', { name: /All/ }));
      const arg = onChange.mock.calls[0][0];
      expect('category' in arg).toBe(true);
      expect(arg.category).toBeUndefined();
    });

    it('preserves existing filters when changing sort', () => {
      const onChange = vi.fn();
      render(SearchFilters, { props: { filters: { category: 'files' }, onChange } });
      fireEvent.click(screen.getByRole('button', { name: 'Date' }));
      expect(onChange.mock.calls[0][0]).toEqual({ category: 'files', sortBy: 'date' });
    });

    it('calls onChange with the chosen date range', () => {
      const onChange = vi.fn();
      render(SearchFilters, { props: { filters: {}, onChange } });
      fireEvent.click(screen.getByRole('button', { name: 'This week' }));
      expect(onChange.mock.calls[0][0]).toEqual({ dateRange: 'week' });
    });
  });

  describe('active-filters summary + clear', () => {
    it('hides the active-filters block and Clear-all when no filters', () => {
      render(SearchFilters, { props: { filters: {} } });
      expect(screen.queryByText(/filter\(s\) active/)).not.toBeInTheDocument();
      expect(screen.queryByRole('button', { name: /Clear all/i })).not.toBeInTheDocument();
    });

    it('shows the count of active (truthy) filters', () => {
      render(SearchFilters, { props: { filters: { category: 'files', dateRange: 'week' } } });
      expect(screen.getByText(/2 filter\(s\) active/)).toBeInTheDocument();
    });

    it('Clear all calls onChange with an empty filter object', () => {
      const onChange = vi.fn();
      render(SearchFilters, { props: { filters: { category: 'files' }, onChange } });
      fireEvent.click(screen.getByRole('button', { name: /Clear all/i }));
      expect(onChange).toHaveBeenCalledWith({});
    });
  });

  describe('optional-callback guard', () => {
    it('does not throw when onChange is null', () => {
      render(SearchFilters, { props: { filters: {}, onChange: null } });
      expect(() => fireEvent.click(screen.getByRole('button', { name: /Messages/ }))).not.toThrow();
    });
  });
});
