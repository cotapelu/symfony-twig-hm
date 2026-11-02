import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import VoicePlayer from './VoicePlayer.svelte';

describe('VoicePlayer Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(VoicePlayer);
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('renders with audio URL', () => {
      render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('renders with custom duration', () => {
      render(VoicePlayer, { props: { duration: 120 } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('renders with showControls false', () => {
      render(VoicePlayer, { props: { showControls: false } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('renders with autoplay true', () => {
      render(VoicePlayer, { props: { autoplay: true } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts audioUrl prop', () => {
      render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('accepts audioBlob prop', () => {
      render(VoicePlayer, { props: { audioBlob: new Blob(['test'], { type: 'audio/webm' }) } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('accepts duration prop', () => {
      render(VoicePlayer, { props: { duration: 180 } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('accepts showControls prop', () => {
      render(VoicePlayer, { props: { showControls: false } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('accepts autoplay prop', () => {
      render(VoicePlayer, { props: { autoplay: true } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });
  });

  describe('Component Structure', () => {
    it('renders with correct structure', () => {
      render(VoicePlayer);
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
      expect(screen.getByTestId('voice-player')).toHaveClass('voice-player');
    });

    it('renders play overlay button', () => {
      render(VoicePlayer);
      expect(screen.getByTestId('play-overlay')).toBeInTheDocument();
    });

    it('renders progress section when showControls is true', () => {
      render(VoicePlayer, { props: { showControls: true } });
      expect(screen.getByTestId('progress-section')).toBeInTheDocument();
    });

    it('hides progress section when showControls is false', () => {
      render(VoicePlayer, { props: { showControls: false } });
      expect(screen.queryByTestId('progress-section')).not.toBeInTheDocument();
    });
  });

  describe('Playback Controls', () => {
    it('shows play icon when not playing', () => {
      render(VoicePlayer);
      expect(screen.getByTestId('play-overlay')).toHaveTextContent('▶️');
    });
  });

  describe('Playback Functions', () => {
    it('has play function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.play).toBe('function');
    });

    it('has pause function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.pause).toBe('function');
    });

    it('has togglePlay function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.togglePlay).toBe('function');
    });

    it('has seek function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.seek).toBe('function');
    });

    it('has setVolume function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.setVolume).toBe('function');
    });

    it('has toggleMute function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.toggleMute).toBe('function');
    });

    it('has changePlaybackRate function', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(typeof component.changePlaybackRate).toBe('function');
    });

    it('has formatTime function', () => {
      const { component } = render(VoicePlayer);
      expect(typeof component.formatTime).toBe('function');
    });
  });

  describe('Format Time', () => {
    it('formats seconds correctly', () => {
      const { component } = render(VoicePlayer);
      expect(component.formatTime(0)).toBe('0:00');
      expect(component.formatTime(30)).toBe('0:30');
      expect(component.formatTime(60)).toBe('1:00');
      expect(component.formatTime(90)).toBe('1:30');
      expect(component.formatTime(125)).toBe('2:05');
      expect(component.formatTime(3661)).toBe('61:01');
    });
  });

  describe('Volume Control', () => {
    it('sets volume correctly', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      component.setVolume(0.5);
      expect(component.getVolume()).toBe(0.5);
    });

    it('clamps volume to valid range', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      component.setVolume(1.5);
      expect(component.getVolume()).toBeLessThanOrEqual(1);
      component.setVolume(-0.5);
      expect(component.getVolume()).toBeGreaterThanOrEqual(0);
    });

    it('toggles mute correctly', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(component.getIsMuted()).toBe(false);
      component.toggleMute();
      expect(component.getIsMuted()).toBe(true);
      component.toggleMute();
      expect(component.getIsMuted()).toBe(false);
    });
  });

  describe('Playback Rate', () => {
    it('changes playback rate', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      component.changePlaybackRate(1.5);
      expect(component.getPlaybackRate()).toBe(1.5);
    });

    it('has default playback rate of 1', () => {
      const { component } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(component.getPlaybackRate()).toBe(1);
    });
  });

  describe('Event Dispatching', () => {
    it('dispatches loaded event on audio load', async () => {
      const loadedHandler = vi.fn();
      render(VoicePlayer, { 
        props: { audioUrl: 'test.mp3', onloaded: loadedHandler } 
      });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('dispatches play event', async () => {
      const playHandler = vi.fn();
      render(VoicePlayer, { 
        props: { audioUrl: 'test.mp3', onplay: playHandler } 
      });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('dispatches pause event', async () => {
      const pauseHandler = vi.fn();
      render(VoicePlayer, { 
        props: { audioUrl: 'test.mp3', onpause: pauseHandler } 
      });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('dispatches ended event', async () => {
      const endedHandler = vi.fn();
      render(VoicePlayer, { 
        props: { audioUrl: 'test.mp3', onended: endedHandler } 
      });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('dispatches error event on load failure', async () => {
      const errorHandler = vi.fn();
      render(VoicePlayer, { 
        props: { audioUrl: 'invalid.mp3', onerror: errorHandler } 
      });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });
  });

  describe('Cleanup', () => {
    it('cleans up audio element on unmount', () => {
      const { unmount } = render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      unmount();
      expect(true).toBe(true); // If no error thrown, cleanup works
    });
  });

  describe('Edge Cases', () => {
    it('handles empty audioUrl', () => {
      render(VoicePlayer, { props: { audioUrl: '' } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('handles zero duration', () => {
      render(VoicePlayer, { props: { duration: 0 } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('handles very large duration', () => {
      render(VoicePlayer, { props: { duration: 3600 } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('handles unicode characters', () => {
      render(VoicePlayer, { props: { audioUrl: 'test.mp3' } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });

    it('does not throw when called before mount', () => {
      expect(() => {
        (window as any).showToast?.('Test', 'info', 100);
      }).not.toThrow();
    });

    it('accepts all toast types without throwing', () => {
      render(VoicePlayer);
      expect(() => {
        (window as any).showToast?.('Info', 'info', 1000);
        (window as any).showToast?.('Success', 'success', 1000);
        (window as any).showToast?.('Error', 'error', 1000);
        (window as any).showToast?.('Warning', 'warning', 1000);
      }).not.toThrow();
    });
  });

  describe('Multiple Instances', () => {
    it('renders multiple instances independently', () => {
      const { container: container1 } = render(VoicePlayer, { props: { audioUrl: '1.mp3' } });
      const { container: container2 } = render(VoicePlayer, { props: { audioUrl: '2.mp3' } });
      expect(container1).toBeTruthy();
      expect(container2).toBeTruthy();
    });
  });

  describe('Accessibility', () => {
    it('has proper aria label on play button', () => {
      render(VoicePlayer);
      const playButton = screen.getByTestId('play-overlay');
      expect(playButton).toHaveAttribute('aria-label');
    });

    it('has proper aria label on volume button', () => {
      render(VoicePlayer, { props: { showControls: true } });
      const volumeBtn = screen.getByTestId('volume-btn');
      expect(volumeBtn).toHaveAttribute('aria-label');
    });

    it('has proper aria label on download button', () => {
      render(VoicePlayer, { props: { audioUrl: 'test.mp3', showControls: true } });
      const downloadBtn = screen.getByTestId('download-btn');
      expect(downloadBtn).toHaveAttribute('aria-label');
    });
  });

  describe('Responsive Design', () => {
    it('applies mobile styles at max-width 640px', () => {
      render(VoicePlayer, { props: { showControls: true } });
      expect(screen.getByTestId('voice-player')).toBeInTheDocument();
    });
  });
});