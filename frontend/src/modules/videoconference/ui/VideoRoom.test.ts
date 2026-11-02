import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import VideoRoom from './VideoRoom.svelte';

describe('VideoRoom Component', () => {
  it('renders video room layout', () => {
    render(VideoRoom);
    const videoGrid = document.querySelector('.video-grid');
    expect(videoGrid).toBeTruthy();
  });

  it('displays both participants', () => {
    render(VideoRoom);
    expect(screen.getByText('Bạn (Host)')).toBeTruthy();
    expect(screen.getByText('User 2')).toBeTruthy();
  });

  it('shows video placeholder for participants with video enabled', () => {
    render(VideoRoom);
    const placeholders = document.querySelectorAll('.video-placeholder');
    expect(placeholders).toHaveLength(2);
  });

  it('shows microphone indicator when audio is off', () => {
    render(VideoRoom);
    const indicators = document.querySelectorAll('.indicators');
    expect(indicators[1].textContent).toBe('🔇'); // User 2 has audio: false
    expect(indicators[0].textContent).toBe(''); // Host has audio: true
  });

  it('renders control buttons', () => {
    render(VideoRoom);
    expect(screen.getByRole('button', { name: '🎤' })).toBeTruthy();
    expect(screen.getByRole('button', { name: '📹' })).toBeTruthy();
    expect(screen.getByRole('button', { name: '🖥️' })).toBeTruthy();
    expect(screen.getByRole('button', { name: /Rời phòng/i })).toBeTruthy();
  });

  it('toggles audio when microphone button clicked', async () => {
    render(VideoRoom);
    const audioButton = screen.getByRole('button', { name: '🎤' });
    fireEvent.click(audioButton);
    await waitFor(() => {
      expect(audioButton.textContent).toBe('🔇');
    });
    fireEvent.click(audioButton);
    await waitFor(() => {
      expect(audioButton.textContent).toBe('🎤');
    });
  });

  it('toggles video when camera button clicked', async () => {
    render(VideoRoom);
    const videoButton = screen.getByRole('button', { name: '📹' });
    fireEvent.click(videoButton);
    await waitFor(() => {
      expect(videoButton.textContent).toBe('📷');
    });
    fireEvent.click(videoButton);
    await waitFor(() => {
      expect(videoButton.textContent).toBe('📹');
    });
  });

  it('toggles screen sharing when button clicked', async () => {
    render(VideoRoom);
    const shareButton = screen.getByRole('button', { name: '🖥️' });
    expect(shareButton.textContent).toBe('🖥️');
    fireEvent.click(shareButton);
    await waitFor(() => {
      expect(shareButton.textContent).toBe('📺');
    });
    fireEvent.click(shareButton);
    await waitFor(() => {
      expect(shareButton.textContent).toBe('🖥️');
    });
  });

  it('displays chat panel with input', () => {
    render(VideoRoom);
    const chatInput = screen.getByPlaceholderText('Nhập tin nhắn...');
    expect(chatInput).toBeTruthy();
  });

  it('sends chat message on button click or Enter key', async () => {
    render(VideoRoom);
    const input = screen.getByPlaceholderText('Nhập tin nhắn...');
    // set value
    fireEvent.input(input, { target: { value: 'Hello everyone' } });
    fireEvent.keyPress(input, { key: 'Enter', code: 'Enter' });
    await waitFor(() => {
      expect(screen.getByText(/Hello everyone/)).toBeTruthy();
    });
  });

  it('clears input after sending message', async () => {
    render(VideoRoom);
    const input = screen.getByPlaceholderText('Nhập tin nhắn...');
    fireEvent.input(input, { target: { value: 'Test message' } });
    fireEvent.keyPress(input, { key: 'Enter', code: 'Enter' });
    await waitFor(() => {
      expect(input.value).toBe('');
    });
  });

  it('does not send empty messages', async () => {
    render(VideoRoom);
    const input = screen.getByPlaceholderText('Nhập tin nhắn...') as HTMLInputElement;
    fireEvent.input(input, { target: { value: '   ' } });
    fireEvent.keyPress(input, { key: 'Enter', code: 'Enter' });
    await waitFor(() => {
      const messages = document.querySelectorAll('.message');
      expect(messages).toHaveLength(0);
    });
  });
});
