import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import ThreadItem from './ThreadItem.svelte';

const baseThread = {
  id: 't1',
  title: 'Bug in payment flow',
  replies: [
    {
      sender: { name: 'Alice' },
      createdAt: '2026-07-27T10:30:00Z',
      content: 'I see the same issue.',
    },
    {
      sender: { name: 'Bob' },
      createdAt: '2026-07-27T11:00:00Z',
      content: 'Reproduced on staging.',
    },
  ],
  lastActivity: '2026-07-27T11:00:00Z',
};

describe('ThreadItem Component', () => {
  it('renders nothing when thread is null', async () => {
    const { container } = render(ThreadItem, { props: { thread: null } });
    await tick();
    expect(container.querySelector('.thread-item')).toBeNull();
  });

  it('renders wrapper with thread-item class', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    expect(container.querySelector('.thread-item')).not.toBeNull();
  });

  it('renders thread title', async () => {
    render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    expect(screen.getByText('Bug in payment flow')).not.toBeNull();
  });

  it('renders default title "Thread" when title is missing', async () => {
    render(ThreadItem, { props: { thread: { id: 't1' } } });
    await tick();
    expect(screen.getByText('Thread')).not.toBeNull();
  });

  it('renders thread icon', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    expect(container.querySelector('.thread-icon')?.textContent).toContain('🧵');
  });

  it('renders reply count', async () => {
    render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    expect(screen.getByText('2 replies')).not.toBeNull();
  });

  it('renders 0 replies when replies array is empty', async () => {
    render(ThreadItem, { props: { thread: { id: 't1', title: 'T', replies: [] } } });
    await tick();
    expect(screen.getByText('0 replies')).not.toBeNull();
  });

  it('renders 0 replies when replies is undefined', async () => {
    render(ThreadItem, { props: { thread: { id: 't1', title: 'T' } } });
    await tick();
    expect(screen.getByText('0 replies')).not.toBeNull();
  });

  it('renders last activity timestamp when present', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    const meta = container.querySelector('.thread-last-activity');
    expect(meta?.textContent?.trim()).not.toBe('');
  });

  it('renders empty last-activity span when timestamp is missing', async () => {
    const { container } = render(ThreadItem, {
      props: { thread: { id: 't1', title: 'T' } },
    });
    await tick();
    const meta = container.querySelector('.thread-last-activity');
    expect(meta?.textContent?.trim()).toBe('');
  });

  it('does not show thread-content by default (collapsed)', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: false } });
    await tick();
    expect(container.querySelector('.thread-content')).toBeNull();
  });

  it('shows thread-content when isExpanded=true', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    expect(container.querySelector('.thread-content')).not.toBeNull();
  });

  it('applies expanded class when isExpanded=true', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    expect(container.querySelector('.thread-item')?.classList.contains('expanded')).toBe(true);
  });

  it('does not apply expanded class when collapsed', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: false } });
    await tick();
    expect(container.querySelector('.thread-item')?.classList.contains('expanded')).toBe(false);
  });

  it('renders reply messages when expanded', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    const replies = container.querySelectorAll('.thread-reply');
    expect(replies.length).toBe(2);
  });

  it('renders sender name in reply header', async () => {
    render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    expect(screen.getByText('Alice')).not.toBeNull();
    expect(screen.getByText('Bob')).not.toBeNull();
  });

  it('renders reply content text', async () => {
    render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    expect(screen.getByText('I see the same issue.')).not.toBeNull();
    expect(screen.getByText('Reproduced on staging.')).not.toBeNull();
  });

  it('renders avatar with first letter of sender name', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    const avatars = container.querySelectorAll('.reply-avatar');
    expect(avatars[0]?.textContent?.trim()).toBe('A');
    expect(avatars[1]?.textContent?.trim()).toBe('B');
  });

  it('renders ? placeholder avatar for unknown sender', async () => {
    const thread = {
      id: 't1',
      title: 'T',
      replies: [
        {
          createdAt: '2026-07-27T10:00:00Z',
          content: 'Anonymous reply',
        },
      ],
    };
    const { container } = render(ThreadItem, { props: { thread, isExpanded: true } });
    await tick();
    const avatar = container.querySelector('.reply-avatar');
    expect(avatar?.textContent?.trim()).toBe('?');
  });

  it('renders "Unknown" when sender name is missing', async () => {
    const thread = {
      id: 't1',
      title: 'T',
      replies: [
        { createdAt: '2026-07-27T10:00:00Z', content: 'X' },
      ],
    };
    render(ThreadItem, { props: { thread, isExpanded: true } });
    await tick();
    expect(screen.getByText('Unknown')).not.toBeNull();
  });

  it('renders reply button when expanded', async () => {
    render(ThreadItem, { props: { thread: baseThread, isExpanded: true } });
    await tick();
    expect(screen.getByText(/Reply to thread/)).not.toBeNull();
  });

  it('clicking reply button calls onReply and dispatches', async () => {
    const onReply = vi.fn();
    const { container } = render(ThreadItem, {
      props: { thread: baseThread, isExpanded: true, onReply },
    });
    await tick();
    const btn = container.querySelector('.reply-btn');
    if (btn) {
      await fireEvent.click(btn);
    }
    expect(onReply).toHaveBeenCalledWith(baseThread);
  });

  it('does not throw when reply clicked and thread is null', async () => {
    const onReply = vi.fn();
    const { container } = render(ThreadItem, { props: { thread: null, onReply } });
    await tick();
    expect(container.querySelector('.reply-btn')).toBeNull();
    expect(onReply).not.toHaveBeenCalled();
  });

  it('header has role="button" for accessibility', async () => {
    const { container } = render(ThreadItem, { props: { thread: baseThread } });
    await tick();
    const header = container.querySelector('.thread-header');
    expect(header?.getAttribute('role')).toBe('button');
    expect(header?.getAttribute('tabindex')).toBe('0');
  });
});
