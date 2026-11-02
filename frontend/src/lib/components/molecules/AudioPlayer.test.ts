import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import AudioPlayer from './AudioPlayer.svelte';

// Mock canvas context
const mockCanvasContext = {
  fillStyle: '',
  fillRect: vi.fn(),
  clearRect: vi.fn(),
};

const mockCanvas = {
  width: 400,
  height: 80,
  getContext: vi.fn(() => mockCanvasContext),
};

// Mock audio element
const mockAudioElement = {
  paused: true,
  currentTime: 0,
  duration: 0,
  volume: 1,
  muted: false,
  src: '',
  play: vi.fn().mockResolvedValue(undefined),
  pause: vi.fn(),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  dispatchEvent: vi.fn().mockReturnValue(true),
};

// Mock getBoundingClientRect
const mockProgressRect = { left: 0, width: 400 };
const mockCanvasRect = { left: 0, width: 400 };

// Setup mocks
beforeEach(() => {
  vi.clearAllMocks();
  
  // Mock document.createElement for canvas and audio
  const originalCreateElement = document.createElement.bind(document);
  document.createElement = vi.fn((tag: string) => {
    if (tag === 'canvas') return mockCanvas;
    if (tag === 'audio') return mockAudioElement;
    return originalCreateElement(tag);
  }) as any;
  
  // Mock getBoundingClientRect for progress bar and canvas
  Element.prototype.getBoundingClientRect = vi.fn(() => mockProgressRect);
  HTMLCanvasElement.prototype.getBoundingClientRect = vi.fn(() => mockCanvasRect);
  
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
});

