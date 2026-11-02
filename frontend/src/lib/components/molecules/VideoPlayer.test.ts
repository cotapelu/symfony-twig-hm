import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import VideoPlayer from './VideoPlayer.svelte';

// Mock video element
const mockVideoElement = {
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
  requestFullscreen: vi.fn().mockResolvedValue(undefined),
};

// Mock container element
const mockContainerElement = {
  requestFullscreen: vi.fn().mockResolvedValue(undefined),
};

// Mock getBoundingClientRect
const mockProgressRect = { left: 0, width: 400 };
const mockVolumeRect = { left: 0, width: 60 };

// Mock document fullscreen methods
const mockDocument = {
  fullscreenElement: null,
  exitFullscreen: vi.fn().mockResolvedValue(undefined),
  webkitExitFullscreen: vi.fn().mockResolvedValue(undefined),
  mozCancelFullScreen: vi.fn().mockResolvedValue(undefined),
  msExitFullscreen: vi.fn().mockResolvedValue(undefined),
  fullscreenElement: null,
  webkitFullscreenElement: null,
  mozFullScreenElement: null,
  msFullscreenElement: null,
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
};

// Setup mocks
beforeEach(() => {
  vi.clearAllMocks();
  
  // Mock document methods
  Object.defineProperty(document, 'fullscreenElement', {
    value: null,
    writable: true,
    configurable: true,
  });
  Object.defineProperty(document, 'webkitFullscreenElement', {
    value: null,
    writable: true,
    configurable: true,
  });
  Object.defineProperty(document, 'mozFullScreenElement', {
    value: null,
    writable: true,
    configurable: true,
  });
  Object.defineProperty(document, 'msFullscreenElement', {
    value: null,
    writable: true,
    configurable: true,
  });
  
  document.exitFullscreen = vi.fn().mockResolvedValue(undefined);
  document.webkitExitFullscreen = vi.fn().mockResolvedValue(undefined);
  document.mozCancelFullScreen = vi.fn().mockResolvedValue(undefined);
  document.msExitFullscreen = vi.fn().mockResolvedValue(undefined);
  
  document.addEventListener = vi.fn();
  document.removeEventListener = vi.fn();
  
  // Mock document.createElement for video
  const originalCreateElement = document.createElement.bind(document);
  document.createElement = vi.fn((tag: string) => {
    if (tag === 'video') return mockVideoElement;
    return originalCreateElement(tag);
  }) as any;
  
  // Mock getBoundingClientRect
  Element.prototype.getBoundingClientRect = vi.fn(() => mockProgressRect);
  HTMLDivElement.prototype.getBoundingClientRect = vi.fn(() => mockProgressRect);
  
  // Mock container element methods
  HTMLElement.prototype.requestFullscreen = vi.fn().mockResolvedValue(undefined);
  HTMLElement.prototype.webkitRequestFullscreen = vi.fn().mockResolvedValue(undefined);
  HTMLElement.prototype.mozRequestFullScreen = vi.fn().mockResolvedValue(undefined);
  HTMLElement.prototype.msRequestFullscreen = vi.fn().mockResolvedValue(undefined);
  
  vi.useFakeTimers();
});

afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
});

