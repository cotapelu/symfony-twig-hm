import { describe, it, expect, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import StoryRing from './StoryRing.svelte';

/**
 * Pure presentational avatar-ring for a story. Branches: viewed vs new state,
 * avatar fallback, hoursLeft>0 vs 'Expires soon', onClick wiring, aria-label.
 * `expiryMarginDays`-free timestamps are derived from Date.now(); future tests
 * use a >1h buffer so floor() stays deterministic.
 */
function makeStory(overrides: Record<string, unknown> = {}) {
  return {
    expiresAt: new Date(Date.now() + 2 * 3600000).toISOString(), // ~2h → "1h left"
    userName: 'Alice',
    userAvatar: '/a.png',
    ...overrides,
  } as any;
}

afterEach(() => cleanup());

describe('StoryRing', () => {
  describe('rendering', () => {
    it('renders the user name and an accessible button label', () => {
      render(StoryRing, { props: { story: makeStory(), onClick: vi.fn() } });
      expect(screen.getByText('Alice')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'View story by Alice' })).toBeInTheDocument();
    });

    it('uses the provided avatar', () => {
      render(StoryRing, { props: { story: makeStory({ userAvatar: '/me.png' }), onClick: vi.fn() } });
      expect(screen.getByAltText('Alice')).toHaveAttribute('src', '/me.png');
    });

    it("falls back to '/default-avatar.png' when no avatar", () => {
      render(StoryRing, { props: { story: makeStory({ userAvatar: undefined }), onClick: vi.fn() } });
      expect(screen.getByAltText('Alice')).toHaveAttribute('src', '/default-avatar.png');
    });
  });

  describe('viewed vs new state', () => {
    it('marks NEW when the current user has not viewed the story', () => {
      render(StoryRing, { props: { story: makeStory(), onClick: vi.fn() } });
      expect(screen.getByText('NEW')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'View story by Alice' })).toHaveClass('new');
    });

    it('marks viewed (no NEW badge) when current user is in viewedBy', () => {
      render(StoryRing, {
        props: { story: makeStory({ viewedBy: ['me'], currentUser: { id: 'me' } }), onClick: vi.fn() },
      });
      expect(screen.queryByText('NEW')).not.toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'View story by Alice' })).toHaveClass('viewed');
    });

    it('stays NEW when viewedBy contains a different user', () => {
      render(StoryRing, {
        props: { story: makeStory({ viewedBy: ['other'], currentUser: { id: 'me' } }), onClick: vi.fn() },
      });
      expect(screen.getByText('NEW')).toBeInTheDocument();
    });
  });

  describe('expiry', () => {
    it('shows remaining hours when the story expires in the future', () => {
      render(StoryRing, { props: { story: makeStory(), onClick: vi.fn() } });
      expect(screen.getByText('1h left')).toBeInTheDocument();
    });

    it("shows 'Expires soon' when the story is expired/past", () => {
      render(StoryRing, {
        props: { story: makeStory({ expiresAt: new Date(Date.now() - 3600000).toISOString() }), onClick: vi.fn() },
      });
      expect(screen.getByText('Expires soon')).toBeInTheDocument();
    });
  });

  describe('interaction', () => {
    it('invokes onClick when the ring is clicked', () => {
      const onClick = vi.fn();
      render(StoryRing, { props: { story: makeStory(), onClick } });
      fireEvent.click(screen.getByRole('button', { name: 'View story by Alice' }));
      expect(onClick).toHaveBeenCalledTimes(1);
    });
  });
});
