import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, cleanup } from '@testing-library/svelte';
import SearchResultItem from './SearchResultItem.svelte';

/**
 * Pure presentational component. Covers render branches:
 * image vs icon fallback, type→icon/color mapping, optional fields
 * (author/date/category/relevanceScore), url fallback, highlighted state.
 */
function makeResult(overrides = {}) {
  return {
    id: 'r1',
    type: 'user',
    title: 'Alice Nguyen',
    description: 'A resident of Ward 4',
    ...overrides,
  };
}

afterEach(() => cleanup());

describe('SearchResultItem', () => {
  describe('core rendering', () => {
    it('renders the title, type badge, and description', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.getByText('Alice Nguyen')).toBeInTheDocument();
      expect(screen.getByText('user')).toBeInTheDocument();
      expect(screen.getByText('A resident of Ward 4')).toBeInTheDocument();
    });

    it('renders an anchor with role link', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.getByRole('link')).toBeInTheDocument();
    });

    it('uses result.url for the link href', () => {
      render(SearchResultItem, {
        props: { result: makeResult({ url: '/users/alice' }) },
      });
      expect(screen.getByRole('link')).toHaveAttribute('href', '/users/alice');
    });

    it("falls back to '#' when url is missing", () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.getByRole('link')).toHaveAttribute('href', '#');
    });
  });

  describe('image vs icon fallback', () => {
    it('renders an img when result.image is set', () => {
      render(SearchResultItem, {
        props: { result: makeResult({ image: 'https://x/y.png' }) },
      });
      const img = screen.getByAltText('Alice Nguyen') as HTMLImageElement;
      expect(img).toBeInTheDocument();
      expect(img).toHaveAttribute('src', 'https://x/y.png');
    });

    it('renders fallback emoji icon (no avatar) for a user result', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.getByText('👤')).toBeInTheDocument();
    });

    it('maps each type to its icon emoji', () => {
      const map = {
        user: '👤', message: '💬', file: '📄', product: '📦',
        post: '📝', group: '👥', event: '📅', place: '📍',
      };
      for (const [type, icon] of Object.entries(map)) {
        const { unmount } = render(SearchResultItem, {
          props: { result: makeResult({ type }) },
        });
        expect(screen.getByText(icon)).toBeInTheDocument();
        unmount();
      }
    });

    it("maps an unknown/other type to the default '🔍' icon", () => {
      render(SearchResultItem, { props: { result: makeResult({ type: 'other' }) } });
      expect(screen.getByText('🔍')).toBeInTheDocument();
    });
  });

  describe('type badge color', () => {
    it('applies blue styling for a user type', () => {
      render(SearchResultItem, { props: { result: makeResult({ type: 'user' }) } });
      expect(screen.getByText('user')).toHaveClass('text-blue-800');
    });

    it('applies gray styling for the default (other) type', () => {
      render(SearchResultItem, { props: { result: makeResult({ type: 'other' }) } });
      expect(screen.getByText('other')).toHaveClass('text-gray-800');
    });
  });

  describe('optional metadata', () => {
    it('shows relevance match percentage when relevanceScore is present', () => {
      render(SearchResultItem, {
        props: { result: makeResult({ relevanceScore: 0.873 }) },
      });
      expect(screen.getByText('87% match')).toBeInTheDocument();
    });

    it('hides relevance match when relevanceScore is absent', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.queryByText(/% match/)).not.toBeInTheDocument();
    });

    it('hides relevance match when relevanceScore is 0 (falsy guard)', () => {
      render(SearchResultItem, {
        props: { result: makeResult({ relevanceScore: 0 }) },
      });
      expect(screen.queryByText(/% match/)).not.toBeInTheDocument();
    });

    it('shows author and category when provided', () => {
      render(SearchResultItem, {
        props: { result: makeResult({ author: 'System', category: 'news' }) },
      });
      expect(screen.getByText('System')).toBeInTheDocument();
      expect(screen.getByText('#news')).toBeInTheDocument();
    });

    it('omits author/category when not provided', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.queryByText('System')).not.toBeInTheDocument();
      expect(screen.queryByText(/#news/)).not.toBeInTheDocument();
    });

    it('renders a date element when date is provided, omits when absent', () => {
      const { unmount } = render(SearchResultItem, {
        props: { result: makeResult({ date: '2026-08-11T00:00:00.000Z' }) },
      });
      // jsdom has no <time>; the date is rendered as text inside a span.
      expect(screen.getByText(/2026/)).toBeInTheDocument();
      unmount();
      cleanup();
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.queryByText(/2026/)).not.toBeInTheDocument();
    });
  });

  describe('highlighted state', () => {
    it('applies highlight classes when highlighted=true', () => {
      render(SearchResultItem, {
        props: { result: makeResult(), highlighted: true },
      });
      const link = screen.getByRole('link');
      expect(link).toHaveClass('bg-blue-50');
      expect(link).toHaveClass('border-blue-200');
    });

    it('does not apply highlight classes by default', () => {
      render(SearchResultItem, { props: { result: makeResult() } });
      expect(screen.getByRole('link')).not.toHaveClass('bg-blue-50');
    });

    it('reactively updates highlight classes via rerender', () => {
      const { rerender } = render(SearchResultItem, {
        props: { result: makeResult(), highlighted: false },
      });
      expect(screen.getByRole('link')).not.toHaveClass('bg-blue-50');
      rerender({ result: makeResult(), highlighted: true });
      expect(screen.getByRole('link')).toHaveClass('bg-blue-50');
    });
  });
});
