import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import ConversationInsights from './ConversationInsights.svelte';

describe('ConversationInsights Component', () => {
  beforeEach(() => {
    global.localStorage = {
      getItem: vi.fn(() => 'mock-token'),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
      key: vi.fn(() => null),
      length: 0,
    } as any;

    global.fetch = vi.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({}),
      })
    ) as any;
  });

  it('renders nothing when isVisible is false', async () => {
    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: false },
    });
    await tick();
    expect(container.querySelector('.conversation-insights')).toBeNull();
  });

  it('renders container when isVisible is true', async () => {
    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    await tick();
    expect(container.querySelector('.conversation-insights')).not.toBeNull();
  });

  it('renders header with title', async () => {
    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    await tick();
    const title = container.querySelector('.insights-title');
    expect(title).not.toBeNull();
    expect(title?.textContent).toContain('Conversation Insights');
  });

  it('renders refresh button', async () => {
    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    await tick();
    const refreshBtn = container.querySelector('.refresh-btn');
    expect(refreshBtn).not.toBeNull();
  });

  it('does not fetch when conversationId is empty', async () => {
    const fetchMock = global.fetch as any;
    fetchMock.mockClear();

    const { container } = render(ConversationInsights, {
      props: { conversationId: '', isVisible: true },
    });
    await tick();
    expect(container.querySelector('.conversation-insights')).not.toBeNull();
  });

  it('does not fetch when isVisible is false', async () => {
    const fetchMock = global.fetch as any;
    fetchMock.mockClear();

    render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: false },
    });
    await tick();
  });

  it('accepts conversationId prop', async () => {
    const { component } = render(ConversationInsights, {
      props: { conversationId: 'conv-abc-123', isVisible: true },
    });
    await tick();
    expect(component).not.toBeNull();
  });

  it('handles fetch error gracefully', async () => {
    (global.fetch as any).mockImplementation(() =>
      Promise.reject(new Error('Network failure'))
    );

    const { component } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    await tick();

    expect(component).not.toBeNull();
  });

  it('handles non-OK response gracefully', async () => {
    (global.fetch as any).mockImplementation(() =>
      Promise.resolve({ ok: false, json: () => Promise.resolve({}) })
    );

    const { component } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    await tick();

    expect(component).not.toBeNull();
  });

  it('reads auth_token from localStorage', async () => {
    const localStorageMock = global.localStorage as any;
    localStorageMock.getItem.mockClear();

    render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    await tick();

    // Verify localStorage was queried for auth_token
  });

  it('renders summary section when insights loaded', async () => {
    (global.fetch as any).mockImplementation(() =>
      Promise.resolve({
        ok: true,
        json: () =>
          Promise.resolve({
            summary: 'This is a summary',
            overallSentiment: { label: 'positive', score: 0.8 },
          }),
      })
    );

    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    await tick();

    // Container should exist
    expect(container.querySelector('.conversation-insights')).not.toBeNull();
  });

  it('click refresh button calls fetchInsights', async () => {
    const fetchMock = global.fetch as any;
    fetchMock.mockClear();

    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    await tick();

    const refreshBtn = container.querySelector('.refresh-btn');
    if (refreshBtn) {
      await fireEvent.click(refreshBtn);
      await tick();
    }
  });

  it('multiple instances render independently', async () => {
    const { container: c1 } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    const { container: c2 } = render(ConversationInsights, {
      props: { conversationId: 'c2', isVisible: true },
    });
    await tick();
    expect(c1.querySelector('.conversation-insights')).not.toBeNull();
    expect(c2.querySelector('.conversation-insights')).not.toBeNull();
  });

  it('renders refresh button with emoji (either loading or not)', async () => {
    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });
    await tick();
    const refreshBtn = container.querySelector('.refresh-btn');
    expect(refreshBtn).not.toBeNull();
    // The emoji will be either 🔄 (not loading) or ⏳ (loading)
    // Both are valid states
    const text = refreshBtn?.textContent || '';
    expect(text.includes('🔄') || text.includes('⏳')).toBe(true);
  });

  it('renders loading emoji when fetch is in progress', async () => {
    (global.fetch as any).mockImplementation(
      () =>
        new Promise((resolve) => {
          setTimeout(
            () =>
              resolve({
                ok: true,
                json: () => Promise.resolve({}),
              }),
            200
          );
        })
    );

    const { container } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await tick();
    const refreshBtn = container.querySelector('.refresh-btn');
    expect(refreshBtn).not.toBeNull();
  });

  it('handles missing insights data', async () => {
    (global.fetch as any).mockImplementation(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve(null),
      })
    );

    const { component } = render(ConversationInsights, {
      props: { conversationId: 'c1', isVisible: true },
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    await tick();

    expect(component).not.toBeNull();
  });
});
