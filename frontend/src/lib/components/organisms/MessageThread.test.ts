import { describe, it, expect, vi, afterEach } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import MessageThread from './MessageThread.svelte';

describe('MessageThread Component', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders thread-container wrapper', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    expect(container.querySelector('.thread-container')).not.toBeNull();
  });

  it('renders thread-toggle button with initial 0 thread count', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle');
    expect(btn).not.toBeNull();
    expect(btn?.textContent).toContain('0 thread');
  });

  it('thread-toggle button shows plural "threads" when count != 1', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle');
    expect(btn?.textContent).toContain('threads');
  });

  it('thread-toggle button shows 💬 emoji prefix', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle');
    expect(btn?.textContent).toContain('💬');
  });

  it('omits thread-input when toggle is not clicked', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    expect(container.querySelector('.thread-input')).toBeNull();
  });

  it('omits threads-list when there are no threads', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    expect(container.querySelector('.threads-list')).toBeNull();
  });

  it('clicking thread-toggle reveals thread-input', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    expect(btn).not.toBeNull();
    await fireEvent.click(btn);
    await tick();
    expect(container.querySelector('.thread-input')).not.toBeNull();
  });

  it('clicking thread-toggle twice hides thread-input', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(btn);
    await tick();
    expect(container.querySelector('.thread-input')).not.toBeNull();
    await fireEvent.click(btn);
    await tick();
    expect(container.querySelector('.thread-input')).toBeNull();
  });

  it('thread-input contains textarea for content', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(btn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea');
    expect(textarea).not.toBeNull();
    expect(textarea?.getAttribute('placeholder')).toBe('Reply in thread...');
  });

  it('thread-input contains Reply button', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(btn);
    await tick();
    const replyBtn = Array.from(container.querySelectorAll('.thread-input button')).find(b => b.textContent?.includes('Reply'));
    expect(replyBtn).not.toBeUndefined();
  });

  it('renders without crashing with default empty messageId', async () => {
    const { container } = render(MessageThread);
    await tick();
    expect(container.querySelector('.thread-container')).not.toBeNull();
  });

  it('renders without crashing with default empty conversationId', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    expect(container.querySelector('.thread-container')).not.toBeNull();
  });

  it('clicking Reply with empty content does not throw', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1', conversationId: 'conv-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const replyBtn = Array.from(container.querySelectorAll('.thread-input button')).find(b => b.textContent?.includes('Reply')) as HTMLButtonElement;
    expect(replyBtn).not.toBeUndefined();
    // Click without typing — should no-throw
    await fireEvent.click(replyBtn);
    await tick();
  });

  it('subscribes to thread topic at component init (logs subscribe message)', async () => {
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    render(MessageThread, { props: { messageId: 'msg-abc' } });
    await tick();
    expect(consoleSpy).toHaveBeenCalledWith('Subscribed to:', 'thread/msg-abc');
    consoleSpy.mockRestore();
  });

  it('multiple MessageThread instances each subscribe to their own topic', async () => {
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    const { unmount: u1 } = render(MessageThread, { props: { messageId: 'msg-1' } });
    const { unmount: u2 } = render(MessageThread, { props: { messageId: 'msg-2' } });
    await tick();
    const logs = consoleSpy.mock.calls.map(c => c[1]);
    expect(logs).toContain('thread/msg-1');
    expect(logs).toContain('thread/msg-2');
    u1();
    u2();
    consoleSpy.mockRestore();
  });

  it('textarea is empty by default', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea') as HTMLTextAreaElement;
    expect(textarea?.value).toBe('');
  });

  it('textarea accepts typed content', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea') as HTMLTextAreaElement;
    await fireEvent.input(textarea, { target: { value: 'Hello thread' } });
    await tick();
    expect(textarea.value).toBe('Hello thread');
  });

  it('thread-toggle click does not throw with empty content scenario', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const btn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(btn);
    await tick();
    // No throw
    expect(container.querySelector('.thread-container')).not.toBeNull();
  });

  it('does not throw when Enter pressed in textarea with empty content', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea') as HTMLTextAreaElement;
    await fireEvent.keyPress(textarea, { key: 'Enter' });
    await tick();
    expect(container.querySelector('.thread-container')).not.toBeNull();
  });

  it('does not throw when Enter pressed with content', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1', conversationId: 'conv-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea') as HTMLTextAreaElement;
    await fireEvent.input(textarea, { target: { value: 'Some content' } });
    await tick();
    await fireEvent.keyPress(textarea, { key: 'Enter' });
    await tick();
  });

  it('does not submit when Shift+Enter is pressed (allows newline)', async () => {
    const { container } = render(MessageThread, { props: { messageId: 'msg-1', conversationId: 'conv-1' } });
    await tick();
    const toggleBtn = container.querySelector('.thread-toggle') as HTMLButtonElement;
    await fireEvent.click(toggleBtn);
    await tick();
    const textarea = container.querySelector('.thread-input textarea') as HTMLTextAreaElement;
    await fireEvent.input(textarea, { target: { value: 'Line 1' } });
    await tick();
    await fireEvent.keyPress(textarea, { key: 'Enter', shiftKey: true });
    await tick();
    // After Shift+Enter, textarea should still be visible
    expect(container.querySelector('.thread-input')).not.toBeNull();
  });

  it('does not throw on initial render with various messageId values', async () => {
    const ids = ['', 'msg-1', 'msg-with-special-chars-123', 'a-very-long-message-id-with-many-segments'];
    for (const id of ids) {
      const { container, unmount } = render(MessageThread, { props: { messageId: id } });
      await tick();
      expect(container.querySelector('.thread-container')).not.toBeNull();
      unmount();
    }
  });
});
