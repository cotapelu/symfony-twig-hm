import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import LiveStream from './LiveStream.svelte';

describe('LiveStream', () => {
  afterEach(() => cleanup());

  describe('rendering', () => {
    it('renders the LIVE badge', () => {
      render(LiveStream);
      expect(screen.getByText('LIVE')).toBeInTheDocument();
    });

    it('renders the viewer count', () => {
      render(LiveStream);
      expect(screen.getByText(/ngườí xem/)).toBeInTheDocument();
      expect(screen.getByText(/156/)).toBeInTheDocument();
    });

    it('renders the chat header', () => {
      render(LiveStream);
      expect(screen.getByText(/Chat/)).toBeInTheDocument();
    });

    it('renders the seeded initial message', () => {
      render(LiveStream);
      expect(screen.getByText('Hello!')).toBeInTheDocument();
      expect(screen.getByText('User1:')).toBeInTheDocument();
    });

    it('renders the message input with placeholder', () => {
      render(LiveStream);
      expect(screen.getByPlaceholderText('Nhập tin nhắn...')).toBeInTheDocument();
    });

    it('renders the send button', () => {
      render(LiveStream);
      expect(screen.getByRole('button', { name: 'Gửi' })).toBeInTheDocument();
    });
  });

  describe('sendMessage', () => {
    it('appends a new message when typing and pressing Enter', () => {
      render(LiveStream);
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: 'Xin chào' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      expect(screen.getByText('Xin chào')).toBeInTheDocument();
      expect(screen.getByText('You:')).toBeInTheDocument();
    });

    it('clears the input after sending', () => {
      render(LiveStream);
      const input = screen.getByPlaceholderText('Nhập tin nhắn...') as HTMLInputElement;
      fireEvent.input(input, { target: { value: 'Tin nhắn mới' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      expect(input.value).toBe('');
    });

    it('sends message via the Gửi button', () => {
      render(LiveStream);
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: 'Từ nút Gửi' } });
      fireEvent.click(screen.getByRole('button', { name: 'Gửi' }));
      expect(screen.getByText('Từ nút Gửi')).toBeInTheDocument();
    });

    it('does not send an empty message (guard clause)', () => {
      render(LiveStream);
      fireEvent.click(screen.getByRole('button', { name: 'Gửi' }));
      // only the seeded message remains, no "You:" entry
      expect(screen.queryByText('You:')).not.toBeInTheDocument();
    });

    it('does not send whitespace-only message', () => {
      render(LiveStream);
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: '   ' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      expect(screen.queryByText('You:')).not.toBeInTheDocument();
      expect((input as HTMLInputElement).value).toBe('   ');
    });

    it('appends multiple messages in order', () => {
      render(LiveStream);
      const input = screen.getByPlaceholderText('Nhập tin nhắn...');
      fireEvent.input(input, { target: { value: 'Một' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      fireEvent.input(input, { target: { value: 'Hai' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      expect(screen.getByText('Một')).toBeInTheDocument();
      expect(screen.getByText('Hai')).toBeInTheDocument();
    });
  });
});
