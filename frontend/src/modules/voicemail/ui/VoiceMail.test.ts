import { describe, it, expect, afterEach } from 'vitest';
import { render, screen, fireEvent, cleanup } from '@testing-library/svelte';
import VoiceMail from './VoiceMail.svelte';

describe('VoiceMail', () => {
  afterEach(() => cleanup());

  describe('rendering', () => {
    it('renders the title', () => {
      render(VoiceMail);
      expect(screen.getByText(/Hộp thư thoại/)).toBeInTheDocument();
    });

    it('renders all three seeded voicemails', () => {
      render(VoiceMail);
      expect(screen.getByText('Nguyễn Văn A')).toBeInTheDocument();
      expect(screen.getByText('Trần Thị B')).toBeInTheDocument();
      expect(screen.getByText('Lê Văn C')).toBeInTheDocument();
    });

    it('renders duration and date metadata', () => {
      render(VoiceMail);
      expect(screen.getByText(/2:30/)).toBeInTheDocument();
      expect(screen.getByText(/2024-01-15/)).toBeInTheDocument();
    });

    it('marks unread message with the unread class', () => {
      render(VoiceMail);
      expect(document.querySelector('.voicemail-item.unread')).toBeInTheDocument();
      expect(document.querySelectorAll('.voicemail-item.unread').length).toBe(1);
    });
  });

  describe('playVoicemail', () => {
    it('marks an unread message as listened (removes unread)', () => {
      render(VoiceMail);
      expect(document.querySelectorAll('.voicemail-item.unread').length).toBe(1);
      const playButtons = screen.getAllByRole('button');
      fireEvent.click(playButtons[0]); // first voicemail (unread)
      expect(document.querySelectorAll('.voicemail-item.unread').length).toBe(0);
    });

    it('toggles the play/pause icon for the playing item', () => {
      render(VoiceMail);
      const playButtons = screen.getAllByRole('button');
      // initially shows ▶️ for all
      expect(screen.getAllByText('▶️').length).toBe(3);
      fireEvent.click(playButtons[0]);
      expect(screen.getByText('⏸️')).toBeInTheDocument();
    });
  });

  describe('deleteVoicemail', () => {
    it('removes a voicemail from the list', () => {
      render(VoiceMail);
      const deleteButtons = screen.getAllByRole('button', { name: '🗑️' });
      fireEvent.click(deleteButtons[1]); // delete Trần Thị B
      expect(screen.queryByText('Trần Thị B')).not.toBeInTheDocument();
      expect(screen.getByText('Nguyễn Văn A')).toBeInTheDocument();
    });

    it('deletes all voicemails independently', () => {
      render(VoiceMail);
      ['Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C'].forEach((_, idx) => {
        const deleteButtons = screen.getAllByRole('button', { name: '🗑️' });
        fireEvent.click(deleteButtons[0]);
      });
      expect(screen.queryByText('Nguyễn Văn A')).not.toBeInTheDocument();
    });

    it('shows empty state when all voicemails are deleted (edge)', () => {
      render(VoiceMail);
      for (let i = 0; i < 3; i++) {
        const deleteButtons = screen.getAllByRole('button', { name: '🗑️' });
        fireEvent.click(deleteButtons[0]);
      }
      expect(screen.getByText('Không có tin nhắn thoại nào')).toBeInTheDocument();
    });
  });
});