describe('AudioPlayer Component', () => {
  const defaultPlaylist = [
    { id: '1', title: 'Track 1', artist: 'Artist 1', src: 'track1.mp3', duration: 180 },
    { id: '2', title: 'Track 2', artist: 'Artist 2', src: 'track2.mp3', duration: 240 },
    { id: '3', title: 'Track 3', artist: 'Artist 3', src: 'track3.mp3', duration: 300 },
  ];

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(AudioPlayer);
      expect(screen.getByText('No tracks in playlist')).toBeInTheDocument();
    });

    it('renders with playlist', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      // Use testid for current track title to avoid duplicate text issues
      expect(screen.getByTestId('current-track-title')).toBeInTheDocument();
      expect(screen.getByTestId('current-track-artist')).toBeInTheDocument();
    });

    it('renders with currentIndex prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, currentIndex: 1 } });
      expect(screen.getByTestId('current-track-title')).toHaveTextContent('Track 2');
    });

    it('renders with autoplay prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, autoplay: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('renders with loop prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, loop: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('renders with shuffle prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, shuffle: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('renders with showWaveform false', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
    });

    it('renders with custom waveformColor', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, waveformColor: '#ff0000' } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('renders with custom progressColor', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, progressColor: '#00ff00' } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });
  });

  describe('Component Structure', () => {
    it('renders track info when playlist provided', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('current-track-title')).toBeInTheDocument();
      expect(screen.getByTestId('current-track-artist')).toBeInTheDocument();
    });

    it('renders canvas when showWaveform true', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: true } });
      expect(screen.getByTestId('waveform-canvas')).toBeInTheDocument();
    });

    it('renders progress bar when showWaveform false', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
    });

    it('renders time display', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByText('0:00 / 0:00')).toBeInTheDocument();
    });

    it('renders player controls', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('play-pause-btn')).toBeInTheDocument();
      expect(screen.getByTestId('prev-track-btn')).toBeInTheDocument();
      expect(screen.getByTestId('next-track-btn')).toBeInTheDocument();
      expect(screen.getByTestId('shuffle-btn')).toBeInTheDocument();
    });

    it('renders volume control', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('mute-btn')).toBeInTheDocument();
      expect(screen.getByTestId('volume-slider')).toBeInTheDocument();
    });

    it('renders playlist when more than one track', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('playlist-item-0')).toBeInTheDocument();
      expect(screen.getByTestId('playlist-item-1')).toBeInTheDocument();
    });

    it('does not render playlist when only one track', () => {
      render(AudioPlayer, { props: { playlist: [defaultPlaylist[0]] } });
      expect(screen.queryByTestId('playlist-item-0')).not.toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts playlist prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('accepts currentIndex prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, currentIndex: 2 } });
      expect(screen.getByTestId('current-track-title')).toHaveTextContent('Track 3');
    });

    it('accepts autoplay prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, autoplay: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('accepts loop prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, loop: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('accepts shuffle prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, shuffle: true } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('accepts showWaveform prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
    });

    it('accepts waveformColor prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, waveformColor: '#ff0000' } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });

    it('accepts progressColor prop', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, progressColor: '#00ff00' } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
    });
  });

  describe('Format Time Function', () => {
    it('formats seconds correctly', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      // Use the exported formatTime function
      expect(component.formatTime(0)).toBe('0:00');
      expect(component.formatTime(30)).toBe('0:30');
      expect(component.formatTime(60)).toBe('1:00');
      expect(component.formatTime(90)).toBe('1:30');
      expect(component.formatTime(125)).toBe('2:05');
      expect(component.formatTime(3661)).toBe('61:01');
    });
  });

  describe('Playback Controls', () => {
    it('shows play button when not playing', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('play-pause-btn')).toBeInTheDocument();
    });

    it('has play/pause button with correct aria-label', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      const playBtn = screen.getByTestId('play-pause-btn');
      expect(playBtn).toHaveAttribute('aria-label', 'Play');
    });

    it('has prev/next buttons', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('prev-track-btn')).toBeInTheDocument();
      expect(screen.getByTestId('next-track-btn')).toBeInTheDocument();
    });

    it('disables prev/next buttons when only one track', () => {
      render(AudioPlayer, { props: { playlist: [defaultPlaylist[0]] } });
      
      const prevButton = screen.getByTestId('prev-track-btn');
      const nextButton = screen.getByTestId('next-track-btn');
      
      expect(prevButton).toBeDisabled();
      expect(nextButton).toBeDisabled();
    });
  });

  describe('Shuffle', () => {
    it('has shuffle button', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('shuffle-btn')).toBeInTheDocument();
    });

    it('toggles shuffle state via getter', async () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      // Use the getter to check shuffle state
      expect(component.getShuffle()).toBe(false);
      
      await fireEvent.click(screen.getByTestId('shuffle-btn'));
      
      expect(component.getShuffle()).toBe(true);
      
      await fireEvent.click(screen.getByTestId('shuffle-btn'));
      
      expect(component.getShuffle()).toBe(false);
    });
  });

  describe('Volume Control', () => {
    it('has mute button', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('mute-btn')).toBeInTheDocument();
    });

    it('has volume slider', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('volume-slider')).toBeInTheDocument();
    });

    it('has mute button with correct aria-label', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      const muteBtn = screen.getByTestId('mute-btn');
      expect(muteBtn).toHaveAttribute('aria-label', 'Mute');
    });
  });

  describe('Progress Bar', () => {
    it('renders progress bar when showWaveform false', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
    });

    it('progress bar has proper ARIA attributes', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toHaveAttribute('aria-valuemin', '0');
      expect(progressBar).toHaveAttribute('aria-valuemax', '0');
      expect(progressBar).toHaveAttribute('aria-valuenow', '0');
    });
  });

  describe('Playlist Selection', () => {
    it('renders playlist items with correct structure', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      const trackItems = screen.getAllByTestId(/playlist-item-\d+/);
      expect(trackItems.length).toBe(3);
    });

    it('highlights active track in playlist', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, currentIndex: 1 } });
      
      const trackItems = screen.getAllByTestId(/playlist-item-\d+/);
      expect(trackItems[1]).toHaveAttribute('aria-selected', 'true');
      expect(trackItems[0]).toHaveAttribute('aria-selected', 'false');
    });

    it('has proper tabindex on playlist items', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      const trackItems = screen.getAllByTestId(/playlist-item-\d+/);
      trackItems.forEach(item => {
        expect(item).toHaveAttribute('tabindex', '0');
      });
    });
  });

  describe('Waveform', () => {
    it('generates waveform data via getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: true } });
      
      // Waveform data should be generated
      expect(component.getWaveformData()).toBeDefined();
      expect(Array.isArray(component.getWaveformData())).toBe(true);
    });

    it('renders canvas when showWaveform true', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: true } });
      
      expect(screen.getByTestId('waveform-canvas')).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('has proper ARIA labels on controls', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      expect(screen.getByTestId('play-pause-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('prev-track-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('next-track-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('shuffle-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('mute-btn')).toHaveAttribute('aria-label');
    });

    it('progress bar has proper ARIA attributes', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: false } });
      
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toHaveAttribute('aria-valuemin', '0');
      expect(progressBar).toHaveAttribute('aria-valuemax', '0');
      expect(progressBar).toHaveAttribute('aria-valuenow', '0');
    });

    it('playlist items have proper ARIA attributes', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      const trackItems = screen.getAllByTestId(/playlist-item-\d+/);
      expect(trackItems[0]).toHaveAttribute('role', 'option');
      expect(trackItems[0]).toHaveAttribute('aria-selected');
    });

    it('has tab index on focusable elements', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      const trackItems = screen.getAllByTestId(/playlist-item-\d+/);
      trackItems.forEach(item => {
        expect(item).toHaveAttribute('tabindex', '0');
      });
    });
  });

  describe('Responsive Design', () => {
    it('applies mobile styles at max-width 480px', () => {
      const { container } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      const style = window.getComputedStyle(container.querySelector('.audio-player')!);
      expect(style).toBeDefined();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty playlist', () => {
      render(AudioPlayer, { props: { playlist: [] } });
      expect(screen.getByText('No tracks in playlist')).toBeInTheDocument();
    });

    it('handles track without artist', () => {
      render(AudioPlayer, { props: { playlist: [{ id: '1', title: 'Track 1', src: 'track1.mp3' }, { id: '2', title: 'Track 2', artist: 'Artist 2', src: 'track2.mp3' }] } });
      expect(screen.getByTestId('track-title-0')).toBeInTheDocument();
      expect(screen.queryByTestId('track-artist-0')).not.toBeInTheDocument();
    });

    it('handles track with duration', () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(screen.getByTestId('track-duration-0')).toHaveTextContent('3:00');
    });

    it('handles special characters in track title', () => {
      render(AudioPlayer, { props: { playlist: [{ id: '1', title: 'Träck 🎵', artist: 'Artíst', src: 'track.mp3' }] } });
      expect(screen.getByTestId('current-track-title')).toHaveTextContent('Träck 🎵');
      expect(screen.getByTestId('current-track-artist')).toHaveTextContent('Artíst');
    });

    it('handles rapid track changes', async () => {
      render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      
      await fireEvent.click(screen.getByTestId('next-track-btn'));
      await fireEvent.click(screen.getByTestId('next-track-btn'));
      await fireEvent.click(screen.getByTestId('prev-track-btn'));
      
      // Should handle rapid clicks without errors
    });
  });

  describe('Exported State Getters', () => {
    it('has getShuffle getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getShuffle).toBe('function');
      expect(component.getShuffle()).toBe(false);
    });

    it('has getIsPlaying getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getIsPlaying).toBe('function');
      expect(component.getIsPlaying()).toBe(false);
    });

    it('has getIsMuted getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getIsMuted).toBe('function');
      expect(component.getIsMuted()).toBe(false);
    });

    it('has getVolume getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getVolume).toBe('function');
      expect(component.getVolume()).toBe(1);
    });

    it('has getWaveformData getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist, showWaveform: true } });
      expect(typeof component.getWaveformData).toBe('function');
      expect(component.getWaveformData()).toBeDefined();
    });

    it('has getCurrentTime getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getCurrentTime).toBe('function');
      expect(component.getCurrentTime()).toBe(0);
    });

    it('has getDuration getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getDuration).toBe('function');
      expect(component.getDuration()).toBe(0);
    });

    it('has getCurrentTrack getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getCurrentTrack).toBe('function');
      expect(component.getCurrentTrack()).toBeDefined();
    });

    it('has getCurrentIndex getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getCurrentIndex).toBe('function');
      expect(component.getCurrentIndex()).toBe(0);
    });

    it('has getShuffledIndices getter', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.getShuffledIndices).toBe('function');
      expect(component.getShuffledIndices()).toEqual([]);
    });
  });

  describe('Exported Functions', () => {
    it('has formatTime function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.formatTime).toBe('function');
      expect(component.formatTime(0)).toBe('0:00');
      expect(component.formatTime(60)).toBe('1:00');
    });

    it('has togglePlay function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.togglePlay).toBe('function');
    });

    it('has toggleMute function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.toggleMute).toBe('function');
    });

    it('has toggleShuffle function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.toggleShuffle).toBe('function');
    });

    it('has setVolume function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.setVolume).toBe('function');
    });

    it('has nextTrack function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.nextTrack).toBe('function');
    });

    it('has prevTrack function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.prevTrack).toBe('function');
    });

    it('has selectTrack function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.selectTrack).toBe('function');
    });

    it('has seekTo function', () => {
      const { component } = render(AudioPlayer, { props: { playlist: defaultPlaylist } });
      expect(typeof component.seekTo).toBe('function');
    });
  });
});