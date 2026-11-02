import { describe, it, expect, afterEach } from 'vitest';
import { render, cleanup } from '@testing-library/svelte';
import ParticipantGrid from './ParticipantGrid.svelte';

const p = (id: string, name: string, over: Partial<any> = {}) => ({
  id, name, isVideoOn: false, isHost: false, isMuted: false,
  isScreenSharing: false, isSpeaking: false, ...over
});

describe('ParticipantGrid', () => {
  afterEach(() => cleanup());

  it('renders nothing but a grid container when no participants', () => {
    render(ParticipantGrid, { props: { participants: [] } });
    expect(document.querySelector('.participant-grid')).toBeInTheDocument();
    expect(document.querySelectorAll('.participant-card').length).toBe(0);
  });

  it('renders each participant name', () => {
    render(ParticipantGrid, { props: { participants: [p('a', 'Alice'), p('b', 'Bob')] } });
    expect(document.body.textContent).toContain('Alice');
    expect(document.body.textContent).toContain('Bob');
  });

  describe('getGridClass boundaries', () => {
    // getGridClass: <=1→cols-1, 2..4→cols-2, 5..6→cols-3, 7+→cols-4
    it('single total (no local) => grid-cols-1', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice')] } });
      expect(document.querySelector('.grid-cols-1')).toBeInTheDocument();
    });
    it('remote + local (2 total) => grid-cols-2', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice')], localParticipant: p('me', 'Me') } });
      expect(document.querySelector('.grid-cols-2')).toBeInTheDocument();
    });
    it('three total (2 remote + local) => grid-cols-2 (count<=4)', () => {
      render(ParticipantGrid, {
        props: { participants: [p('a', 'A'), p('b', 'B')], localParticipant: p('me', 'Me') }
      });
      expect(document.querySelector('.grid-cols-2')).toBeInTheDocument();
    });
    it('five total (4 remote + local) => grid-cols-3 (count<=6)', () => {
      const many = Array.from({ length: 4 }, (_, i) => p(`x${i}`, `P${i}`));
      render(ParticipantGrid, { props: { participants: many, localParticipant: p('me', 'Me') } });
      expect(document.querySelector('.grid-cols-3')).toBeInTheDocument();
    });
    it('seven total (6 remote + local) => grid-cols-4', () => {
      const many = Array.from({ length: 6 }, (_, i) => p(`x${i}`, `P${i}`));
      render(ParticipantGrid, { props: { participants: many, localParticipant: p('me', 'Me') } });
      expect(document.querySelector('.grid-cols-4')).toBeInTheDocument();
    });
  });

  describe('badges and media state', () => {
    it('shows Host badge for host participant', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice', { isHost: true })] } });
      expect(document.body.textContent).toContain('Host');
    });
    it('shows You badge for the local participant', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice')], localParticipant: p('me', 'Me') } });
      expect(document.body.textContent).toContain('You');
    });
    it('renders avatar initial when video is off', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice', { isVideoOn: false })] } });
      expect(document.querySelector('video')).not.toBeInTheDocument();
      expect(document.body.textContent).toContain('A');
    });
    it('renders a video element when video is on', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice', { isVideoOn: true })] } });
      expect(document.querySelector('video')).toBeInTheDocument();
    });
    it('renders muted, screen-sharing and speaking indicators', () => {
      render(ParticipantGrid, {
        props: { participants: [p('a', 'Alice', { isMuted: true, isScreenSharing: true, isSpeaking: true })] }
      });
      expect(document.querySelector('.bg-red-500\\/80')).toBeInTheDocument();
      expect(document.querySelector('.bg-cyan-500\\/80')).toBeInTheDocument();
      expect(document.querySelector('.bg-green-500\\/80')).toBeInTheDocument();
    });
    it('does not render status icons when flags are false', () => {
      render(ParticipantGrid, { props: { participants: [p('a', 'Alice')] } });
      expect(document.querySelector('.bg-red-500\\/80')).not.toBeInTheDocument();
    });
  });
});