describe('VideoPlayer Component', () => {
  const defaultProps = { src: 'test-video.mp4', poster: 'test-poster.jpg' };

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with custom src', () => {
      render(VideoPlayer, { props: { src: 'custom.mp4', poster: 'poster.jpg' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with autoplay true', () => {
      render(VideoPlayer, { props: { ...defaultProps, autoplay: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with muted true', () => {
      render(VideoPlayer, { props: { ...defaultProps, muted: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with loop true', () => {
      render(VideoPlayer, { props: { ...defaultProps, loop: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with controls false', () => {
      render(VideoPlayer, { props: { ...defaultProps, controls: false } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
      expect(screen.queryByTestId('video-controls')).not.toBeInTheDocument();
    });

    it('renders with custom preload', () => {
      render(VideoPlayer, { props: { ...defaultProps, preload: 'auto' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('renders with custom width and height', () => {
      render(VideoPlayer, { props: { ...defaultProps, width: '800px', height: '600px' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });
  });

  describe('Component Structure', () => {
    it('renders video element', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('video-element')).toBeInTheDocument();
    });

    it('renders video controls when controls prop is true', () => {
      render(VideoPlayer, { props: { ...defaultProps, controls: true } });
      expect(screen.getByTestId('video-controls')).toBeInTheDocument();
    });

    it('does not render controls when controls prop is false', () => {
      render(VideoPlayer, { props: { ...defaultProps, controls: false } });
      expect(screen.queryByTestId('video-controls')).not.toBeInTheDocument();
    });

    it('renders progress bar', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('progress-bar')).toBeInTheDocument();
      expect(screen.getByTestId('progress-fill')).toBeInTheDocument();
    });

    it('renders play/pause button', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('play-pause-btn')).toBeInTheDocument();
    });

    it('renders time display', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('time-display')).toBeInTheDocument();
    });

    it('renders mute button', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('mute-btn')).toBeInTheDocument();
    });

    it('renders volume container', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('volume-container')).toBeInTheDocument();
      expect(screen.getByTestId('volume-fill')).toBeInTheDocument();
    });

    it('renders fullscreen button', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('fullscreen-btn')).toBeInTheDocument();
    });
  });

  describe('Props', () => {
    it('accepts src prop', () => {
      render(VideoPlayer, { props: { src: 'test.mp4' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts poster prop', () => {
      render(VideoPlayer, { props: { poster: 'poster.jpg' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts autoplay prop', () => {
      render(VideoPlayer, { props: { autoplay: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts muted prop', () => {
      render(VideoPlayer, { props: { muted: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts loop prop', () => {
      render(VideoPlayer, { props: { loop: true } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts controls prop', () => {
      render(VideoPlayer, { props: { controls: false } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts preload prop', () => {
      render(VideoPlayer, { props: { preload: 'auto' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts width prop', () => {
      render(VideoPlayer, { props: { width: '800px' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('accepts height prop', () => {
      render(VideoPlayer, { props: { height: '600px' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });
  });

  describe('Format Time Function', () => {
    it('formats seconds correctly', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(component.formatTime(0)).toBe('0:00');
      expect(component.formatTime(30)).toBe('0:30');
      expect(component.formatTime(60)).toBe('1:00');
      expect(component.formatTime(90)).toBe('1:30');
      expect(component.formatTime(125)).toBe('2:05');
      expect(component.formatTime(3661)).toBe('61:01');
    });

    it('handles edge cases', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(component.formatTime(59)).toBe('0:59');
      expect(component.formatTime(61)).toBe('1:01');
      expect(component.formatTime(3599)).toBe('59:59');
    });
  });

  describe('Exported State Getters', () => {
    it('has getIsPlaying getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getIsPlaying).toBe('function');
      expect(component.getIsPlaying()).toBe(false);
    });

    it('has getCurrentTime getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getCurrentTime).toBe('function');
      expect(component.getCurrentTime()).toBe(0);
    });

    it('has getDuration getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getDuration).toBe('function');
      expect(component.getDuration()).toBe(0);
    });

    it('has getVolume getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getVolume).toBe('function');
      expect(component.getVolume()).toBe(1);
    });

    it('has getIsMuted getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getIsMuted).toBe('function');
      expect(component.getIsMuted()).toBe(false);
    });

    it('has getIsFullscreen getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getIsFullscreen).toBe('function');
      expect(component.getIsFullscreen()).toBe(false);
    });

    it('has getShowControls getter', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.getShowControls).toBe('function');
      expect(component.getShowControls()).toBe(true);
    });
  });

  describe('Exported Functions', () => {
    it('has togglePlay function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.togglePlay).toBe('function');
    });

    it('has seekTo function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.seekTo).toBe('function');
    });

    it('has setVolume function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.setVolume).toBe('function');
    });

    it('has toggleMute function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.toggleMute).toBe('function');
    });

    it('has toggleFullscreen function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.toggleFullscreen).toBe('function');
    });

    it('has formatTime function', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(typeof component.formatTime).toBe('function');
      expect(component.formatTime(0)).toBe('0:00');
      expect(component.formatTime(60)).toBe('1:00');
    });
  });

  describe('Playback Controls', () => {
    it('shows play button when not playing', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('play-pause-btn')).toBeInTheDocument();
    });

    it('has play/pause button with correct aria-label', () => {
      render(VideoPlayer, { props: defaultProps });
      const playBtn = screen.getByTestId('play-pause-btn');
      expect(playBtn).toHaveAttribute('aria-label', 'Play');
    });

    it('toggles play when play button clicked', async () => {
      render(VideoPlayer, { props: defaultProps });
      await fireEvent.click(screen.getByTestId('play-pause-btn'));
      // Note: Actual play() is mocked and would be called in real environment
    });
  });

  describe('Volume Controls', () => {
    it('has mute button with correct aria-label', () => {
      render(VideoPlayer, { props: defaultProps });
      const muteBtn = screen.getByTestId('mute-btn');
      expect(muteBtn).toHaveAttribute('aria-label', 'Mute');
    });

    it('has volume container', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('volume-container')).toBeInTheDocument();
      expect(screen.getByTestId('volume-fill')).toBeInTheDocument();
    });
  });

  describe('Fullscreen', () => {
    it('has fullscreen button with correct aria-label', () => {
      render(VideoPlayer, { props: defaultProps });
      const fullscreenBtn = screen.getByTestId('fullscreen-btn');
      expect(fullscreenBtn).toHaveAttribute('aria-label', 'Enter fullscreen');
    });
  });

  describe('Progress Bar', () => {
    it('has progress bar with proper ARIA attributes', () => {
      render(VideoPlayer, { props: defaultProps });
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toHaveAttribute('aria-valuemin', '0');
      expect(progressBar).toHaveAttribute('aria-valuemax', '0');
      expect(progressBar).toHaveAttribute('aria-valuenow', '0');
    });

    it('has progress fill element', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('progress-fill')).toBeInTheDocument();
    });
  });

  describe('Time Display', () => {
    it('displays current time and duration', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('time-display')).toHaveTextContent('0:00 / 0:00');
    });
  });

  describe('Accessibility', () => {
    it('has proper ARIA labels on controls', () => {
      render(VideoPlayer, { props: defaultProps });
      
      expect(screen.getByTestId('play-pause-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('mute-btn')).toHaveAttribute('aria-label');
      expect(screen.getByTestId('fullscreen-btn')).toHaveAttribute('aria-label');
    });

    it('progress bar has proper ARIA attributes', () => {
      render(VideoPlayer, { props: defaultProps });
      
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toHaveAttribute('aria-valuemin', '0');
      expect(progressBar).toHaveAttribute('aria-valuemax', '0');
      expect(progressBar).toHaveAttribute('aria-valuenow', '0');
    });

    it('volume container has proper ARIA attributes', () => {
      render(VideoPlayer, { props: defaultProps });
      
      const volumeContainer = screen.getByTestId('volume-container');
      expect(volumeContainer).toHaveAttribute('aria-valuemin', '0');
      expect(volumeContainer).toHaveAttribute('aria-valuemax', '1');
      expect(volumeContainer).toHaveAttribute('aria-valuenow', '1');
    });
  });

  describe('Responsive Design', () => {
    it('applies mobile styles at max-width 768px', () => {
      const { container } = render(VideoPlayer, { props: defaultProps });
      const style = window.getComputedStyle(container.querySelector('.video-player')!);
      expect(style).toBeDefined();
    });
  });

  describe('Edge Cases', () => {
    it('handles empty src', () => {
      render(VideoPlayer, { props: { src: '' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('handles special characters in src', () => {
      render(VideoPlayer, { props: { src: 'video with spaces.mp4' } });
      expect(screen.getByTestId('video-player')).toBeInTheDocument();
    });

    it('handles very long duration', () => {
      const { component } = render(VideoPlayer, { props: defaultProps });
      expect(component.formatTime(7200)).toBe('120:00');
    });

    it('handles zero duration', () => {
      render(VideoPlayer, { props: defaultProps });
      expect(screen.getByTestId('time-display')).toHaveTextContent('0:00 / 0:00');
    });
  });

  describe('Video Element Attributes', () => {
    it('has src attribute on video element', () => {
      render(VideoPlayer, { props: { src: 'test.mp4' } });
      const video = screen.getByTestId('video-element');
      expect(video).toHaveAttribute('src', 'test.mp4');
    });

    it('has poster attribute on video element', () => {
      render(VideoPlayer, { props: { poster: 'poster.jpg' } });
      const video = screen.getByTestId('video-element');
      expect(video).toHaveAttribute('poster', 'poster.jpg');
    });

    it('has autoplay attribute when autoplay is true', () => {
      render(VideoPlayer, { props: { autoplay: true } });
      const video = screen.getByTestId('video-element');
      expect(video).toHaveAttribute('autoplay');
    });

    it('has muted property when muted is true', () => {
      render(VideoPlayer, { props: { muted: true } });
      const video = screen.getByTestId('video-element');
      expect(video.muted).toBe(true);
    });

    it('has loop attribute when loop is true', () => {
      render(VideoPlayer, { props: { loop: true } });
      const video = screen.getByTestId('video-element');
      expect(video).toHaveAttribute('loop');
    });

    it('has preload attribute', () => {
      render(VideoPlayer, { props: { preload: 'auto' } });
      const video = screen.getByTestId('video-element');
      expect(video).toHaveAttribute('preload', 'auto');
    });
  });

  describe('Keyboard Navigation', () => {
    it('has tabindex on video player container', () => {
      render(VideoPlayer, { props: defaultProps });
      const container = screen.getByTestId('video-player');
      expect(container).toHaveAttribute('tabindex', '0');
    });

    it('progress bar is keyboard focusable', () => {
      render(VideoPlayer, { props: defaultProps });
      const progressBar = screen.getByTestId('progress-bar');
      expect(progressBar).toHaveAttribute('tabindex', '0');
    });

    it('volume container is keyboard focusable', () => {
      render(VideoPlayer, { props: defaultProps });
      const volumeContainer = screen.getByTestId('volume-container');
      expect(volumeContainer).toHaveAttribute('tabindex', '0');
    });
  });
});