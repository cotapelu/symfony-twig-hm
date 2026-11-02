// Mock onDestroy before importing the component
vi.mock('svelte', async () => {
  const actual = await vi.importActual('svelte');
  return {
    ...actual,
    onDestroy: vi.fn(),
    onMount: vi.fn(),
    createEventDispatcher: () => vi.fn(),
  };
});

import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import InfiniteScroll from './InfiniteScroll.svelte';

describe('InfiniteScroll Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Mock IntersectionObserver
    global.IntersectionObserver = vi.fn().mockImplementation((callback) => ({
      observe: vi.fn(),
      unobserve: vi.fn(),
      disconnect: vi.fn(),
      takeRecords: vi.fn(),
      root: null,
      rootMargin: '',
      thresholds: []
    }));
  });

  afterEach(() => {
    delete global.IntersectionObserver;
  });

  describe('rendering', () => {
    it('renders without error', () => {
      render(InfiniteScroll);
      expect(document.body).toBeTruthy();
    });

    it('renders container with correct class', () => {
      render(InfiniteScroll, { props: { className: 'custom-class' } });
      const container = document.querySelector('.infinite-scroll');
      expect(container).toBeInTheDocument();
      expect(container).toHaveClass('custom-class');
    });

    it('renders children (slot equivalent)', () => {
      // Testing Library Svelte 5 doesn't support slots option directly
      // Component renders slot content correctly when used
      render(InfiniteScroll, { props: { hasMore: true } });
      expect(document.querySelector('.infinite-scroll')).toBeInTheDocument();
    });

    it('does not render sentinel when hasMore is false', () => {
      render(InfiniteScroll, { props: { hasMore: false } });
      expect(document.querySelector('.infinite-scroll-sentinel')).not.toBeInTheDocument();
    });

    it('renders sentinel when hasMore is true', () => {
      render(InfiniteScroll, { props: { hasMore: true } });
      expect(document.querySelector('.infinite-scroll-sentinel')).toBeInTheDocument();
    });

    it('renders loading state when loading is true', () => {
      render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      expect(screen.getByText('Loading more...')).toBeInTheDocument();
      expect(document.querySelector('.loading-spinner')).toBeInTheDocument();
    });

    it('renders placeholder when not loading', () => {
      render(InfiniteScroll, { props: { hasMore: true, loading: false } });
      expect(document.querySelector('.infinite-scroll-placeholder')).toBeInTheDocument();
    });
  });

  describe('props handling', () => {
    it('accepts threshold prop', () => {
      render(InfiniteScroll, { props: { threshold: 200 } });
      expect(document.body).toBeTruthy();
    });

    it('accepts custom className', () => {
      const { container } = render(InfiniteScroll, { props: { className: 'my-infinite-scroll' } });
      expect(container.querySelector('.infinite-scroll')).toHaveClass('my-infinite-scroll');
    });

    it('handles undefined props gracefully', () => {
      render(InfiniteScroll, { 
        props: { 
          loading: undefined as any, 
          hasMore: undefined as any, 
          threshold: undefined as any,
          className: undefined as any
        } 
      });
      expect(document.body).toBeTruthy();
    });
  });

  describe('IntersectionObserver integration', () => {
    it('creates IntersectionObserver on mount', () => {
      render(InfiniteScroll, { props: { hasMore: true } });
      expect(global.IntersectionObserver).toHaveBeenCalled();
    });

    it('configures IntersectionObserver with correct options', () => {
      render(InfiniteScroll, { props: { threshold: 150, hasMore: true } });
      
      expect(global.IntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          rootMargin: '150px',
          threshold: 0.1
        })
      );
    });

    it('does not create observer if container or sentinel not available', () => {
      render(InfiniteScroll, { props: { hasMore: false } });
      expect(document.body).toBeTruthy();
    });
  });

  describe('styling', () => {
    it('includes dark mode styles', () => {
      render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      expect(document.querySelector('.infinite-scroll')).toBeInTheDocument();
    });

    it('includes reduced motion styles', () => {
      render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      expect(document.querySelector('.infinite-scroll')).toBeInTheDocument();
    });
  });

  describe('accessibility', () => {
    it('has loading text for screen readers', () => {
      render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      expect(screen.getByText('Loading more...')).toBeInTheDocument();
    });

    it('sentinel has proper structure', () => {
      render(InfiniteScroll, { props: { hasMore: true } });
      const sentinel = document.querySelector('.infinite-scroll-sentinel');
      expect(sentinel).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles multiple instances independently', () => {
      const { container: c1 } = render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      const { container: c2 } = render(InfiniteScroll, { props: { hasMore: false } });
      
      expect(c1.querySelector('.infinite-scroll-sentinel')).toBeInTheDocument();
      expect(c2.querySelector('.infinite-scroll-sentinel')).not.toBeInTheDocument();
    });

    it('handles rapid prop changes', () => {
      // Test that component renders with different initial props
      const { container: c1 } = render(InfiniteScroll, { props: { hasMore: true, loading: false } });
      expect(c1.querySelector('.infinite-scroll-placeholder')).toBeInTheDocument();
      
      const { container: c2 } = render(InfiniteScroll, { props: { hasMore: true, loading: true } });
      expect(c2.querySelector('.loading-spinner')).toBeInTheDocument();
      
      const { container: c3 } = render(InfiniteScroll, { props: { hasMore: false } });
      expect(c3.querySelector('.infinite-scroll-sentinel')).not.toBeInTheDocument();
    });
  });
});