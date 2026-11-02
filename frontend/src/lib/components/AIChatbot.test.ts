import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import AIChatbot from './AIChatbot.svelte';

describe('AIChatbot Component', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    fetchMock = vi.fn();
    global.fetch = fetchMock;
    // Mock fetch for smart replies (returns array)
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockResolvedValueOnce([{ text: 'Reply 1' }, { text: 'Reply 2' }])
    });
  });

  it('renders floating toggle button with aria-label', () => {
    render(AIChatbot);
    const button = screen.getByRole('button', { name: /Trợ lý AI/i });
    expect(button).toBeTruthy();
  });

  it('opens chat panel and shows input when toggle button clicked', async () => {
    render(AIChatbot);
    const toggleButton = screen.getByRole('button', { name: /Trợ lý AI/i });
    fireEvent.click(toggleButton);
    await waitFor(() => {
      expect(screen.getByPlaceholderText('Nhập tin nhắn...')).toBeTruthy();
    });
  });

  it('displays chat header after opening', async () => {
    render(AIChatbot);
    const toggleButton = screen.getByRole('button', { name: /Trợ lý AI/i });
    fireEvent.click(toggleButton);
    await waitFor(() => {
      expect(screen.getByText(/Trợ lý AI/)).toBeTruthy();
    });
  });

  it('allows typing message', async () => {
    render(AIChatbot);
    const toggleButton = screen.getByRole('button', { name: /Trợ lý AI/i });
    fireEvent.click(toggleButton);
    await waitFor(() => {
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: 'Hello AI' } });
      expect(input.value).toBe('Hello AI');
    });
  });

  it('sends message when send button clicked', async () => {
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockResolvedValueOnce({ reply: 'AI response' })
    });

    render(AIChatbot);
    const toggleButton = screen.getByRole('button', { name: /Trợ lý AI/i });
    fireEvent.click(toggleButton);

    await waitFor(() => {
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: 'Test' } });
    });

    const sendButton = screen.getByRole('button', { name: /Gửi/i });
    fireEvent.click(sendButton);

    // Should show user message
    await waitFor(() => {
      expect(screen.getByText('Test')).toBeTruthy();
    });
    // Should have called fetch endpoint
    expect(fetchMock).toHaveBeenCalledWith('/api/ai/chat', expect.any(Object));
  });

  it('has send button disabled when input is empty', async () => {
    render(AIChatbot);
    const toggleButton = screen.getByRole('button', { name: /Trợ lý AI/i });
    fireEvent.click(toggleButton);
    await waitFor(() => {
      const sendButton = screen.getByRole('button', { name: /Gửi/i });
      expect(sendButton).toBeDisabled();
    });
  });
});
